<?php
/**
 * پیکربندی اصلی برنامه حسابداری
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم به این فایل
if (!defined('BASEPATH')) {
    die('دسترسی مستقیم به این فایل مجاز نیست.');
}

// تنظیمات منطقه زمانی و زبان
date_default_timezone_set('Asia/Tehran');
setlocale(LC_ALL, 'fa_IR.utf8');

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabino');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// مسیرهای اصلی
define('BASEPATH', dirname(dirname(__FILE__)));
define('URL', 'http://localhost/hesabino/'); // تغییر به آدرس اصلی سایت
define('ASSETS', URL . 'assets/');

// تنظیمات امنیتی
define('SECURE_SESSION', true);
define('SESSION_NAME', 'HESABINO_SESSION');
define('CSRF_TOKEN_NAME', 'hesabino_csrf_token');

// تنظیمات عمومی
define('SITE_NAME', 'حسابینو');
define('SITE_DESC', 'سیستم حسابداری آنلاین');
define('ADMIN_EMAIL', 'admin@example.com');

// تنظیمات حسابداری
define('DEFAULT_CURRENCY', 'IRR');
define('CURRENCY_SYMBOL', 'ریال');
define('TAX_RATE', 0.09); // 9% مالیات بر ارزش افزوده
define('INVOICE_PREFIX', 'INV-');
define('TRANSACTION_PREFIX', 'TRN-');

// تنظیمات نمایشی
define('ITEMS_PER_PAGE', 20);
define('DATE_FORMAT', 'Y/m/d');
define('TIME_FORMAT', 'H:i:s');

// کلاس تنظیمات
class Config {
    private static $instance = null;
    private $settings = [];

    private function __construct() {
        // تنظیمات پیش‌فرض
        $this->settings = [
            'debug_mode' => true,
            'maintenance_mode' => false,
            'allow_registration' => true,
            'email_verification' => true,
            'auto_backup' => true,
            'backup_frequency' => 'daily',
            'log_level' => 'info',
            'max_login_attempts' => 5,
            'lockout_time' => 15, // دقیقه
            'session_lifetime' => 3600, // ثانیه
            'password_min_length' => 8,
            'file_upload_max_size' => 5242880, // 5MB
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        ];
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    public function set($key, $value) {
        $this->settings[$key] = $value;
    }

    public function getAll() {
        return $this->settings;
    }
}

// تنظیمات اولیه برنامه
function initializeApp() {
    // تنظیمات سشن
    if (SECURE_SESSION) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);
    }
    
    session_name(SESSION_NAME);
    session_start();

    // تنظیمات خطایابی
    if (Config::getInstance()->get('debug_mode')) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    // تنظیم هدرهای امنیتی
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // بررسی حالت تعمیر
    if (Config::getInstance()->get('maintenance_mode') && 
        !isset($_SESSION['admin_logged_in'])) {
        die('سایت در حال بروزرسانی است. لطفاً بعداً مراجعه کنید.');
    }
}

// اجرای تنظیمات اولیه
initializeApp();