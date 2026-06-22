<?php
namespace Controllers;

use Models\Product;
use Models\Log;

class ProductController {
    
    /**
     * Handle DataTable AJAX requests
     */
    public function handleDataTableRequest() {
        header('Content-Type: application/json');
        
        $search = $_POST['search']['value'] ?? '';
        $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
        
        $order_column = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
        $order_dir = $_POST['order'][0]['dir'] ?? 'asc';
        
        $category_filter = $_POST['category_filter'] ?? '';
        $product_name_filter = $_POST['product_name_filter'] ?? '';
        $supplier_filter = $_POST['supplier_filter'] ?? '';
        
        $productModel = new Product();
        $data = $productModel->getFilteredProducts($search, $start, $length, $order_column, $order_dir, $category_filter, $product_name_filter, $supplier_filter);
        $total_count = $productModel->getCount();
        $filtered_count = $productModel->getFilteredProductsCount($search, $category_filter, $product_name_filter, $supplier_filter);
        
        $response = [
            'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
            'recordsTotal' => $total_count,
            'recordsFiltered' => $filtered_count,
            'data' => $data
        ];
        
        echo json_encode($response);
        exit;
    }

    /**
     * Update single product category
     */
    public function updateCategory($product_id, $category) {
        header('Content-Type: application/json');
        
        $productModel = new Product();
        $res = $productModel->updateCategory($product_id, $category);
        
        if ($res) {
            $logModel = new Log();
            $logModel->record('Product Catalog Change', "Updated category of Product ID {$product_id} to '{$category}'.");
            
            echo json_encode(['status' => 'success', 'message' => 'Category updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update category.']);
        }
        exit;
    }

    /**
     * Bulk update categories of products
     */
    public function bulkUpdateCategory($ids, $category) {
        header('Content-Type: application/json');
        
        if (empty($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No products selected.']);
            exit;
        }

        $productModel = new Product();
        $res = $productModel->bulkUpdateCategory($ids, $category);
        
        if ($res) {
            $count = count($ids);
            $logModel = new Log();
            $logModel->record('Product Catalog Bulk Change', "Updated categories of {$count} products to '{$category}' in bulk.");
            
            echo json_encode(['status' => 'success', 'message' => "Successfully updated category of {$count} products."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to perform bulk category update.']);
        }
        exit;
    }

    /**
     * Get details for single product (modal view)
     */
    public function getDetails($id) {
        header('Content-Type: application/json');
        
        $productModel = new Product();
        $db = \Config\Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        }
        exit;
    }
}
