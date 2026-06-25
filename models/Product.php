<?php
namespace Models;

use Config\Database;
use PDO;

class Product {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get product count
     * @return int
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as cnt FROM products";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Retrieve products in batches (for scanner)
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getBatch($offset, $limit) {
        $sql = "SELECT id, product_code, product_name, current_category, price, supplier, other_fields_json 
                FROM products 
                ORDER BY id ASC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Batch insert or update products
     * @param array $products Array of products, each being an associative array of columns
     * @return int Number of affected rows
     */
    public function importBatch($products) {
        if (empty($products)) {
            return 0;
        }

        // Build batch insert query with ON DUPLICATE KEY UPDATE
        $insert_parts = [];
        $bindings = [];
        
        $sql = "INSERT INTO products (product_code, product_name, current_category, price, supplier, other_fields_json) VALUES ";
        
        $index = 0;
        foreach ($products as $p) {
            $insert_parts[] = "(?, ?, ?, ?, ?, ?)";
            
            $bindings[] = $p['product_code'];
            $bindings[] = $p['product_name'];
            $bindings[] = $p['current_category'];
            $bindings[] = (float)$p['price'];
            $bindings[] = $p['supplier'] ?? null;
            $bindings[] = isset($p['other_fields_json']) ? json_encode($p['other_fields_json']) : null;
            $index++;
        }

        $sql .= implode(', ', $insert_parts);
        $sql .= " ON DUPLICATE KEY UPDATE 
                    product_name = VALUES(product_name), 
                    current_category = VALUES(current_category), 
                    price = VALUES(price), 
                    supplier = VALUES(supplier), 
                    other_fields_json = VALUES(other_fields_json)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        
        return $stmt->rowCount();
    }

    /**
     * Update product category and clear pending suggestions
     * @param int $product_id
     * @param string $new_category
     * @return bool
     */
    public function updateCategory($product_id, $new_category) {
        $sql = "UPDATE products SET current_category = :category WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([
            ':category' => $new_category,
            ':id' => $product_id
        ]);
        if ($res) {
            // Delete pending suggestions for this product since it is manually categorized now
            $sql_del = "DELETE FROM category_suggestions WHERE product_id = :product_id AND status = 'pending'";
            $stmt_del = $this->db->prepare($sql_del);
            $stmt_del->execute([':product_id' => $product_id]);
        }
        return $res;
    }

    /**
     * Bulk update categories of products and clear pending suggestions
     * @param array $ids
     * @param string $category
     * @return bool
     */
    public function bulkUpdateCategory($ids, $category) {
        if (empty($ids)) return false;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Update product category
        $sql = "UPDATE products SET current_category = ? WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $params = array_merge([$category], $ids);
        $res = $stmt->execute($params);
        
        if ($res) {
            // Delete pending suggestions for these products
            $sql_del = "DELETE FROM category_suggestions WHERE product_id IN ($placeholders) AND status = 'pending'";
            $stmt_del = $this->db->prepare($sql_del);
            $stmt_del->execute($ids);
        }
        return $res;
    }

    /**
     * Retrieve products for DataTable server-side processing
     */
    public function getFilteredProducts($search, $start, $length, $order_column, $order_dir, $category_filter, $product_name_filter = '', $supplier_filter = '') {
        $sql = "SELECT id, product_code, product_name, current_category, price, supplier, other_fields_json 
                FROM products WHERE 1=1";
        $params = [];

        if (!empty($category_filter)) {
            $sql .= " AND current_category = :category_filter";
            $params[':category_filter'] = $category_filter;
        }

        if (!empty($supplier_filter)) {
            $sql .= " AND supplier = :supplier_filter";
            $params[':supplier_filter'] = $supplier_filter;
        }

        if (!empty($product_name_filter)) {
            $sql .= " AND product_name LIKE :product_name_filter";
            $params[':product_name_filter'] = '%' . $product_name_filter . '%';
        }

        if (!empty($search)) {
            $sql .= " AND (product_code LIKE :search OR product_name LIKE :search OR supplier LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        // Map column index to SQL columns for ordering
        // Column 0 is the checkbox, so starts ordering at index 1
        $columns = [
            1 => 'product_name',
            2 => 'current_category',
            3 => 'supplier',
            4 => 'price'
        ];
        
        $order_by = 'id';
        if (isset($columns[$order_column])) {
            $order_by = $columns[$order_column];
        }

        $order_dir = strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY $order_by $order_dir LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$length, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$start, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of filtered products for DataTable pagination total
     */
    public function getFilteredProductsCount($search, $category_filter, $product_name_filter = '', $supplier_filter = '') {
        $sql = "SELECT COUNT(*) as cnt FROM products WHERE 1=1";
        $params = [];

        if (!empty($category_filter)) {
            $sql .= " AND current_category = :category_filter";
            $params[':category_filter'] = $category_filter;
        }

        if (!empty($supplier_filter)) {
            $sql .= " AND supplier = :supplier_filter";
            $params[':supplier_filter'] = $supplier_filter;
        }

        if (!empty($product_name_filter)) {
            $sql .= " AND product_name LIKE :product_name_filter";
            $params[':product_name_filter'] = '%' . $product_name_filter . '%';
        }

        if (!empty($search)) {
            $sql .= " AND (product_code LIKE :search OR product_name LIKE :search OR supplier LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Get all unique suppliers in database
     * @return array
     */
    public function getUniqueSuppliers() {
        $sql = "SELECT DISTINCT supplier FROM products WHERE supplier IS NOT NULL AND supplier != '' ORDER BY supplier ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
