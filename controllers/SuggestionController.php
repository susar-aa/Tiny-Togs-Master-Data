<?php
namespace Controllers;

use Models\Suggestion;
use Models\Category;
use Models\Product;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuggestionController {
    private $suggestionModel;

    public function __construct() {
        $this->suggestionModel = new Suggestion();
    }

    /**
     * Handle AJAX requests from DataTables server-side
     */
    public function handleDataTableRequest() {
        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
        $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
        
        // Search
        $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
        
        // Sorting
        $order_column = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 8; // Default to created_at
        $order_dir = isset($_POST['order'][0]['dir']) ? trim($_POST['order'][0]['dir']) : 'desc';

        // Custom filters
        $filters = [
            'status' => isset($_POST['status']) ? trim($_POST['status']) : '',
            'current_category' => isset($_POST['current_category']) ? trim($_POST['current_category']) : '',
            'suggested_category' => isset($_POST['suggested_category']) ? trim($_POST['suggested_category']) : '',
            'min_confidence' => isset($_POST['min_confidence']) ? trim($_POST['min_confidence']) : ''
        ];

        // Fetch data
        $data = $this->suggestionModel->getFilteredSuggestions($filters, $search, $start, $length, $order_column, $order_dir);
        $filtered_count = $this->suggestionModel->getFilteredSuggestionsCount($filters, $search);
        $total_count = $this->suggestionModel->getTotalCount();

        // Format data for DataTables
        $formatted_data = [];
        foreach ($data as $row) {
            $formatted_data[] = [
                'id' => $row['id'],
                'product_id' => $row['product_id'],
                'product_code' => htmlspecialchars($row['product_code']),
                'product_name' => htmlspecialchars($row['product_name']),
                'current_category' => htmlspecialchars($row['current_category']),
                'suggested_category' => htmlspecialchars($row['suggested_category']),
                'matched_keyword' => htmlspecialchars($row['matched_keyword']),
                'confidence_score' => $row['confidence_score'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }

        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $total_count,
            "recordsFiltered" => $filtered_count,
            "data" => $formatted_data
        ]);
        exit;
    }

    /**
     * Get suggestions details for view modal
     */
    public function getDetails($id) {
        $detail = $this->suggestionModel->getById($id);
        if (!$detail) {
            echo json_encode(['status' => 'error', 'message' => 'Suggestion not found.']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $detail['id'],
                'product_id' => $detail['product_id'],
                'product_code' => htmlspecialchars($detail['product_code']),
                'product_name' => htmlspecialchars($detail['product_name']),
                'price' => number_format($detail['price'], 2),
                'supplier' => htmlspecialchars($detail['supplier'] ?? 'N/A'),
                'current_category' => htmlspecialchars($detail['current_category']),
                'suggested_category' => htmlspecialchars($detail['suggested_category']),
                'matched_keyword' => htmlspecialchars($detail['matched_keyword']),
                'confidence_score' => $detail['confidence_score'],
                'status' => $detail['status']
            ]
        ]);
        exit;
    }

    /**
     * Approve a suggestion
     */
    public function approve($id) {
        try {
            $result = $this->suggestionModel->approve($id);
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Category correction approved successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to approve. Record might not exist.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Ignore a suggestion
     */
    public function ignore($id) {
        $result = $this->suggestionModel->ignore($id);
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Category suggestion ignored.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to ignore.']);
        }
        exit;
    }

    /**
     * Bulk Approve
     */
    public function bulkApprove($ids) {
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No items selected.']);
            exit;
        }

        try {
            $count = $this->suggestionModel->bulkApprove($ids);
            echo json_encode(['status' => 'success', 'message' => "Successfully approved {$count} category corrections."]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Bulk approval failed: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Bulk Ignore
     */
    public function bulkIgnore($ids) {
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No items selected.']);
            exit;
        }

        $count = $this->suggestionModel->bulkIgnore($ids);
        echo json_encode(['status' => 'success', 'message' => "Successfully ignored {$count} category suggestions."]);
        exit;
    }

    /**
     * Export report to Excel or CSV
     */
    public function exportReport($filters, $format) {
        $data = $this->suggestionModel->getSuggestionsForExport($filters);

        if ($format === 'excel') {
            $this->exportToExcel($data);
        } else {
            $this->exportToCsv($data);
        }
        exit;
    }

    /**
     * Generate Excel sheet using PhpSpreadsheet
     */
    private function exportToExcel($data) {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set Header Styling
            $sheet->setCellValue('A1', 'Product Name')
                  ->setCellValue('B1', 'Product Code')
                  ->setCellValue('C1', 'Current Category')
                  ->setCellValue('D1', 'Suggested Category')
                  ->setCellValue('E1', 'Suggested Main Category')
                  ->setCellValue('F1', 'Confidence')
                  ->setCellValue('G1', 'Matched Keyword')
                  ->setCellValue('H1', 'Status');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

            $row_idx = 2;
            foreach ($data as $row) {
                $sheet->setCellValue('A' . $row_idx, $row['product_name'])
                      ->setCellValue('B' . $row_idx, $row['product_code'])
                      ->setCellValue('C' . $row_idx, $row['current_category'])
                      ->setCellValue('D' . $row_idx, $row['suggested_category'])
                      ->setCellValue('E' . $row_idx, $row['main_category'] ?? 'None')
                      ->setCellValue('F' . $row_idx, $row['confidence_score'] . '%')
                      ->setCellValue('G' . $row_idx, $row['matched_keyword'])
                      ->setCellValue('H' . $row_idx, ucfirst($row['status']));
                $row_idx++;
            }

            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="suspicious_products_report_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            
        } catch (\Exception $e) {
            die("Excel export failed: " . $e->getMessage());
        }
    }

    /**
     * Generate CSV file
     */
    private function exportToCsv($data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="suspicious_products_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, ['Product Name', 'Product Code', 'Current Category', 'Suggested Category', 'Suggested Main Category', 'Confidence', 'Matched Keyword', 'Status']);
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['product_name'],
                $row['product_code'],
                $row['current_category'],
                $row['suggested_category'],
                $row['main_category'] ?? 'None',
                $row['confidence_score'] . '%',
                $row['matched_keyword'],
                ucfirst($row['status'])
            ]);
        }
        
        fclose($output);
    }
}
