<?php
namespace Models;

use Config\Database;
use PDO;

class Setting {
    private $db;
    private static $cache = [];

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get a setting value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $sql = "SELECT setting_value FROM settings WHERE setting_key = :key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();

        if ($row) {
            self::$cache[$key] = $row['setting_value'];
            return $row['setting_value'];
        }

        return $default;
    }

    /**
     * Set a setting value
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function set($key, $value) {
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE setting_value = :value2";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':value2' => $value
        ]);

        if ($result) {
            self::$cache[$key] = $value;
            // Log setting change
            $log = new Log();
            $log->record('Update Settings', "Setting '{$key}' changed to '{$value}'");
        }

        return $result;
    }

    /**
     * Get all settings as associative array
     * @return array
     */
    public function getAll() {
        $sql = "SELECT setting_key, setting_value FROM settings";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
