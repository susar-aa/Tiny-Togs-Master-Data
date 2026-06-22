<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static $host = 'localhost';
    private static $db_name = 'product_category_val';
    private static $username = 'suzxlabs';
    private static $password = 'Susara@200611003614';
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4",
                    self::$username,
                    self::$password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $exception) {
                die("Connection error: " . $exception->getMessage());
            }
        }
        return self::$conn;
    }
}
