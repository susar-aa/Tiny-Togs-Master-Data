<?php
namespace Models;

use Config\Database;
use PDO;

class MainCategory {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS main_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                main_category_name VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Fix collation on existing table if needed
        try {
            $this->db->exec("ALTER TABLE main_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\PDOException $e) {
            // Ignore if alter fails
        }
    }

    /**
     * Get all main categories
     * @return array
     */
    public function getAll() {
        // Fetch main categories and also count how many sub-categories belong to each
        $sql = "SELECT m.id, m.main_category_name AS name, m.created_at,
                       (SELECT COUNT(*) FROM categories c WHERE c.main_category COLLATE utf8mb4_unicode_ci = m.main_category_name) AS sub_category_count
                FROM main_categories m
                ORDER BY m.main_category_name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of main categories
     * @return int
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as cnt FROM main_categories";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Save/Create a main category
     * @param string $name
     * @return bool|int
     */
    public function save($name) {
        $name = trim($name);
        if (empty($name)) return false;

        $sql = "INSERT INTO main_categories (main_category_name) VALUES (:name) ON DUPLICATE KEY UPDATE main_category_name = :name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name]);
        return $this->db->lastInsertId() ?: true;
    }

    /**
     * Update main category name
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
            $stmt = $this->db->prepare("SELECT main_category_name FROM main_categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $old_name = $stmt->fetchColumn();

            // Update main category
            $stmt = $this->db->prepare("UPDATE main_categories SET main_category_name = :new_name WHERE id = :id");
            $stmt->execute([
                ':new_name' => $new_name,
                ':id' => $id
            ]);

            // Update sub-categories dependency
            if ($old_name) {
                $stmt = $this->db->prepare("UPDATE categories SET main_category = :new_name WHERE main_category = :old_name");
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
     * Delete main category
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $id = (int)$id;
        if (!$id) return false;

        try {
            $this->db->beginTransaction();

            // Fetch name to clear references
            $stmt = $this->db->prepare("SELECT main_category_name FROM main_categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $name = $stmt->fetchColumn();

            // Delete main category
            $stmt = $this->db->prepare("DELETE FROM main_categories WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Set main_category to NULL for sub-categories referencing this main category
            if ($name) {
                $stmt = $this->db->prepare("UPDATE categories SET main_category = NULL WHERE main_category = :name");
                $stmt->execute([':name' => $name]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
