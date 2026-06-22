<?php
namespace Models;

use Config\Database;
use PDO;

class Keyword {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get all keywords with their categories
     * @return array
     */
    public function getAllWithCategories() {
        $sql = "SELECT k.id, k.keyword, k.category_id, c.category_name 
                FROM category_keywords k 
                JOIN categories c ON k.category_id = c.id
                ORDER BY LENGTH(k.keyword) DESC, k.keyword ASC"; // Order by length descending so longer keywords (like multi-word) match first
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get total keyword count
     * @return int
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as cnt FROM category_keywords";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }
}
