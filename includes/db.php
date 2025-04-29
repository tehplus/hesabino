<?php
/**
 * کلاس ارتباط با پایگاه داده
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
    private $connection;
    private $transactions = 0;
    private $query_count = 0;
    private $queries = [];
    private $execution_time = 0;
    
    /**
     * سازنده کلاس
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
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // لاگ خطا
            error_log("Database Connection Error: " . $e->getMessage());
            
            // نمایش خطای مناسب
            die("خطا در اتصال به پایگاه داده. لطفاً با مدیر سیستم تماس بگیرید.");
        }
    }
    
    /**
     * جلوگیری از clone شدن
     */
    private function __clone() {}
    
    /**
     * دریافت نمونه کلاس (Singleton)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * اجرای کوئری با پارامترها
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return PDOStatement
     */
    private function execute($query, $params = []) {
        $start = microtime(true);
        
        try {
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            
            // ذخیره اطلاعات کوئری
            $this->query_count++;
            $this->queries[] = [
                'query' => $query,
                'params' => $params,
                'time' => microtime(true) - $start
            ];
            $this->execution_time += microtime(true) - $start;
            
            return $statement;
        } catch (PDOException $e) {
            // لاگ خطا
            error_log("Database Query Error: " . $e->getMessage() . "\nQuery: " . $query . "\nParams: " . print_r($params, true));
            
            // ریست تراکنش در صورت وجود خطا
            if ($this->transactions > 0) {
                $this->connection->rollBack();
                $this->transactions = 0;
            }
            
            // پرتاب خطا
            throw $e;
        }
    }
    
    /**
     * دریافت یک رکورد
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return array|false
     */
    public function getRow($query, $params = []) {
        $statement = $this->execute($query, $params);
        return $statement->fetch();
    }
    
    /**
     * دریافت چند رکورد
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return array
     */
    public function getRows($query, $params = []) {
        $statement = $this->execute($query, $params);
        return $statement->fetchAll();
    }
    
    /**
     * دریافت یک مقدار
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return mixed
     */
    public function getValue($query, $params = []) {
        $statement = $this->execute($query, $params);
        return $statement->fetchColumn();
    }
    
    /**
     * دریافت یک ستون
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return array
     */
    public function getColumn($query, $params = []) {
        $statement = $this->execute($query, $params);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * درج رکورد جدید
     * 
     * @param string $table نام جدول
     * @param array $data داده‌ها
     * @return int|false
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $query = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        $statement = $this->execute($query, $values);
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * بروزرسانی رکورد
     * 
     * @param string $table نام جدول
     * @param array $data داده‌ها
     * @param string $where شرط
     * @param array $params پارامترهای شرط
     * @return bool
     */
    public function update($table, $data, $where, $params = []) {
        $fields = array_keys($data);
        $values = array_values($data);
        
        $set = implode('=?,', $fields) . '=?';
        $query = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $statement = $this->execute($query, array_merge($values, $params));
        return $statement->rowCount() > 0;
    }
    
    /**
     * حذف رکورد
     * 
     * @param string $table نام جدول
     * @param string $where شرط
     * @param array $params پارامترهای شرط
     * @return bool
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $statement = $this->execute($query, $params);
        return $statement->rowCount() > 0;
    }
    
    /**
     * شروع تراکنش
     */
    public function beginTransaction() {
        if ($this->transactions == 0) {
            $this->connection->beginTransaction();
        }
        $this->transactions++;
    }
    
    /**
     * تأیید تراکنش
     */
    public function commit() {
        $this->transactions--;
        if ($this->transactions == 0) {
            $this->connection->commit();
        }
    }
    
    /**
     * بازگشت تراکنش
     */
    public function rollBack() {
        if ($this->transactions > 0) {
            $this->connection->rollBack();
            $this->transactions = 0;
        }
    }
    
    /**
     * دریافت تعداد کوئری‌ها
     * 
     * @return int
     */
    public function getQueryCount() {
        return $this->query_count;
    }
    
    /**
     * دریافت لیست کوئری‌ها
     * 
     * @return array
     */
    public function getQueries() {
        return $this->queries;
    }
    
    /**
     * دریافت زمان اجرای کوئری‌ها
     * 
     * @return float
     */
    public function getExecutionTime() {
        return $this->execution_time;
    }
    
    /**
     * فرار از کاراکترهای خاص
     * 
     * @param string $value مقدار ورودی
     * @return string
     */
    public function escape($value) {
        return $this->connection->quote($value);
    }
    
    /**
     * اجرای مستقیم کوئری
     * 
     * @param string $query کوئری SQL
     * @return bool|PDOStatement
     */
    public function query($query) {
        return $this->connection->query($query);
    }
}