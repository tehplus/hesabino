 <?php
/**
 * کلاس مدیریت احراز هویت و کاربران
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__FILE__)));
}

require_once BASEPATH . '/config/config.php';
require_once BASEPATH . '/includes/db.php';

class Auth {
    private static $instance = null;
    private $db = null;
    private $user = null;
    private $errors = [];
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->initSession();
        $this->checkRememberMe();
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
     * راه‌اندازی سشن
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * بررسی "مرا به خاطر بسپار"
     */
    private function checkRememberMe() {
        if (!$this->isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $user = $this->db->getRow(
                "SELECT * FROM users WHERE remember_token = ? AND status = 'active'",
                [$token]
            );
            
            if ($user) {
                $this->loginUser($user);
            }
        }
    }

    /**
     * ثبت‌نام کاربر جدید
     */
    public function register($data) {
        // اعتبارسنجی داده‌ها
        $this->validateRegistration($data);
        
        if (!empty($this->errors)) {
            return false;
        }

        try {
            // هش کردن رمز عبور
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // ایجاد کد تأیید ایمیل
            $verificationCode = bin2hex(random_bytes(32));
            
            // درج کاربر جدید
            $userId = $this->db->insert('users', [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'full_name' => $data['full_name'],
                'role' => 'user',
                'status' => 'inactive',
                'verification_code' => $verificationCode,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($userId) {
                // ارسال ایمیل تأیید
                $this->sendVerificationEmail($data['email'], $verificationCode);
                return true;
            }

            $this->errors[] = 'خطا در ثبت اطلاعات کاربر';
            return false;

        } catch (Exception $e) {
            $this->errors[] = 'خطا در ثبت‌نام. لطفاً مجدداً تلاش کنید.';
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * اعتبارسنجی داده‌های ثبت‌نام
     */
    private function validateRegistration($data) {
        $this->errors = [];

        // بررسی نام کاربری
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $this->errors[] = 'نام کاربری باید حداقل 3 کاراکتر باشد.';
        } else {
            $exists = $this->db->getValue(
                "SELECT COUNT(*) FROM users WHERE username = ?",
                [$data['username']]
            );
            if ($exists) {
                $this->errors[] = 'این نام کاربری قبلاً ثبت شده است.';
            }
        }

        // بررسی ایمیل
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'لطفاً یک ایمیل معتبر وارد کنید.';
        } else {
            $exists = $this->db->getValue(
                "SELECT COUNT(*) FROM users WHERE email = ?",
                [$data['email']]
            );
            if ($exists) {
                $this->errors[] = 'این ایمیل قبلاً ثبت شده است.';
            }
        }

        // بررسی رمز عبور
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $this->errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
        }

        // بررسی تأیید رمز عبور
        if ($data['password'] !== $data['confirm_password']) {
            $this->errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
        }

        // بررسی نام و نام خانوادگی
        if (empty($data['full_name']) || strlen($data['full_name']) < 3) {
            $this->errors[] = 'نام و نام خانوادگی باید حداقل 3 کاراکتر باشد.';
        }
    }

    /**
     * ورود کاربر
     */
    public function login($username, $password, $remember = false) {
        try {
            // بررسی نام کاربری یا ایمیل
            $user = $this->db->getRow(
                "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'",
                [$username, $username]
            );

            if (!$user || !password_verify($password, $user['password'])) {
                $this->errors[] = 'نام کاربری یا رمز عبور اشتباه است.';
                return false;
            }

            // ورود کاربر
            $this->loginUser($user);

            // تنظیم "مرا به خاطر بسپار"
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 روز
                
                $this->db->update('users',
                    ['remember_token' => $token],
                    'id = ?',
                    [$user['id']]
                );
            }

            return true;

        } catch (Exception $e) {
            $this->errors[] = 'خطا در ورود به سیستم. لطفاً مجدداً تلاش کنید.';
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * ورود کاربر به سیستم
     */
    private function loginUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        // بروزرسانی آخرین ورود
        $this->db->update('users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
    }

    /**
     * خروج کاربر
     */
    public function logout() {
        // حذف توکن "مرا به خاطر بسپار"
        if (isset($_COOKIE['remember_token'])) {
            $this->db->update('users',
                ['remember_token' => null],
                'id = ?',
                [$_SESSION['user_id']]
            );
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // حذف سشن
        session_unset();
        session_destroy();
    }

    /**
     * تأیید ایمیل کاربر
     */
    public function verifyEmail($code) {
        try {
            $user = $this->db->getRow(
                "SELECT * FROM users WHERE verification_code = ? AND status = 'inactive'",
                [$code]
            );

            if (!$user) {
                $this->errors[] = 'کد تأیید نامعتبر است.';
                return false;
            }

            // فعال‌سازی کاربر
            $this->db->update('users',
                [
                    'status' => 'active',
                    'verification_code' => null,
                    'email_verified_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$user['id']]
            );

            return true;

        } catch (Exception $e) {
            $this->errors[] = 'خطا در تأیید ایمیل. لطفاً مجدداً تلاش کنید.';
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * بازیابی رمز عبور
     */
    public function resetPassword($email) {
        try {
            $user = $this->db->getRow(
                "SELECT * FROM users WHERE email = ? AND status = 'active'",
                [$email]
            );

            if (!$user) {
                $this->errors[] = 'ایمیل وارد شده در سیستم ثبت نشده است.';
                return false;
            }

            // ایجاد توکن بازیابی
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->db->update('users',
                [
                    'reset_token' => $token,
                    'reset_token_expiry' => $expiry
                ],
                'id = ?',
                [$user['id']]
            );

            // ارسال ایمیل بازیابی
            $this->sendPasswordResetEmail($email, $token);

            return true;

        } catch (Exception $e) {
            $this->errors[] = 'خطا در بازیابی رمز عبور. لطفاً مجدداً تلاش کنید.';
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * تغییر رمز عبور
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->db->getRow(
                "SELECT * FROM users WHERE id = ?",
                [$userId]
            );

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                $this->errors[] = 'رمز عبور فعلی اشتباه است.';
                return false;
            }

            // بروزرسانی رمز عبور
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('users',
                ['password' => $hashedPassword],
                'id = ?',
                [$userId]
            );

            return true;

        } catch (Exception $e) {
            $this->errors[] = 'خطا در تغییر رمز عبور. لطفاً مجدداً تلاش کنید.';
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * ارسال ایمیل تأیید
     */
    private function sendVerificationEmail($email, $code) {
        $verifyLink = SITE_URL . 'verify-email.php?code=' . $code;
        
        $subject = 'تأیید حساب کاربری - ' . SITE_NAME;
        
        $message = <<<EOT
        <html dir="rtl">
        <head>
            <style>
                body { font-family: Tahoma, Arial; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #667eea; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; margin-top: 20px; }
                .button { display: inline-block; padding: 10px 20px; background: #667eea; color: white; 
                          text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>تأیید حساب کاربری</h2>
                </div>
                <div class="content">
                    <p>با تشکر از ثبت‌نام شما در {$siteName}</p>
                    <p>برای تکمیل ثبت‌نام و فعال‌سازی حساب کاربری خود، لطفاً روی دکمه زیر کلیک کنید:</p>
                    <p style="text-align: center;">
                        <a href="{$verifyLink}" class="button">تأیید حساب کاربری</a>
                    </p>
                    <p>یا می‌توانید لینک زیر را در مرورگر خود کپی کنید:</p>
                    <p>{$verifyLink}</p>
                </div>
                <div class="footer">
                    <p>این ایمیل به صورت خودکار ارسال شده است. لطفاً به آن پاسخ ندهید.</p>
                </div>
            </div>
        </body>
        </html>
        EOT;

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>',
            'X-Mailer: PHP/' . phpversion()
        ];

        mail($email, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * ارسال ایمیل بازیابی رمز عبور
     */
    private function sendPasswordResetEmail($email, $token) {
        $resetLink = SITE_URL . 'reset-password.php?token=' . $token;
        
        $subject = 'بازیابی رمز عبور - ' . SITE_NAME;
        
        $message = <<<EOT
        <html dir="rtl">
        <head>
            <style>
                body { font-family: Tahoma, Arial; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #667eea; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; margin-top: 20px; }
                .button { display: inline-block; padding: 10px 20px; background: #667eea; color: white; 
                          text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>بازیابی رمز عبور</h2>
                </div>
                <div class="content">
                    <p>شما درخواست بازیابی رمز عبور کرده‌اید.</p>
                    <p>برای تنظیم رمز عبور جدید، لطفاً روی دکمه زیر کلیک کنید:</p>
                    <p style="text-align: center;">
                        <a href="{$resetLink}" class="button">بازیابی رمز عبور</a>
                    </p>
                    <p>یا می‌توانید لینک زیر را در مرورگر خود کپی کنید:</p>
                    <p>{$resetLink}</p>
                    <p>این لینک تا یک ساعت معتبر است.</p>
                </div>
                <div class="footer">
                    <p>اگر شما این درخواست را نکرده‌اید، می‌توانید این ایمیل را نادیده بگیرید.</p>
                </div>
            </div>
        </body>
        </html>
        EOT;

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>',
            'X-Mailer: PHP/' . phpversion()
        ];

        mail($email, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * بررسی وضعیت ورود کاربر
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * دریافت کاربر فعلی
     */
    public function getCurrentUser() {
        if (!$this->user && $this->isLoggedIn()) {
            $this->user = $this->db->getRow(
                "SELECT * FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
        }
        return $this->user;
    }

    /**
     * بررسی دسترسی‌های کاربر
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // اگر کاربر ادمین است، همه دسترسی‌ها را دارد
        if ($_SESSION['role'] === 'admin') {
            return true;
        }

        // بررسی دسترسی‌های خاص
        $permissions = $this->db->getRow(
            "SELECT permissions FROM user_permissions WHERE user_id = ?",
            [$_SESSION['user_id']]
        );

        if ($permissions) {
            $userPermissions = json_decode($permissions['permissions'], true);
            return in_array($permission, $userPermissions);
        }

        return false;
    }

    /**
     * دریافت خطاها
     */
    public function getErrors() {
        return $this->errors;
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