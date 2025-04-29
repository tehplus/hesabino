<?php
/**
 * پردازش فرم تماس
 * 
 * این فایل مسئول دریافت، اعتبارسنجی و پردازش پیام‌های فرم تماس است
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم به این فایل
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__FILE__)));
}

// لود کردن فایل‌های مورد نیاز
require_once BASEPATH . '/config/config.php';
require_once BASEPATH . '/includes/db.php';
require_once BASEPATH . '/includes/functions.php';

// تنظیم هدر پاسخ به JSON
header('Content-Type: application/json; charset=utf-8');

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, 'درخواست نامعتبر است.');
}

// دریافت و پاکسازی داده‌های ورودی
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// اعتبارسنجی داده‌ها
$errors = [];

if (empty($name) || strlen($name) < 3) {
    $errors[] = 'نام و نام خانوادگی باید حداقل 3 کاراکتر باشد.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'لطفاً یک ایمیل معتبر وارد کنید.';
}

if (empty($subject) || strlen($subject) < 5) {
    $errors[] = 'موضوع پیام باید حداقل 5 کاراکتر باشد.';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'متن پیام باید حداقل 10 کاراکتر باشد.';
}

// بررسی وجود خطا
if (!empty($errors)) {
    send_response(false, 'لطفاً خطاهای زیر را برطرف کنید:', $errors);
}

try {
    // ایجاد جدول پیام‌ها در صورت عدم وجود
    $db = Database::getInstance();
    $db->query("CREATE TABLE IF NOT EXISTS `contact_messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `subject` varchar(200) NOT NULL,
        `message` text NOT NULL,
        `ip_address` varchar(45) NOT NULL,
        `user_agent` varchar(255) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status` enum('new','read','replied') NOT NULL DEFAULT 'new',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;");

    // ذخیره پیام در دیتابیس
    $messageData = [
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    $messageId = $db->insert('contact_messages', $messageData);

    if (!$messageId) {
        throw new Exception('خطا در ذخیره پیام');
    }

    // ارسال ایمیل به مدیر سایت
    $adminEmailBody = <<<EOT
    <html dir="rtl">
    <head>
        <style>
            body { font-family: Tahoma, Arial; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; text-align: center; }
            .content { background: #f8f9fa; padding: 20px; margin-top: 20px; }
            .footer { text-align: center; margin-top: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>پیام جدید از فرم تماس</h2>
            </div>
            <div class="content">
                <p><strong>نام و نام خانوادگی:</strong> {$name}</p>
                <p><strong>ایمیل:</strong> {$email}</p>
                <p><strong>موضوع:</strong> {$subject}</p>
                <p><strong>پیام:</strong></p>
                <p>{$message}</p>
                <hr>
                <p><small>IP: {$_SERVER['REMOTE_ADDR']}</small></p>
                <p><small>تاریخ: {$date}</small></p>
            </div>
            <div class="footer">
                <p>این ایمیل به صورت خودکار ارسال شده است.</p>
            </div>
        </div>
    </body>
    </html>
    EOT;

    // تنظیمات ایمیل
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>',
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . phpversion()
    ];

    // ارسال ایمیل به مدیر
    $mailSent = mail(
        ADMIN_EMAIL,
        'پیام جدید از ' . SITE_NAME . ': ' . $subject,
        $adminEmailBody,
        implode("\r\n", $headers)
    );

    if (!$mailSent) {
        error_log('خطا در ارسال ایمیل - پیام شماره: ' . $messageId);
    }

    // ارسال ایمیل تأییدیه به کاربر
    $userEmailBody = <<<EOT
    <html dir="rtl">
    <head>
        <style>
            body { font-family: Tahoma, Arial; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; text-align: center; }
            .content { background: #f8f9fa; padding: 20px; margin-top: 20px; }
            .footer { text-align: center; margin-top: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>تأییدیه دریافت پیام</h2>
            </div>
            <div class="content">
                <p>با سلام {$name} عزیز،</p>
                <p>پیام شما با موضوع "{$subject}" با موفقیت دریافت شد.</p>
                <p>در اسرع وقت به پیام شما پاسخ خواهیم داد.</p>
                <hr>
                <p>با تشکر،<br>{$siteName}</p>
            </div>
            <div class="footer">
                <p>این ایمیل به صورت خودکار ارسال شده است.</p>
            </div>
        </div>
    </body>
    </html>
    EOT;

    // ارسال ایمیل به کاربر
    mail(
        $email,
        'تأییدیه دریافت پیام - ' . SITE_NAME,
        $userEmailBody,
        implode("\r\n", $headers)
    );

    // ارسال پاسخ موفقیت
    send_response(true, 'پیام شما با موفقیت ارسال شد. به زودی با شما تماس خواهیم گرفت.');

} catch (Exception $e) {
    // ثبت خطا در لاگ
    error_log($e->getMessage());
    
    // ارسال پاسخ خطا
    send_response(false, 'متأسفانه در ارسال پیام مشکلی پیش آمده. لطفاً مجدداً تلاش کنید.');
}

/**
 * ارسال پاسخ JSON
 * 
 * @param bool $success وضعیت موفقیت
 * @param string $message پیام
 * @param array $data داده‌های اضافی
 */
function send_response($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}