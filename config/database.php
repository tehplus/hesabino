<?php
// تنظیمات اتصال به دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabino');
define('DB_USER', 'root'); // نام کاربری دیتابیس خود را اینجا قرار دهید
define('DB_PASS', ''); // رمز عبور دیتابیس خود را اینجا قرار دهید
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_persian_ci"
        ]
    );
} catch (PDOException $e) {
    // در محیط توسعه می‌توانید پیام خطا را نمایش دهید
    die("خطا در اتصال به پایگاه داده: " . $e->getMessage());
}

// تابع ساده برای اجرای کوئری‌ها
function dbQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // در محیط توسعه می‌توانید پیام خطا را نمایش دهید
        die("خطا در اجرای کوئری: " . $e->getMessage());
    }
}

// تابع برای گرفتن آخرین شناسه درج شده
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}