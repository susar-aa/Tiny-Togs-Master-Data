<?php
namespace Controllers;

use Config\Database;
use Models\Category;
use Models\Product;
use Models\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

// Chunk read filter for memory efficiency with large spreadsheets
class ChunkReadFilter implements IReadFilter {
    private $startRow = 0;
    private $endRow = 0;

    public function __construct($startRow, $chunkSize) {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
        // We always need the header row (row 1) and our chunk range
        if ($row == 1 || ($row >= $this->startRow && $row <= $this->endRow)) {
            return true;
        }
        return false;
    }
}

class ImportController {
    
    /**
     * Handle category Excel import
     */
    public function importCategories($file_tmp) {
        if (!file_exists($file_tmp)) {
            return ['status' => 'error', 'message' => 'Upload file not found.'];
        }

        try {
            $spreadsheet = IOFactory::load($file_tmp);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);
            
            if (count($rows) <= 1) {
                return ['status' => 'error', 'message' => 'The uploaded file is empty.'];
            }

            // Parse headers
            $headers = array_map('trim', array_shift($rows)); // Extract and trim header row
            
            // Map columns
            $cat_col = null;
            $main_col = null;
            $items_col = null;
            
            foreach ($headers as $col_letter => $header_name) {
                $lower_name = strtolower($header_name);
                if (strpos($lower_name, 'main category') !== false) {
                    $main_col = $col_letter;
                } elseif (strpos($lower_name, 'category') !== false) {
                    $cat_col = $col_letter;
                } elseif (strpos($lower_name, 'item') !== false || strpos($lower_name, 'keyword') !== false) {
                    $items_col = $col_letter;
                }
            }

            // Fallback to columns A, B and C if headers not matched
            if ($cat_col === null) $cat_col = 'A';
            if ($main_col === null) $main_col = 'B';
            if ($items_col === null) $items_col = 'C';

            $categoryModel = new Category();
            $logModel = new Log();
            
            $imported_count = 0;
            $keyword_count = 0;

            foreach ($rows as $row) {
                $category_name = $row[$cat_col] ?? '';
                $main_category = $row[$main_col] ?? '';
                $including_items = $row[$items_col] ?? '';

                if (empty(trim($category_name))) {
                    continue;
                }

                $cat_id = $categoryModel->importCategory($category_name, $including_items, $main_category);
                if ($cat_id) {
                    $imported_count++;
                    if (!empty($including_items)) {
                        $keyword_count += count(explode(',', $including_items));
                    }
                }
            }

            $logModel->record('Import Category File', "Imported {$imported_count} categories and associated {$keyword_count} keywords.");
            
            return [
                'status' => 'success',
                'message' => "Successfully imported {$imported_count} categories and {$keyword_count} keywords."
            ];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Excel processing failed: ' . $e->getMessage()];
        }
    }

    /**
     * Initialize product import: moves file to uploads and returns info
     */
    public function initProductImport($file) {
        $upload_dir = ROOT_PATH . 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['xlsx', 'xls', 'csv'])) {
            return ['status' => 'error', 'message' => 'Invalid file format. Please upload XLSX, XLS, or CSV.'];
        }

        $temp_filename = 'products_temp_' . time() . '.' . $extension;
        $dest_path = $upload_dir . $temp_filename;

        if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
            return ['status' => 'error', 'message' => 'Failed to save uploaded file.'];
        }

        try {
            // Get reader to count total rows without loading full data
            $reader = IOFactory::createReaderForFile($dest_path);
            $reader->setReadDataOnly(true);
            $info = $reader->listWorksheetInfo($dest_path);
            
            $total_rows = 0;
            if (!empty($info)) {
                $total_rows = $info[0]['totalRows'];
            }

            if ($total_rows <= 1) {
                unlink($dest_path);
                return ['status' => 'error', 'message' => 'The uploaded file has no product rows.'];
            }

            return [
                'status' => 'success',
                'file_path' => 'uploads/' . $temp_filename,
                'total_rows' => $total_rows - 1 // Exclude header row
            ];

        } catch (\Exception $e) {
            if (file_exists($dest_path)) unlink($dest_path);
            return ['status' => 'error', 'message' => 'Error reading workbook: ' . $e->getMessage()];
        }
    }

    /**
     * Process a batch of product records
     */
    public function processProductBatch($relative_path, $offset, $limit) {
        $file_path = ROOT_PATH . $relative_path;
        if (!file_exists($file_path)) {
            return ['status' => 'error', 'message' => 'Temporary file not found.'];
        }

        try {
            $reader = IOFactory::createReaderForFile($file_path);
            $reader->setReadDataOnly(true);
            
            // Set chunk read filter
            // Offset is 0-indexed count of products.
            // Spreadsheets are 1-indexed, and row 1 is header.
            // So startRow should be offset + 2 (row 2 corresponds to offset 0)
            $startRow = $offset + 2;
            $chunkFilter = new ChunkReadFilter($startRow, $limit);
            $reader->setReadFilter($chunkFilter);
            
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);
            
            if (count($rows) <= 1) {
                return [
                    'status' => 'success',
                    'imported' => 0,
                    'done' => true
                ];
            }

            // Row 1 is header
            $headers = array_map('trim', array_shift($rows));
            
            // Map column indexes based on header text
            $code_col = null;
            $name_col = null;
            $cat_col = null;
            $price_col = null;
            $sup_col = null;
            $other_cols = [];

            foreach ($headers as $col_letter => $header_name) {
                $lower = strtolower($header_name);
                
                // 1. Supplier / Vendor
                if (strpos($lower, 'supplier') !== false || strpos($lower, 'vendor') !== false) {
                    $sup_col = $col_letter;
                }
                // 2. Category
                elseif (strpos($lower, 'category') !== false) {
                    $cat_col = $col_letter;
                }
                // 3. Price (Prefer Selling Price if both cost and selling exist)
                elseif (strpos($lower, 'selling price') !== false) {
                    if ($price_col !== null) {
                        $other_cols[$price_col] = $headers[$price_col];
                    }
                    $price_col = $col_letter;
                }
                elseif (strpos($lower, 'price') !== false || strpos($lower, 'rate') !== false || strpos($lower, 'cost') !== false) {
                    if ($price_col === null) {
                        $price_col = $col_letter;
                    } else {
                        $other_cols[$col_letter] = $header_name;
                    }
                }
                // 4. Product Name
                elseif (strpos($lower, 'product name') !== false) {
                    $name_col = $col_letter;
                }
                elseif (strpos($lower, 'name') !== false || strpos($lower, 'title') !== false) {
                    if ($name_col === null) {
                        $name_col = $col_letter;
                    } else {
                        $other_cols[$col_letter] = $header_name;
                    }
                }
                // 5. Product Code / SKU
                elseif (strpos($lower, 'sku') !== false) {
                    if ($code_col !== null) {
                        $other_cols[$code_col] = $headers[$code_col];
                    }
                    $code_col = $col_letter;
                }
                elseif (strpos($lower, 'code') !== false) {
                    if ($code_col === null) {
                        $code_col = $col_letter;
                    } else {
                        $other_cols[$col_letter] = $header_name;
                    }
                }
                // 6. Other attributes
                else {
                    if (!empty($header_name)) {
                        $other_cols[$col_letter] = $header_name;
                    }
                }
            }

            // Fallbacks
            if ($code_col === null) $code_col = 'A';
            if ($name_col === null) $name_col = 'B';
            if ($cat_col === null) $cat_col = 'C';
            if ($price_col === null) $price_col = 'D';
            if ($sup_col === null) $sup_col = 'E';

            $products_batch = [];
            
            // Only process rows that correspond to our filter
            foreach ($rows as $row_num => $row) {
                // Double check if row is within range (PhpSpreadsheet might read trailing empty rows)
                if ($row_num < $startRow || $row_num > ($startRow + $limit - 1)) {
                    continue;
                }

                $code = trim($row[$code_col] ?? '');
                $name = trim($row[$name_col] ?? '');
                $category = trim($row[$cat_col] ?? '');
                
                // If code and name are empty, skip
                if (empty($code) && empty($name)) {
                    continue;
                }

                // Auto-create category if missing
                if (!empty($category)) {
                    $categoryModel = new \Models\Category();
                    $categoryModel->getOrCreate($category);
                }

                $price = $row[$price_col] ?? 0.0;
                $supplier = trim($row[$sup_col] ?? '');
                
                // Extra fields
                $extra_fields = [];
                foreach ($other_cols as $col_letter => $header_name) {
                    if (isset($row[$col_letter]) && $row[$col_letter] !== '') {
                        $extra_fields[$header_name] = $row[$col_letter];
                    }
                }

                $products_batch[] = [
                    'product_code' => $code,
                    'product_name' => $name,
                    'current_category' => $category,
                    'price' => $price,
                    'supplier' => empty($supplier) ? null : $supplier,
                    'other_fields_json' => empty($extra_fields) ? null : $extra_fields
                ];
            }

            $productModel = new Product();
            $affected_rows = 0;
            if (!empty($products_batch)) {
                $affected_rows = $productModel->importBatch($products_batch);
            }

            $imported_count = count($products_batch);
            
            return [
                'status' => 'success',
                'imported' => $imported_count,
                'affected_rows' => $affected_rows,
                'done' => false
            ];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Batch processing error: ' . $e->getMessage()];
        }
    }

    /**
     * Finalize the product import (cleanup & log)
     */
    public function finalizeProductImport($relative_path, $total_imported) {
        $file_path = ROOT_PATH . $relative_path;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $logModel = new Log();
        $logModel->record('Import Product File', "Successfully imported/updated {$total_imported} products from Excel.");

        return [
            'status' => 'success',
            'message' => "Successfully imported/updated {$total_imported} products."
        ];
    }
}
