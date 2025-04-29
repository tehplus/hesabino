<?php
/**
 * فایل توابع عمومی سیستم حسابداری حسابینو
 * 
 * این فایل شامل تمام توابع مورد نیاز برای عملیات پایه سیستم است
 * @author Your Name
 * @package Hesabino
 */

/**
 * تابع اعتبارسنجی ورود کاربر
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * تابع پاکسازی ورودی‌های کاربر
 * 
 * @param string $data داده ورودی
 * @return string داده پاکسازی شده
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * تبدیل تاریخ میلادی به شمسی
 * 
 * @param string $date تاریخ میلادی
 * @return string تاریخ شمسی
 */
function toJalali($date) {
    // اضافه کردن کد تبدیل تاریخ
    return $date; // فعلاً برای نمونه
}

/**
 * فرمت کردن اعداد به فرمت پول
 * 
 * @param float $amount مبلغ
 * @return string مبلغ فرمت شده
 */
function formatCurrency($amount) {
    return number_format($amount, 0, '.', ',') . ' ریال';
}

/**
 * ثبت لاگ سیستم
 * 
 * @param string $message پیام
 * @param string $type نوع لاگ (error, info, warning)
 * @return void
 */
function logActivity($message, $type = 'info') {
    $log = date('Y-m-d H:i:s') . " [$type] " . $message . PHP_EOL;
    error_log($log, 3, __DIR__ . '/../logs/activity.log');
}

/**
 * اعتبارسنجی فرم‌ها
 * 
 * @param array $data داده‌های فرم
 * @param array $rules قوانین اعتبارسنجی
 * @return array خطاها
 */
function validateForm($data, $rules) {
    $errors = [];
    foreach ($rules as $field => $rule) {
        if (isset($rule['required']) && $rule['required'] && empty($data[$field])) {
            $errors[$field] = 'این فیلد الزامی است';
        }
        if (isset($rule['min']) && strlen($data[$field]) < $rule['min']) {
            $errors[$field] = "حداقل طول مجاز {$rule['min']} کاراکتر است";
        }
        // سایر قوانین اعتبارسنجی
    }
    return $errors;
}

/**
 * تولید توکن CSRF
 * 
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * بررسی توکن CSRF
 * 
 * @param string $token توکن ارسالی
 * @return bool
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ایجاد اعلان (notification)
 * 
 * @param string $message پیام
 * @param string $type نوع (success, error, warning, info)
 */
function setNotification($message, $type = 'info') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * نمایش اعلان
 * 
 * @return string|null
 */
function getNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    return null;
}
