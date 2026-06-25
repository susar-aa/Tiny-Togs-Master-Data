<?php
namespace Models;

use Config\Database;
use PDO;

class Supplier {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS suppliers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                supplier_name VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Fix existing table collation if it was created with wrong collation
        $this->db->exec("ALTER TABLE suppliers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Get all suppliers with product counts
     * @return array
     */
    public function getAll() {
        $sql = "SELECT s.id, s.supplier_name AS name, s.created_at,
                       (SELECT COUNT(*) FROM products p WHERE p.supplier COLLATE utf8mb4_unicode_ci = s.supplier_name) AS product_count
                FROM suppliers s
                ORDER BY s.supplier_name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of suppliers
     * @return int
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as cnt FROM suppliers";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Save/Create a supplier
     * @param string $name
     * @return bool|int
     */
    public function save($name) {
        $name = trim($name);
        if (empty($name)) return false;

        $sql = "INSERT INTO suppliers (supplier_name) VALUES (:name) ON DUPLICATE KEY UPDATE supplier_name = :name2";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name, ':name2' => $name]);
        return $this->db->lastInsertId() ?: true;
    }

    /**
     * Update supplier name
     * @param int $id
     * @param string $new_name
     * @return bool
     */
    public function update($id, $new_name) {
        $id = (int)$id;
        $new_name = trim($new_name);
        if (!$id || empty($new_name)) return false;

        try {
            $this->db->beginTransaction();

            // Fetch old name first to update dependencies
            $stmt = $this->db->prepare("SELECT supplier_name FROM suppliers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $old_name = $stmt->fetchColumn();

            // Update supplier
            $stmt = $this->db->prepare("UPDATE suppliers SET supplier_name = :new_name WHERE id = :id");
            $stmt->execute([
                ':new_name' => $new_name,
                ':id' => $id
            ]);

            // Update products dependency
            if ($old_name) {
                $stmt = $this->db->prepare("UPDATE products SET supplier = :new_name WHERE supplier = :old_name");
                $stmt->execute([
                    ':new_name' => $new_name,
                    ':old_name' => $old_name
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete supplier
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $id = (int)$id;
        if (!$id) return false;

        try {
            $this->db->beginTransaction();

            // Fetch name to clear references
            $stmt = $this->db->prepare("SELECT supplier_name FROM suppliers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $name = $stmt->fetchColumn();

            // Delete supplier
            $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Set supplier to NULL for products referencing this supplier
            if ($name) {
                $stmt = $this->db->prepare("UPDATE products SET supplier = NULL WHERE supplier = :name");
                $stmt->execute([':name' => $name]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Transfer products from one supplier to another
     * @param string $from_supplier
     * @param string $to_supplier
     * @return int|false Number of products transferred or false on failure
     */
    public function transferProducts($from_supplier, $to_supplier) {
        if (empty($from_supplier) || empty($to_supplier) || $from_supplier === $to_supplier) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE products SET supplier = :to_supplier WHERE supplier = :from_supplier");
        $stmt->execute([
            ':to_supplier' => $to_supplier,
            ':from_supplier' => $from_supplier
        ]);
        return $stmt->rowCount();
    }

    /**
     * Bulk update supplier for selected products
     * @param array $product_ids
     * @param string $supplier_name
     * @return bool
     */
    public function bulkUpdateProductsSupplier($product_ids, $supplier_name) {
        if (empty($product_ids)) return false;

        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $sql = "UPDATE products SET supplier = ? WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $params = array_merge([$supplier_name], $product_ids);
        return $stmt->execute($params);
    }

    /**
     * Get products by supplier name
     * @param string $supplier_name
     * @return array
     */
    public function getProductsBySupplier($supplier_name) {
        $sql = "SELECT id, product_code, product_name, current_category, selling_price, supplier 
                FROM products WHERE supplier = :supplier ORDER BY product_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':supplier' => $supplier_name]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if supplier name exists (excluding a specific id)
     * @param string $name
     * @param int $exclude_id
     * @return bool
     */
    public function exists($name, $exclude_id = 0) {
        $sql = "SELECT COUNT(*) FROM suppliers WHERE supplier_name = :name AND id != :exclude_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name, ':exclude_id' => (int)$exclude_id]);
        return $stmt->fetchColumn() > 0;
    }
}