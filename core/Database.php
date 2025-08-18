<?php
/**
 * کلاس مدیریت پایگاه داده
 */
class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // اگر رمز عبور خالی باشد، نباید پاس شود
            if (empty(DB_PASS)) {
                $this->connection = new PDO($dsn, DB_USER, null, $options);
            } else {
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('اتصال به پایگاه داده برقرار نشد');
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            logError('Database query failed: ' . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            return false;
        }
    }
    
    public function fetch($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError('Database fetch failed: ' . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            return false;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Database fetchAll failed: ' . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            return [];
        }
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function testConnection() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>