<?php
/**
 * کلاس مدیریت پایگاه داده
 * این فایل شامل کلاس اصلی اتصال به دیتابیس و متدهای مورد نیاز است
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    die('دسترسی مستقیم به این فایل مجاز نیست.');
}

class Database {
    private static $instance = null;
    private $connection = null;
    private $statement = null;
    private $inTransaction = false;
    private $queryCount = 0;
    private $queryLog = [];
    private $lastQuery = '';
    private $lastError = '';

    /**
     * سازنده کلاس - برقراری اتصال به دیتابیس
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_persian_ci"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            $this->logError('خطا در اتصال به پایگاه داده: ' . $e->getMessage());
            die('خطا در اتصال به پایگاه داده. لطفاً با مدیر سیستم تماس بگیرید.');
        }
    }

    /**
     * دریافت نمونه کلاس (الگوی Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * اجرای کوئری با پارامترهای امن
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return PDOStatement|false
     */
    public function query($query, $params = []) {
        try {
            $this->lastQuery = $query;
            $this->statement = $this->connection->prepare($query);
            
            // اجرای کوئری با پارامترها
            $success = $this->statement->execute($params);
            
            // ثبت کوئری در لاگ
            $this->queryCount++;
            $this->logQuery($query, $params);
            
            return $success ? $this->statement : false;
            
        } catch (PDOException $e) {
            $this->logError('خطا در اجرای کوئری: ' . $e->getMessage());
            throw new Exception('خطا در اجرای عملیات پایگاه داده.');
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
        $result = $this->query($query, $params);
        return $result ? $result->fetch() : false;
    }

    /**
     * دریافت تمام رکوردها
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return array
     */
    public function getRows($query, $params = []) {
        $result = $this->query($query, $params);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * دریافت یک مقدار
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return mixed
     */
    public function getValue($query, $params = []) {
        $result = $this->query($query, $params);
        return $result ? $result->fetchColumn() : null;
    }

    /**
     * درج اطلاعات در جدول
     * 
     * @param string $table نام جدول
     * @param array $data داده‌ها
     * @return int|false
     */
    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $values = array_fill(0, count($fields), '?');
            
            $query = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                     VALUES (" . implode(', ', $values) . ")";
            
            $this->query($query, array_values($data));
            return $this->connection->lastInsertId();
            
        } catch (Exception $e) {
            $this->logError('خطا در درج اطلاعات: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * بروزرسانی اطلاعات در جدول
     * 
     * @param string $table نام جدول
     * @param array $data داده‌ها
     * @param string $where شرط
     * @param array $params پارامترهای شرط
     * @return bool
     */
    public function update($table, $data, $where, $params = []) {
        try {
            $fields = array_keys($data);
            $set = implode('=?, ', $fields) . '=?';
            
            $query = "UPDATE {$table} SET {$set} WHERE {$where}";
            
            $params = array_merge(array_values($data), $params);
            return $this->query($query, $params) !== false;
            
        } catch (Exception $e) {
            $this->logError('خطا در بروزرسانی اطلاعات: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * حذف اطلاعات از جدول
     * 
     * @param string $table نام جدول
     * @param string $where شرط
     * @param array $params پارامترهای شرط
     * @return bool
     */
    public function delete($table, $where, $params = []) {
        try {
            $query = "DELETE FROM {$table} WHERE {$where}";
            return $this->query($query, $params) !== false;
            
        } catch (Exception $e) {
            $this->logError('خطا در حذف اطلاعات: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * شروع تراکنش
     */
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->connection->beginTransaction();
            $this->inTransaction = true;
        }
    }

    /**
     * تایید تراکنش
     */
    public function commit() {
        if ($this->inTransaction) {
            $this->connection->commit();
            $this->inTransaction = false;
        }
    }

    /**
     * برگشت تراکنش
     */
    public function rollback() {
        if ($this->inTransaction) {
            $this->connection->rollBack();
            $this->inTransaction = false;
        }
    }

    /**
     * تعداد رکوردهای تحت تاثیر
     */
    public function affectedRows() {
        return $this->statement ? $this->statement->rowCount() : 0;
    }

    /**
     * شمارش تعداد کوئری‌های اجرا شده
     */
    public function getQueryCount() {
        return $this->queryCount;
    }

    /**
     * دریافت آخرین خطا
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * دریافت آخرین کوئری
     */
    public function getLastQuery() {
        return $this->lastQuery;
    }

    /**
     * ثبت خطا
     */
    private function logError($message) {
        $this->lastError = $message;
        error_log(date('Y-m-d H:i:s') . " - {$message}\n", 3, BASEPATH . '/logs/db_errors.log');
    }

    /**
     * ثبت کوئری در لاگ
     */
    private function logQuery($query, $params) {
        if (Config::getInstance()->get('debug_mode')) {
            $this->queryLog[] = [
                'query' => $query,
                'params' => $params,
                'time' => microtime(true)
            ];
        }
    }

    /**
     * پاکسازی رشته برای استفاده در کوئری
     */
    public function escape($string) {
        return $this->connection->quote($string);
    }

    /**
     * بستن اتصال
     */
    public function close() {
        $this->connection = null;
        self::$instance = null;
    }

    /**
     * جلوگیری از کپی شدن
     */
    private function __clone() {}

    /**
     * جلوگیری از unserialize شدن
     */
    private function __wakeup() {}
}

// ایجاد جداول مورد نیاز در صورت عدم وجود
function createRequiredTables() {
    $db = Database::getInstance();
    
    // جدول کاربران
    $db->query("CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `email` varchar(100) NOT NULL,
        `full_name` varchar(100) NOT NULL,
        `role` enum('admin','user') NOT NULL DEFAULT 'user',
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_login` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;");

    // جدول حساب‌ها
    $db->query("CREATE TABLE IF NOT EXISTS `accounts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `type` enum('asset','liability','equity','income','expense') NOT NULL,
        `code` varchar(20) NOT NULL,
        `description` text,
        `parent_id` int(11) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`),
        KEY `parent_id` (`parent_id`),
        FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;");

    // جدول تراکنش‌ها
    $db->query("CREATE TABLE IF NOT EXISTS `transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `date` date NOT NULL,
        `reference` varchar(50) NOT NULL,
        `description` text,
        `type` enum('income','expense','transfer') NOT NULL,
        `amount` decimal(15,2) NOT NULL,
        `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
        `created_by` int(11) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `reference` (`reference`),
        KEY `created_by` (`created_by`),
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;");

    // جدول جزئیات تراکنش‌ها
    $db->query("CREATE TABLE IF NOT EXISTS `transaction_details` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `transaction_id` int(11) NOT NULL,
        `account_id` int(11) NOT NULL,
        `debit` decimal(15,2) DEFAULT '0.00',
        `credit` decimal(15,2) DEFAULT '0.00',
        `description` text,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `transaction_id` (`transaction_id`),
        KEY `account_id` (`account_id`),
        FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;");
}

// اجرای ایجاد جداول در صورت نیاز
createRequiredTables();