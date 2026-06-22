<?php
namespace Models;

use Config\Database;
use PDO;

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get all categories
     * @return array
     */
    public function getAll() {
        $sql = "SELECT c.id, c.category_name, c.main_category, c.including_items, c.is_auto_created, c.created_at,
                       (SELECT COUNT(*) FROM products p WHERE p.current_category = c.category_name) AS product_count
                FROM categories c
                ORDER BY c.main_category ASC, c.category_name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get category count
     * @return int
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as cnt FROM categories";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Save category and split its including items into keywords
     * @param string $category_name
     * @param string $including_items
     * @return int Category ID
     */
    public function importCategory($category_name, $including_items, $main_category = null, $is_auto_created = 0) {
        $category_name = trim($category_name);
        $including_items = trim($including_items);
        $main_category = $main_category ? trim($main_category) : null;

        if (empty($category_name)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Insert or update category
            $sql = "INSERT INTO categories (category_name, main_category, including_items, is_auto_created) 
                    VALUES (:name, :main_cat, :items, :is_auto) 
                    ON DUPLICATE KEY UPDATE including_items = :items2, main_category = COALESCE(:main_cat2, main_category)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $category_name,
                ':main_cat' => $main_category,
                ':items' => $including_items,
                ':is_auto' => $is_auto_created,
                ':items2' => $including_items,
                ':main_cat2' => $main_category
            ]);

            // Get Category ID
            $sql = "SELECT id FROM categories WHERE category_name = :name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':name' => $category_name]);
            $category_id = $stmt->fetchColumn();

            if (!$category_id) {
                $this->db->rollBack();
                return false;
            }

            // Clear old keywords for this category
            $sql = "DELETE FROM category_keywords WHERE category_id = :category_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':category_id' => $category_id]);

            // Insert new keywords
            if (!empty($including_items)) {
                // Split keywords by comma
                $keywords = array_map('trim', explode(',', $including_items));
                
                $insert_sql = "INSERT INTO category_keywords (category_id, keyword) VALUES (:category_id, :keyword)";
                $insert_stmt = $this->db->prepare($insert_sql);

                foreach ($keywords as $keyword) {
                    if (empty($keyword)) continue;
                    $insert_stmt->execute([
                        ':category_id' => $category_id,
                        ':keyword' => $keyword
                    ]);
                }
            }

            $this->db->commit();
            return $category_id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get or create category by name, seeding it with its name as default keyword.
     * @param string $category_name
     * @return int|bool Category ID or false on failure
     */
    public function getOrCreate($category_name) {
        $category_name = trim($category_name);
        if (empty($category_name)) {
            return false;
        }

        // Check if exists
        $sql = "SELECT id FROM categories WHERE category_name = :name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $category_name]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return $id;
        }

        // Create category automatically
        return $this->importCategory($category_name, strtolower($category_name), null, 1);
    }

    /**
     * Get distribution of products per category
     * @return array
     */
    public function getCategoryDistribution() {
        // Count products grouping by their current category
        $sql = "SELECT current_category as category_name, COUNT(*) as product_count 
                FROM products 
                GROUP BY current_category 
                ORDER BY product_count DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Update category details and regenerate keywords
     * @param int $id
     * @param string $category_name
     * @param string $including_items
     * @return bool
     */
    public function updateCategory($id, $category_name, $including_items, $main_category = null) {
        $category_name = trim($category_name);
        $including_items = trim($including_items);
        $main_category = $main_category ? trim($main_category) : null;

        if (empty($category_name) || !$id) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Update category name & items
            $sql = "UPDATE categories SET category_name = :name, main_category = :main_cat, including_items = :items WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $category_name,
                ':main_cat' => $main_category,
                ':items' => $including_items,
                ':id' => $id
            ]);

            // Clear old keywords
            $sql = "DELETE FROM category_keywords WHERE category_id = :category_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':category_id' => $id]);

            // Insert new keywords
            if (!empty($including_items)) {
                $keywords = array_map('trim', explode(',', $including_items));
                $insert_sql = "INSERT INTO category_keywords (category_id, keyword) VALUES (:category_id, :keyword)";
                $insert_stmt = $this->db->prepare($insert_sql);

                foreach ($keywords as $keyword) {
                    if (empty($keyword)) continue;
                    $insert_stmt->execute([
                        ':category_id' => $id,
                        ':keyword' => $keyword
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete category and associated keywords
     * @param int $id
     * @return bool
     */
    public function deleteCategory($id) {
        if (!$id) return false;
        try {
            $this->db->beginTransaction();

            // Delete keywords
            $sql = "DELETE FROM category_keywords WHERE category_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            // Delete category
            $sql = "DELETE FROM categories WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Bulk update main category for multiple categories
     * @param array $ids
     * @param string $main_category
     * @return bool
     */
    public function bulkUpdateMainCategory($ids, $main_category) {
        if (empty($ids) || !is_array($ids)) return false;
        $main_category = !empty(trim($main_category)) ? trim($main_category) : null;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE categories SET main_category = ? WHERE id IN ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        
        $params = array_merge([$main_category], $ids);
        return $stmt->execute($params);
    }
}
