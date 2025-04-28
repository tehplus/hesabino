<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hesabino');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("اتصال به پایگاه داده با خطا مواجه شد: " . $e->getMessage());
}
ini_set('error_log', __DIR__ . '/../logs/debug.log');
// تنظیمات عمومی
define('SITE_NAME', 'حسابینو');
define('SITE_URL', 'http://localhost/hesabino');