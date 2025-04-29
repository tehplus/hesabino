<?php
/**
 * تنظیمات اصلی برنامه
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// تنظیم منطقه زمانی
date_default_timezone_set('Asia/Tehran');

// نمایش خطاها در محیط توسعه
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === 'www.localhost') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// تنظیمات پایه سایت
define('SITE_NAME', 'حسابینو');
define('SITE_DESC', 'سیستم حسابداری آنلاین');
define('SITE_URL', 'http://www.localhost/hesabino/');
define('ADMIN_EMAIL', 'admin@hesabino.com');

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabino');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', 'hb_');

// تنظیمات امنیتی
define('HASH_COST', 12); // هزینه هش کردن رمز عبور
define('SESSION_LIFETIME', 7200); // طول عمر session به ثانیه (2 ساعت)
define('CSRF_EXPIRY', 7200); // طول عمر توکن CSRF به ثانیه (2 ساعت)
define('REMEMBER_COOKIE_NAME', 'hesabino_remember');
define('REMEMBER_COOKIE_EXPIRY', 2592000); // 30 روز به ثانیه

// تنظیمات آپلود
define('UPLOAD_PATH', BASEPATH . '/uploads/');
define('ALLOWED_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// تنظیمات نمایش
define('ITEMS_PER_PAGE', 20);
define('DATE_FORMAT', 'Y/m/d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y/m/d H:i:s');
define('THOUSAND_SEPARATOR', ',');
define('DECIMAL_SEPARATOR', '.');

// تنظیمات ایمیل
define('MAIL_FROM', 'noreply@hesabino.com');
define('MAIL_FROM_NAME', SITE_NAME);
define('MAIL_REPLY_TO', 'support@hesabino.com');

// پایان تنظیمات
return true;