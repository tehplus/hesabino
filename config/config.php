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

// تنظیمات سایت
define('SITE_NAME', 'حسابینو');
define('SITE_DESC', 'سیستم حسابداری آنلاین');
define('SITE_URL', 'http://www.localhost/hesabino');
define('SITE_EMAIL', 'info@hesabino.ir');

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabino');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PREFIX', 'hb_');
define('DB_CHARSET', 'utf8mb4');

// تنظیمات امنیتی
define('HASH_COST', 10); // هزینه هش کردن رمز عبور
define('CSRF_EXPIRY', 7200); // مدت زمان اعتبار توکن CSRF (2 ساعت)
define('REMEMBER_COOKIE_NAME', 'hesabino_remember');
define('REMEMBER_COOKIE_EXPIRY', 2592000); // مدت زمان کوکی "مرا به خاطر بسپار" (30 روز)
define('SESSION_LIFETIME', 7200); // مدت زمان session (2 ساعت)

// تنظیمات ایمیل
define('MAIL_FROM', SITE_EMAIL);
define('MAIL_FROM_NAME', SITE_NAME);
define('MAIL_REPLY_TO', SITE_EMAIL);

// تنظیمات آپلود
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // حداکثر حجم فایل (2MB)
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']); // فرمت‌های مجاز
define('UPLOAD_PATH', 'uploads'); // مسیر آپلود فایل‌ها

// تنظیمات جداکننده‌های اعداد
define('DECIMAL_SEPARATOR', '.'); // جداکننده اعشار
define('THOUSAND_SEPARATOR', ','); // جداکننده هزارگان

// نسخه برنامه
define('APP_VERSION', '1.0.0');