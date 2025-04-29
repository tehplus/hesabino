<?php
/**
 * کلاس مدیریت احراز هویت و دسترسی کاربران
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Auth {
    private static $instance = null;
    private $db = null;
    private $errors = [];
    private $user = null;
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->db = Database::getInstance();
        
        // بررسی لاگین کاربر از session
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->db->getRow(
                "SELECT * FROM " . DB_PREFIX . "users WHERE id = ? AND status = 'active' LIMIT 1",
                [$_SESSION['user_id']]
            );
            
            // اگر کاربر در دیتابیس نباشد یا غیرفعال شده باشد
            if (!$this->user) {
                unset($_SESSION['user_id']);
                unset($_SESSION['user_permissions']);
            }
        }
        // بررسی لاگین کاربر از کوکی remember me
        elseif (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
            list($user_id, $token) = explode(':', $_COOKIE[REMEMBER_COOKIE_NAME]);
            
            $user = $this->db->getRow(
                "SELECT u.* FROM " . DB_PREFIX . "users u 
                INNER JOIN " . DB_PREFIX . "user_tokens ut ON u.id = ut.user_id 
                WHERE u.id = ? AND ut.token = ? AND ut.expires > NOW() AND u.status = 'active' 
                LIMIT 1",
                [$user_id, $token]
            );
            
            if ($user) {
                $this->user = $user;
                $_SESSION['user_id'] = $user['id'];
                $this->loadPermissions();
                
                // تمدید توکن
                $this->db->update('user_tokens', 
                    ['expires' => date('Y-m-d H:i:s', time() + REMEMBER_COOKIE_EXPIRY)],
                    'user_id = ? AND token = ?',
                    [$user_id, $token]
                );
                
                setcookie(
                    REMEMBER_COOKIE_NAME,
                    $user_id . ':' . $token,
                    time() + REMEMBER_COOKIE_EXPIRY,
                    '/',
                    '',
                    true,
                    true
                );
            } else {
                setcookie(REMEMBER_COOKIE_NAME, '', time() - 3600, '/');
            }
        }
    }
    
    /**
     * دریافت نمونه کلاس (Singleton)
     * 
     * @return Auth
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * ثبت‌نام کاربر جدید
     * 
     * @param array $data اطلاعات کاربر
     * @return bool
     */
    public function register($data) {
        // اعتبارسنجی داده‌ها
        if (!$this->validateRegistrationData($data)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // درج اطلاعات کاربر
            $userId = $this->db->insert('users', [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST]),
                'full_name' => $data['full_name'],
                'mobile' => $data['mobile'],
                'company' => $data['company'] ?? null,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // درج نقش پیش‌فرض
            $this->db->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => 2 // نقش کاربر عادی
            ]);
            
            // ایجاد کد تأیید ایمیل
            $verificationCode = generateVerificationCode();
            $this->db->insert('user_verifications', [
                'user_id' => $userId,
                'code' => $verificationCode,
                'type' => 'email',
                'expires' => date('Y-m-d H:i:s', time() + 7200), // 2 ساعت
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // ارسال ایمیل تأیید
            $emailSent = $this->sendVerificationEmail($data['email'], $data['full_name'], $verificationCode);
            
            if (!$emailSent) {
                throw new Exception('خطا در ارسال ایمیل تأیید');
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * ورود کاربر
     * 
     * @param string $username نام کاربری یا ایمیل
     * @param string $password رمز عبور
     * @param bool $remember مرا به خاطر بسپار
     * @return bool
     */
    public function login($username, $password, $remember = false) {
        // بررسی فیلدهای خالی
        if (empty($username) || empty($password)) {
            $this->errors[] = 'لطفاً تمام فیلدها را پر کنید.';
            return false;
        }
        
        // جستجوی کاربر
        $user = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "users 
            WHERE (username = ? OR email = ?) AND status != 'deleted' 
            LIMIT 1",
            [$username, $username]
        );
        
        if (!$user) {
            $this->errors[] = 'نام کاربری یا رمز عبور اشتباه است.';
            return false;
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            // ثبت تلاش ناموفق
            $this->logFailedLogin($user['id']);
            $this->errors[] = 'نام کاربری یا رمز عبور اشتباه است.';
            return false;
        }
        
        // بررسی وضعیت کاربر
        if ($user['status'] === 'blocked') {
            $this->errors[] = 'حساب کاربری شما مسدود شده است.';
            return false;
        }
        
        if ($user['status'] === 'pending') {
            $this->errors[] = 'لطفاً ابتدا ایمیل خود را تأیید کنید.';
            return false;
        }
        
        // ذخیره اطلاعات در session
        $_SESSION['user_id'] = $user['id'];
        $this->user = $user;
        
        // بارگذاری دسترسی‌ها
        $this->loadPermissions();
        
        // ثبت زمان آخرین ورود
        $this->db->update('users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        // ثبت لاگ ورود
        $this->logLogin($user['id']);
        
        // ایجاد توکن remember me
        if ($remember) {
            $token = generateRandomString(64);
            $this->db->insert('user_tokens', [
                'user_id' => $user['id'],
                'token' => $token,
                'created_at' => date('Y-m-d H:i:s'),
                'expires' => date('Y-m-d H:i:s', time() + REMEMBER_COOKIE_EXPIRY)
            ]);
            
            setcookie(
                REMEMBER_COOKIE_NAME,
                $user['id'] . ':' . $token,
                time() + REMEMBER_COOKIE_EXPIRY,
                '/',
                '',
                true, // فقط HTTPS
                true  // فقط HTTP
            );
        }
        
        return true;
    }
    
    /**
     * خروج کاربر
     */
    public function logout() {
        // حذف توکن remember me
        if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
            list($user_id, $token) = explode(':', $_COOKIE[REMEMBER_COOKIE_NAME]);
            
            $this->db->delete('user_tokens',
                'user_id = ? AND token = ?',
                [$user_id, $token]
            );
            
            setcookie(REMEMBER_COOKIE_NAME, '', time() - 3600, '/');
        }
        
        // حذف session
        session_destroy();
        $this->user = null;
    }
    
    /**
     * بازیابی رمز عبور
     * 
     * @param string $email ایمیل کاربر
     * @return bool
     */
    public function forgotPassword($email) {
        // بررسی وجود کاربر
        $user = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "users 
            WHERE email = ? AND status = 'active' 
            LIMIT 1",
            [$email]
        );
        
        if (!$user) {
            $this->errors[] = 'کاربری با این ایمیل یافت نشد.';
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // حذف توکن‌های قبلی
            $this->db->delete('password_resets', 'user_id = ?', [$user['id']]);
            
            // ایجاد توکن جدید
            $token = bin2hex(random_bytes(32));
            $this->db->insert('password_resets', [
                'user_id' => $user['id'],
                'token' => $token,
                'created_at' => date('Y-m-d H:i:s'),
                'expires' => date('Y-m-d H:i:s', time() + 7200) // 2 ساعت
            ]);
            
            // ارسال ایمیل بازیابی
            $resetLink = SITE_URL . 'reset-password?token=' . $token;
            $emailSent = $this->sendPasswordResetEmail($user['email'], $user['full_name'], $resetLink);
            
            if (!$emailSent) {
                throw new Exception('خطا در ارسال ایمیل بازیابی');
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * تغییر رمز عبور
     * 
     * @param string $token توکن بازیابی
     * @param string $password رمز عبور جدید
     * @return bool
     */
    public function resetPassword($token, $password) {
        // بررسی توکن
        $reset = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "password_resets 
            WHERE token = ? AND expires > NOW() AND used = 0 
            LIMIT 1",
            [$token]
        );
        
        if (!$reset) {
            $this->errors[] = 'لینک بازیابی نامعتبر یا منقضی شده است.';
            return false;
        }
        
        // اعتبارسنجی رمز عبور
        if (strlen($password) < 8) {
            $this->errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // بروزرسانی رمز عبور
            $this->db->update('users',
                ['password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST])],
                'id = ?',
                [$reset['user_id']]
            );
            
            // غیرفعال کردن توکن
            $this->db->update('password_resets',
                ['used' => 1],
                'id = ?',
                [$reset['id']]
            );
            
            // ارسال ایمیل اطلاع‌رسانی
            $user = $this->db->getRow(
                "SELECT * FROM " . DB_PREFIX . "users WHERE id = ? LIMIT 1",
                [$reset['user_id']]
            );
            
            $emailSent = $this->sendPasswordChangeNotification($user['email'], $user['full_name']);
            
            if (!$emailSent) {
                throw new Exception('خطا در ارسال ایمیل اطلاع‌رسانی');
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * تأیید ایمیل کاربر
     * 
     * @param string $code کد تأیید
     * @return bool
     */
    public function verifyEmail($code) {
        // بررسی کد تأیید
        $verification = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "user_verifications 
            WHERE code = ? AND type = 'email' AND used = 0 AND expires > NOW() 
            LIMIT 1",
            [$code]
        );
        
        if (!$verification) {
            $this->errors[] = 'کد تأیید نامعتبر یا منقضی شده است.';
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // فعال کردن کاربر
            $this->db->update('users',
                ['status' => 'active', 'email_verified_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$verification['user_id']]
            );
            
            // غیرفعال کردن کد تأیید
            $this->db->update('user_verifications',
                ['used' => 1],
                'id = ?',
                [$verification['id']]
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * بررسی لاگین بودن کاربر
     * 
     * @return bool
     */
    public function isLoggedIn() {
        return $this->user !== null;
    }
    
    /**
     * بررسی دسترسی کاربر
     * 
     * @param string $permission دسترسی مورد نظر
     * @return bool
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return isset($_SESSION['user_permissions']) && 
               in_array($permission, $_SESSION['user_permissions']);
    }
    
    /**
     * بررسی نقش کاربر
     * 
     * @param string $role نقش مورد نظر
     * @return bool
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return isset($_SESSION['user_roles']) && 
               in_array($role, $_SESSION['user_roles']);
    }
    
    /**
     * دریافت اطلاعات کاربر
     * 
     * @return array|null
     */
    public function getUser() {
        return $this->user;
    }
    
    /**
     * دریافت خطاها
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * بارگذاری دسترسی‌های کاربر
     */
    private function loadPermissions() {
        if (!$this->isLoggedIn()) {
            return;
        }
        
        // دریافت نقش‌ها
        $roles = $this->db->getRows(
            "SELECT r.name 
            FROM " . DB_PREFIX . "roles r 
            INNER JOIN " . DB_PREFIX . "user_roles ur ON r.id = ur.role_id 
            WHERE ur.user_id = ?",
            [$this->user['id']]
        );
        
        $_SESSION['user_roles'] = array_column($roles, 'name');
        
        // دریافت دسترسی‌ها
        $permissions = $this->db->getRows(
            "SELECT DISTINCT p.name 
            FROM " . DB_PREFIX . "permissions p 
            INNER JOIN " . DB_PREFIX . "role_permissions rp ON p.id = rp.permission_id 
            INNER JOIN " . DB_PREFIX . "user_roles ur ON rp.role_id = ur.role_id 
            WHERE ur.user_id = ?",
            [$this->user['id']]
        );
        
        $_SESSION['user_permissions'] = array_column($permissions, 'name');
    }
    
    /**
     * اعتبارسنجی داده‌های ثبت‌نام
     * 
     * @param array $data داده‌ها
     * @return bool
     */
    private function validateRegistrationData($data) {
        $this->errors = [];
        
        // بررسی فیلدهای اجباری
        $required = ['username', 'email', 'password', 'confirm_password', 'full_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->errors[] = 'لطفاً تمام فیلدهای اجباری را پر کنید.';
                return false;
            }
        }
        
        // بررسی نام کاربری
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{2,49}$/', $data['username'])) {
            $this->errors[] = 'نام کاربری باید با حروف انگلیسی شروع شود و فقط شامل حروف، اعداد و کاراکترهای - . _ باشد.';
            return false;
        }
        
        // بررسی تکراری بودن نام کاربری
        if ($this->db->getValue(
            "SELECT COUNT(*) FROM " . DB_PREFIX . "users WHERE username = ?",
            [$data['username']]
        ) > 0) {
            $this->errors[] = 'این نام کاربری قبلاً ثبت شده است.';
            return false;
        }
        
        // بررسی ایمیل
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'لطفاً یک ایمیل معتبر وارد کنید.';
            return false;
        }
        
        // بررسی تکراری بودن ایمیل
        if ($this->db->getValue(
            "SELECT COUNT(*) FROM " . DB_PREFIX . "users WHERE email = ?",
            [$data['email']]
        ) > 0) {
            $this->errors[] = 'این ایمیل قبلاً ثبت شده است.';
            return false;
        }
        
        // بررسی رمز عبور
        if (strlen($data['password']) < 8) {
            $this->errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
            return false;
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            $this->errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
            return false;
        }
        
        // بررسی شماره موبایل
        if (!empty($data['mobile']) && !validateMobile($data['mobile'])) {
            $this->errors[] = 'لطفاً یک شماره موبایل معتبر وارد کنید.';
            return false;
        }
        
        return true;
    }
    
    /**
     * ارسال ایمیل تأیید
     * 
     * @param string $email ایمیل
     * @param string $name نام کاربر
     * @param string $code کد تأیید
     * @return bool
     */
    private function sendVerificationEmail($email, $name, $code) {
        $subject = 'تأیید ایمیل - ' . SITE_NAME;
        
        $message = '<div dir="rtl" style="font-family: Vazirmatn, Tahoma; line-height: 1.6;">';
        $message .= '<h2>تأیید ایمیل</h2>';
        $message .= '<p>سلام ' . $name . ' عزیز</p>';
        $message .= '<p>به ' . SITE_NAME . ' خوش آمدید. برای تکمیل ثبت‌نام، لطفاً ایمیل خود را تأیید کنید.</p>';
        $message .= '<p>کد تأیید: <strong style="font-size: 18px;">' . $code . '</strong></p>';
        $message .= '<p>این کد تا 2 ساعت معتبر است.</p>';
        $message .= '<hr>';
        $message .= '<p style="font-size: 13px; color: #666;">اگر شما درخواست ثبت‌نام نداده‌اید، این ایمیل را نادیده بگیرید.</p>';
        $message .= '</div>';
        
        return sendMail($email, $subject, $message);
    }
    
    /**
     * ارسال ایمیل بازیابی رمز عبور
     * 
     * @param string $email ایمیل
     * @param string $name نام کاربر
     * @param string $link لینک بازیابی
     * @return bool
     */
    private function sendPasswordResetEmail($email, $name, $link) {
        $subject = 'بازیابی رمز عبور - ' . SITE_NAME;
        
        $message = '<div dir="rtl" style="font-family: Vazirmatn, Tahoma; line-height: 1.6;">';
        $message .= '<h2>بازیابی رمز عبور</h2>';
        $message .= '<p>سلام ' . $name . ' عزیز</p>';
        $message .= '<p>درخواست بازیابی رمز عبور برای حساب کاربری شما ثبت شده است. برای تغییر رمز عبور روی لینک زیر کلیک کنید:</p>';
        $message .= '<p><a href="' . $link . '" style="display: inline-block; padding: 10px 20px; background-color: #667eea; color: #fff; text-decoration: none; border-radius: 5px;">تغییر رمز عبور</a></p>';
        $message .= '<p>این لینک تا 2 ساعت معتبر است.</p>';
        $message .= '<hr>';
        $message .= '<p style="font-size: 13px; color: #666;">اگر شما درخواست بازیابی رمز عبور نداده‌اید، این ایمیل را نادیده بگیرید.</p>';
        $message .= '</div>';
        
        return sendMail($email, $subject, $message);
    }
    
    /**
     * ارسال ایمیل اطلاع‌رسانی تغییر رمز عبور
     * 
     * @param string $email ایمیل
     * @param string $name نام کاربر
     * @return bool
     */
    private function sendPasswordChangeNotification($email, $name) {
        $subject = 'تغییر رمز عبور - ' . SITE_NAME;
        
        $message = '<div dir="rtl" style="font-family: Vazirmatn, Tahoma; line-height: 1.6;">';
        $message .= '<h2>تغییر رمز عبور</h2>';
        $message .= '<p>سلام ' . $name . ' عزیز</p>';
        $message .= '<p>رمز عبور حساب کاربری شما با موفقیت تغییر کرد.</p>';
        $message .= '<p>اگر شما این تغییر را انجام نداده‌اید، لطفاً سریعاً با پشتیبانی تماس بگیرید.</p>';
        $message .= '</div>';
        
        return sendMail($email, $subject, $message);
    }
    
    /**
     * ثبت تلاش ناموفق ورود
     * 
     * @param int $userId شناسه کاربر
     */
    private function logFailedLogin($userId) {
        $this->db->insert('login_attempts', [
            'user_id' => $userId,
            'ip' => getRealIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // بررسی تعداد تلاش‌های ناموفق در 15 دقیقه گذشته
        $attempts = $this->db->getValue(
            "SELECT COUNT(*) FROM " . DB_PREFIX . "login_attempts 
            WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$userId]
        );
        
        // مسدود کردن کاربر پس از 5 تلاش ناموفق
        if ($attempts >= 5) {
            $this->db->update('users',
                ['status' => 'blocked'],
                'id = ?',
                [$userId]
            );
        }
    }
    
    /**
     * ثبت لاگ ورود موفق
     * 
     * @param int $userId شناسه کاربر
     */
    private function logLogin($userId) {
        $this->db->insert('login_logs', [
            'user_id' => $userId,
            'ip' => getRealIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // پاک کردن تلاش‌های ناموفق قبلی
        $this->db->delete('login_attempts', 'user_id = ?', [$userId]);
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