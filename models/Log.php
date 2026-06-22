<?php
namespace Models;

use Config\Database;
use PDO;

class Log {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Record a system action
     * @param string $action Action name
     * @param string $details Detailed message
     * @return bool
     */
    public function record($action, $details = null) {
        $sql = "INSERT INTO logs (action, details) VALUES (:action, :details)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':action' => $action,
            ':details' => $details
        ]);
    }

    /**
     * Fetch all logs sorted by creation time
     * @param int $limit
     * @return array
     */
    public function getLatestLogs($limit = 100) {
        $sql = "SELECT id, action, details, created_at FROM logs ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Clear all logs
     * @return bool
     */
    public function clearLogs() {
        $sql = "TRUNCATE TABLE logs";
        return $this->db->query($sql) !== false;
    }
}
