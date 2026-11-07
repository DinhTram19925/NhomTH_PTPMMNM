<?php
class Database {
    private static $instance = null;
    private $conn;

    private $host = 'localhost';
    private $db_name = 'royaldine_5s';
    private $username = 'root';
    private $password = '';
    private static $overrideDbName = null;

    private function __construct() {
        $targetDb = self::$overrideDbName
            ?: (defined('ROYALDINE_DB_NAME') ? ROYALDINE_DB_NAME : (getenv('ROYALDINE_DB_NAME') ?: $this->db_name));
        $targetHost = getenv('ROYALDINE_DB_HOST') ?: $this->host;
        $targetUser = getenv('ROYALDINE_DB_USER') ?: $this->username;
        $targetPass = getenv('ROYALDINE_DB_PASS') !== false ? getenv('ROYALDINE_DB_PASS') : $this->password;

        try {
            $dsn = "mysql:host={$targetHost};dbname={$targetDb};charset=utf8mb4";
            $this->conn = new PDO($dsn, $targetUser, $targetPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("[DB Connection Error] " . $e->getMessage());
            die("Lỗi kết nối CSDL RoyalDine 5S.");
        }
    }

    public static function setDatabaseName(string $dbName): void {
        self::$overrideDbName = $dbName;
        self::$instance = null;
    }

    public static function getDatabaseName(): string {
        return self::$overrideDbName
            ?: (defined('ROYALDINE_DB_NAME') ? ROYALDINE_DB_NAME : (getenv('ROYALDINE_DB_NAME') ?: 'royaldine_5s'));
    }

    public static function resetConnection(): void {
        self::$instance = null;
    }

    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}