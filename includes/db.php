<?php
/**
 * کلاس مدیریت پایگاه داده
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Database {
    private static $instance = null;
    private $conn = null;
    private $stmt = null;
    private $transactions = 0;
    
    /**
     * سازنده کلاس و ایجاد اتصال به دیتابیس
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_persian_ci"
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // لاگ خطا
            error_log($e->getMessage());
            throw new Exception('خطا در اتصال به پایگاه داده');
        }
    }
    
    /**
     * دریافت نمونه کلاس (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * اجرای کوئری
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        try {
            $this->stmt = $this->conn->prepare($query);
            $this->stmt->execute($params);
            return $this->stmt;
            
        } catch (PDOException $e) {
            // لاگ خطا
            error_log($e->getMessage());
            throw new Exception('خطا در اجرای کوئری');
        }
    }
    
    /**
     * دریافت یک ردیف
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return array|false
     */
    public function getRow($query, $params = []) {
        return $this->query($query, $params)->fetch();
    }
    
    /**
     * دریافت همه ردیف‌ها
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return array
     */
    public function getRows($query, $params = []) {
        return $this->query($query, $params)->fetchAll();
    }
    
    /**
     * دریافت یک مقدار
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return mixed
     */
    public function getValue($query, $params = []) {
        return $this->query($query, $params)->fetchColumn();
    }
    
    /**
     * درج رکورد جدید
     * 
     * @param string $table نام جدول
     * @param array $data داده‌ها
     * @return int
     */
    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $query = "INSERT INTO " . DB_PREFIX . $table . " 
                     (" . implode(', ', $fields) . ") 
                     VALUES (" . implode(', ', $placeholders) . ")";
            
            $this->query($query, $values);
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            // لاگ خطا
            error_log($e->getMessage());
            throw new Exception('خطا در درج اطلاعات');
        }
    }
    
    /**
     * بروزرسانی رکورد
     * 
     * @param string $table نام جدول
     * @param array $data داده‌ها
     * @param string $where شرط
     * @param array $params پارامترهای شرط
     * @return int
     */
    public function update($table, $data, $where, $params = []) {
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            $set = array_map(function($field) {
                return "$field = ?";
            }, $fields);
            
            $query = "UPDATE " . DB_PREFIX . $table . " 
                     SET " . implode(', ', $set) . " 
                     WHERE " . $where;
            
            $this->query($query, array_merge($values, $params));
            return $this->stmt->rowCount();
            
        } catch (PDOException $e) {
            // لاگ خطا
            error_log($e->getMessage());
            throw new Exception('خطا در بروزرسانی اطلاعات');
        }
    }
    
    /**
     * حذف رکورد
     * 
     * @param string $table نام جدول
     * @param string $where شرط
     * @param array $params پارامترهای شرط
     * @return int
     */
    public function delete($table, $where, $params = []) {
        try {
            $query = "DELETE FROM " . DB_PREFIX . $table . " WHERE " . $where;
            $this->query($query, $params);
            return $this->stmt->rowCount();
            
        } catch (PDOException $e) {
            // لاگ خطا
            error_log($e->getMessage());
            throw new Exception('خطا در حذف اطلاعات');
        }
    }
    
    /**
     * شروع تراکنش
     */
    public function beginTransaction() {
        if ($this->transactions === 0) {
            $this->conn->beginTransaction();
        }
        $this->transactions++;
    }
    
    /**
     * تایید تراکنش
     */
    public function commit() {
        $this->transactions--;
        if ($this->transactions === 0) {
            $this->conn->commit();
        }
    }
    
    /**
     * بازگشت تراکنش
     */
    public function rollback() {
        if ($this->transactions > 0) {
            $this->transactions = 0;
            $this->conn->rollback();
        }
    }
    
    /**
     * دریافت آخرین ID درج شده
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * دریافت تعداد رکوردهای تغییر یافته
     * 
     * @return int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * دریافت اتصال PDO
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * بستن اتصال
     */
    public function __destruct() {
        $this->stmt = null;
        $this->conn = null;
    }
    
    /**
     * جلوگیری از کپی شدن
     */
    private function __clone() {}
    
    /**
     * جلوگیری از unserialize شدن
     */
    public function __wakeup() {} // تغییر از private به public
}