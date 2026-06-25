<?php
require_once __DIR__ . '/config/bootstrap.php';

use Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Query all products and resolve main categories
$db = Database::getConnection();
$sql = "SELECT p.code, p.product_code, p.product_name, p.current_category, c.main_category, p.cost_price, p.selling_price, p.supplier, p.other_fields_json
        FROM products p
        LEFT JOIN categories c ON p.current_category = c.category_name
        ORDER BY p.product_name ASC";
$stmt = $db->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Products Catalog');

// Set Header Row
$headers = ['Code', 'SKU', 'Product Name', 'Product Category', 'Cost Price', 'Selling Price', 'Supplier Name'];

// Gather all unique keys from other_fields_json across all rows to dynamically add them as headers!
$dynamicKeys = [];
foreach ($products as $p) {
    if (!empty($p['other_fields_json'])) {
        $extra = json_decode($p['other_fields_json'], true);
        if (is_array($extra)) {
            foreach (array_keys($extra) as $key) {
                if (!in_array($key, $dynamicKeys)) {
                    $dynamicKeys[] = $key;
                }
            }
        }
    }
}

// Append dynamic keys to headers
$fullHeaders = array_merge($headers, $dynamicKeys);

// Populate Headers
$col = 'A';
foreach ($fullHeaders as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $col++;
}

// Populate Data Row by Row
$rowNum = 2;
foreach ($products as $p) {
    $sheet->setCellValue('A' . $rowNum, $p['code'] ?? '');
    $sheet->setCellValue('B' . $rowNum, $p['product_code']);
    $sheet->setCellValue('C' . $rowNum, $p['product_name']);
    $sheet->setCellValue('D' . $rowNum, $p['current_category'] ?? 'Uncategorized');
    
    // Format cost price
    $sheet->setCellValue('E' . $rowNum, (float)$p['cost_price']);
    $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('"Rs. "#,##0.00');
    
    // Format selling price
    $sheet->setCellValue('F' . $rowNum, (float)$p['selling_price']);
    $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('"Rs. "#,##0.00');
    
    $sheet->setCellValue('G' . $rowNum, $p['supplier'] ?? '');
    
    // Fill dynamic fields
    $extra = !empty($p['other_fields_json']) ? json_decode($p['other_fields_json'], true) : [];
    $col = 'H';
    foreach ($dynamicKeys as $key) {
        $val = $extra[$key] ?? '';
        $sheet->setCellValue($col . $rowNum, $val);
        $col++;
    }
    
    $rowNum++;
}

// Auto-size columns for better layout
$highestCol = $sheet->getHighestColumn();
$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
for ($i = 1; $i <= $highestColIndex; $i++) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

// Set Headers for Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Tiny_Togs_Product_Master_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
