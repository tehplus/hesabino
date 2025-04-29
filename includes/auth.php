<?php
/**
 * کلاس مدیریت احراز هویت
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
    private $db;
    private $errors = [];
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * جلوگیری از clone شدن
     */
    private function __clone() {}
    
    /**
     * بازیابی نمونه کلاس از حالت serialize
     */
    public function __wakeup() {
        $this->db = Database::getInstance();
    }
    
    /**
     * دریافت نمونه کلاس (Singleton)
     * @return Auth
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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
        // بررسی ورودی‌ها
        if (empty($username) || empty($password)) {
            $this->errors[] = 'لطفاً تمام فیلدها را پر کنید.';
            return false;
        }
        
        // جستجوی کاربر
        $user = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "users WHERE (username = ? OR email = ?) AND status = 'active'",
            [$username, $username]
        );
        
        if (!$user) {
            $this->errors[] = 'نام کاربری یا رمز عبور اشتباه است.';
            return false;
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            $this->errors[] = 'نام کاربری یا رمز عبور اشتباه است.';
            return false;
        }
        
        // بررسی تأیید ایمیل
        if (empty($user['email_verified_at'])) {
            $this->errors[] = 'لطفاً ایمیل خود را تأیید کنید.';
            return false;
        }
        
        // ذخیره اطلاعات در session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        
        // دریافت نقش‌های کاربر
        $roles = $this->db->getColumn(
            "SELECT r.name FROM " . DB_PREFIX . "roles r
            INNER JOIN " . DB_PREFIX . "user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ?",
            [$user['id']]
        );
        $_SESSION['user_roles'] = $roles;
        
        // دریافت دسترسی‌های کاربر
        $permissions = $this->db->getColumn(
            "SELECT DISTINCT p.name FROM " . DB_PREFIX . "permissions p
            INNER JOIN " . DB_PREFIX . "role_permissions rp ON p.id = rp.permission_id
            INNER JOIN " . DB_PREFIX . "user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?",
            [$user['id']]
        );
        $_SESSION['user_permissions'] = $permissions;
        
        // ذخیره کوکی "مرا به خاطر بسپار"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + REMEMBER_COOKIE_EXPIRY;
            
            // ذخیره توکن در دیتابیس
            $this->db->insert(DB_PREFIX . 'remember_tokens', [
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', $expiry)
            ]);
            
            // تنظیم کوکی
            setcookie(
                REMEMBER_COOKIE_NAME,
                $token,
                [
                    'expires' => $expiry,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }
        
        // ثبت لاگ ورود
        $this->db->insert(DB_PREFIX . 'login_logs', [
            'user_id' => $user['id'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    }
    
    /**
     * خروج کاربر
     */
    public function logout() {
        // حذف توکن "مرا به خاطر بسپار"
        if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
            $token = $_COOKIE[REMEMBER_COOKIE_NAME];
            $this->db->delete(
                DB_PREFIX . 'remember_tokens',
                'token = ?',
                [$token]
            );
            
            setcookie(
                REMEMBER_COOKIE_NAME,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }
        
        // حذف متغیرهای session
        unset(
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['full_name'],
            $_SESSION['email'],
            $_SESSION['user_roles'],
            $_SESSION['user_permissions']
        );
        
        session_destroy();
    }
    
    /**
     * ثبت‌نام کاربر جدید
     * 
     * @param array $data اطلاعات کاربر
     * @return bool
     */
    public function register($data) {
        // بررسی ورودی‌ها
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        $fullName = trim($data['full_name'] ?? '');
        
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword) || empty($fullName)) {
            $this->errors[] = 'لطفاً تمام فیلدهای اجباری را پر کنید.';
            return false;
        }
        
        // بررسی فرمت نام کاربری
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{2,49}$/', $username)) {
            $this->errors[] = 'نام کاربری باید با حروف انگلیسی شروع شود و فقط شامل حروف، اعداد و کاراکترهای مجاز باشد.';
            return false;
        }
        
        // بررسی فرمت ایمیل
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'لطفاً یک ایمیل معتبر وارد کنید.';
            return false;
        }
        
        // بررسی طول رمز عبور
        if (strlen($password) < 8) {
            $this->errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
            return false;
        }
        
        // بررسی پیچیدگی رمز عبور
        if (!preg_match('/[A-Z]/', $password) || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $this->errors[] = 'رمز عبور باید شامل حروف بزرگ، کوچک، اعداد و کاراکترهای خاص باشد.';
            return false;
        }
        
        // بررسی تطابق رمز عبور
        if ($password !== $confirmPassword) {
            $this->errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
            return false;
        }
        
        // بررسی تکراری نبودن نام کاربری
        if ($this->db->getValue(
            "SELECT COUNT(*) FROM " . DB_PREFIX . "users WHERE username = ?",
            [$username]
        ) > 0) {
            $this->errors[] = 'این نام کاربری قبلاً ثبت شده است.';
            return false;
        }
        
        // بررسی تکراری نبودن ایمیل
        if ($this->db->getValue(
            "SELECT COUNT(*) FROM " . DB_PREFIX . "users WHERE email = ?",
            [$email]
        ) > 0) {
            $this->errors[] = 'این ایمیل قبلاً ثبت شده است.';
            return false;
        }
        
        // ایجاد کاربر جدید
        $userId = $this->db->insert(DB_PREFIX . 'users', [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]),
            'full_name' => $fullName,
            'mobile' => $data['mobile'] ?? null,
            'company' => $data['company'] ?? null,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$userId) {
            $this->errors[] = 'خطا در ثبت اطلاعات. لطفاً مجدداً تلاش کنید.';
            return false;
        }
        
        // اختصاص نقش پیش‌فرض
        $defaultRoleId = $this->db->getValue(
            "SELECT id FROM " . DB_PREFIX . "roles WHERE name = 'user'"
        );
        
        if ($defaultRoleId) {
            $this->db->insert(DB_PREFIX . 'user_roles', [
                'user_id' => $userId,
                'role_id' => $defaultRoleId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // ارسال ایمیل تأیید
        $verificationCode = generateVerificationCode();
        $this->db->insert(DB_PREFIX . 'verification_codes', [
            'user_id' => $userId,
            'code' => $verificationCode,
            'type' => 'email',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600)
        ]);
        
        $message = "
            <h3>به {$_SERVER['HTTP_HOST']} خوش آمدید</h3>
            <p>برای تأیید ایمیل خود، کد زیر را وارد کنید:</p>
            <h2 style='text-align:center;'>{$verificationCode}</h2>
            <p>این کد تا یک ساعت معتبر است.</p>
        ";
        
        sendMail($email, 'تأیید ایمیل', $message);
        
        return true;
    }
    
    /**
     * تأیید ایمیل
     * 
     * @param int $userId شناسه کاربر
     * @param string $code کد تأیید
     * @return bool
     */
    public function verifyEmail($userId, $code) {
        // بررسی کد تأیید
        $verification = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "verification_codes
            WHERE user_id = ? AND code = ? AND type = 'email'
            AND expires_at > NOW() AND used_at IS NULL",
            [$userId, $code]
        );
        
        if (!$verification) {
            $this->errors[] = 'کد تأیید نامعتبر است یا منقضی شده است.';
            return false;
        }
        
        // تأیید ایمیل
        $this->db->update(
            DB_PREFIX . 'users',
            ['email_verified_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userId]
        );
        
        // غیرفعال کردن کد تأیید
        $this->db->update(
            DB_PREFIX . 'verification_codes',
            ['used_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$verification['id']]
        );
        
        return true;
    }
    
    /**
     * درخواست بازیابی رمز عبور
     * 
     * @param string $email ایمیل کاربر
     * @return bool
     */
    public function forgotPassword($email) {
        // بررسی وجود کاربر
        $user = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "users WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            $this->errors[] = 'کاربری با این ایمیل یافت نشد.';
            return false;
        }
        
        // تولید توکن بازیابی
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600); // یک ساعت
        
        $this->db->insert(DB_PREFIX . 'password_resets', [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expiry,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // ارسال ایمیل بازیابی
        $resetLink = url("reset-password?token={$token}");
        $message = "
            <h3>درخواست بازیابی رمز عبور</h3>
            <p>برای بازیابی رمز عبور خود روی لینک زیر کلیک کنید:</p>
            <p><a href='{$resetLink}'>{$resetLink}</a></p>
            <p>این لینک تا یک ساعت معتبر است.</p>
            <p>اگر شما این درخواست را نداده‌اید، این ایمیل را نادیده بگیرید.</p>
        ";
        
        sendMail($email, 'بازیابی رمز عبور', $message);
        
        return true;
    }
    
    /**
     * بازیابی رمز عبور
     * 
     * @param string $token توکن بازیابی
     * @param string $password رمز عبور جدید
     * @param string $confirmPassword تکرار رمز عبور
     * @return bool
     */
    public function resetPassword($token, $password, $confirmPassword) {
        // بررسی توکن
        $reset = $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "password_resets
            WHERE token = ? AND expires_at > NOW() AND used_at IS NULL",
            [$token]
        );
        
        if (!$reset) {
            $this->errors[] = 'لینک بازیابی نامعتبر است یا منقضی شده است.';
            return false;
        }
        
        // بررسی رمز عبور
        if (strlen($password) < 8) {
            $this->errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password) || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $this->errors[] = 'رمز عبور باید شامل حروف بزرگ، کوچک، اعداد و کاراکترهای خاص باشد.';
            return false;
        }
        
        if ($password !== $confirmPassword) {
            $this->errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
            return false;
        }
        
        // بروزرسانی رمز عبور
        $this->db->update(
            DB_PREFIX . 'users',
            [
                'password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$reset['user_id']]
        );
        
        // غیرفعال کردن توکن
        $this->db->update(
            DB_PREFIX . 'password_resets',
            ['used_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$reset['id']]
        );
        
        return true;
    }
    
    /**
     * تغییر رمز عبور
     * 
     * @param int $userId شناسه کاربر
     * @param string $currentPassword رمز عبور فعلی
     * @param string $newPassword رمز عبور جدید
     * @param string $confirmPassword تکرار رمز عبور
     * @return bool
     */
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        // بررسی رمز عبور فعلی
        $user = $this->db->getRow(
            "SELECT password FROM " . DB_PREFIX . "users WHERE id = ?",
            [$userId]
        );
        
        if (!password_verify($currentPassword, $user['password'])) {
            $this->errors[] = 'رمز عبور فعلی اشتباه است.';
            return false;
        }
        
        // بررسی رمز عبور جدید
        if (strlen($newPassword) < 8) {
            $this->errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $newPassword) || 
            !preg_match('/[a-z]/', $newPassword) || 
            !preg_match('/[0-9]/', $newPassword) || 
            !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
            $this->errors[] = 'رمز عبور باید شامل حروف بزرگ، کوچک، اعداد و کاراکترهای خاص باشد.';
            return false;
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
            return false;
        }
        
        // بروزرسانی رمز عبور
        $this->db->update(
            DB_PREFIX . 'users',
            [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => HASH_COST]),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$userId]
        );
        
        return true;
    }
    
    /**
     * بررسی لاگین بودن کاربر
     * 
     * @return bool
     */
    public function isLoggedIn() {
        // بررسی session
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        // بررسی کوکی "مرا به خاطر بسپار"
        if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
            $token = $_COOKIE[REMEMBER_COOKIE_NAME];
            
            $remember = $this->db->getRow(
                "SELECT u.* FROM " . DB_PREFIX . "users u
                INNER JOIN " . DB_PREFIX . "remember_tokens rt ON u.id = rt.user_id
                WHERE rt.token = ? AND rt.expires_at > NOW()",
                [$token]
            );
            
            if ($remember) {
                // ذخیره اطلاعات در session
                $_SESSION['user_id'] = $remember['id'];
                $_SESSION['username'] = $remember['username'];
                $_SESSION['full_name'] = $remember['full_name'];
                $_SESSION['email'] = $remember['email'];
                
                // دریافت نقش‌ها و دسترسی‌ها
                $roles = $this->db->getColumn(
                    "SELECT r.name FROM " . DB_PREFIX . "roles r
                    INNER JOIN " . DB_PREFIX . "user_roles ur ON r.id = ur.role_id
                    WHERE ur.user_id = ?",
                    [$remember['id']]
                );
                $_SESSION['user_roles'] = $roles;
                
                $permissions = $this->db->getColumn(
                    "SELECT DISTINCT p.name FROM " . DB_PREFIX . "permissions p
                    INNER JOIN " . DB_PREFIX . "role_permissions rp ON p.id = rp.permission_id
                    INNER JOIN " . DB_PREFIX . "user_roles ur ON rp.role_id = ur.role_id
                    WHERE ur.user_id = ?",
                    [$remember['id']]
                );
                $_SESSION['user_permissions'] = $permissions;
                
                return true;
            }
            
            // حذف کوکی نامعتبر
            setcookie(
                REMEMBER_COOKIE_NAME,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }
        
        return false;
    }
    
    /**
     * بررسی داشتن نقش
     * 
     * @param string $role نام نقش
     * @return bool
     */
    public function hasRole($role) {
        return isset($_SESSION['user_roles']) && in_array($role, $_SESSION['user_roles']);
    }
    
    /**
     * بررسی داشتن دسترسی
     * 
     * @param string $permission نام دسترسی
     * @return bool
     */
    public function hasPermission($permission) {
        return isset($_SESSION['user_permissions']) && in_array($permission, $_SESSION['user_permissions']);
    }
    
    /**
     * بررسی داشتن یکی از دسترسی‌ها
     * 
     * @param array $permissions آرایه دسترسی‌ها
     * @return bool
     */
    public function hasAnyPermission($permissions) {
        if (!isset($_SESSION['user_permissions'])) {
            return false;
        }
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $_SESSION['user_permissions'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * بررسی داشتن همه دسترسی‌ها
     * 
     * @param array $permissions آرایه دسترسی‌ها
     * @return bool
     */
    public function hasAllPermissions($permissions) {
        if (!isset($_SESSION['user_permissions'])) {
            return false;
        }
        
        foreach ($permissions as $permission) {
            if (!in_array($permission, $_SESSION['user_permissions'])) {
                return false;
            }
        }
        
        return true;
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
     * تغییر تنظیمات پروفایل
     * 
     * @param int $userId شناسه کاربر
     * @param array $data اطلاعات جدید
     * @return bool
     */
    public function updateProfile($userId, $data) {
        $updateData = [
            'full_name' => trim($data['full_name'] ?? ''),
            'mobile' => trim($data['mobile'] ?? ''),
            'company' => trim($data['company'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // بررسی فیلدهای اجباری
        if (empty($updateData['full_name'])) {
            $this->errors[] = 'نام و نام خانوادگی الزامی است.';
            return false;
        }

        // بررسی شماره موبایل
        if (!empty($updateData['mobile']) && !validateMobile($updateData['mobile'])) {
            $this->errors[] = 'شماره موبایل معتبر نیست.';
            return false;
        }

        // بروزرسانی اطلاعات
        $success = $this->db->update(
            DB_PREFIX . 'users',
            $updateData,
            'id = ?',
            [$userId]
        );

        if (!$success) {
            $this->errors[] = 'خطا در بروزرسانی اطلاعات.';
            return false;
        }

        return true;
    }

    /**
     * تغییر تصویر پروفایل
     * 
     * @param int $userId شناسه کاربر
     * @param array $file فایل آپلود شده
     * @return bool|string
     */
    public function updateAvatar($userId, $file) {
        // بررسی خطای آپلود
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = 'خطا در آپلود فایل.';
            return false;
        }

        // بررسی نوع فایل
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $this->errors[] = 'فرمت فایل مجاز نیست. فقط jpg، png و gif مجاز است.';
            return false;
        }

        // بررسی حجم فایل (حداکثر 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->errors[] = 'حجم فایل نباید بیشتر از 2 مگابایت باشد.';
            return false;
        }

        // ایجاد نام فایل یکتا
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = BASEPATH . '/uploads/avatars/';
        $filePath = $uploadPath . $filename;

        // ایجاد پوشه در صورت نیاز
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // آپلود فایل
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->errors[] = 'خطا در ذخیره فایل.';
            return false;
        }

        // بروزرسانی آواتار در دیتابیس
        $success = $this->db->update(
            DB_PREFIX . 'users',
            [
                'avatar' => 'uploads/avatars/' . $filename,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$userId]
        );

        if (!$success) {
            unlink($filePath);
            $this->errors[] = 'خطا در بروزرسانی اطلاعات.';
            return false;
        }

        // حذف آواتار قبلی
        $oldAvatar = $this->db->getValue(
            "SELECT avatar FROM " . DB_PREFIX . "users WHERE id = ?",
            [$userId]
        );

        if ($oldAvatar && file_exists(BASEPATH . '/' . $oldAvatar)) {
            unlink(BASEPATH . '/' . $oldAvatar);
        }

        return 'uploads/avatars/' . $filename;
    }

    /**
     * تغییر وضعیت کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $status وضعیت جدید (active|inactive|banned)
     * @return bool
     */
    public function changeStatus($userId, $status) {
        $allowedStatuses = ['active', 'inactive', 'banned'];
        
        if (!in_array($status, $allowedStatuses)) {
            $this->errors[] = 'وضعیت نامعتبر است.';
            return false;
        }

        $success = $this->db->update(
            DB_PREFIX . 'users',
            [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$userId]
        );

        if (!$success) {
            $this->errors[] = 'خطا در تغییر وضعیت.';
            return false;
        }

        return true;
    }

    /**
     * حذف نشست‌های منقضی شده
     */
    public function cleanExpiredSessions() {
        // حذف توکن‌های "مرا به خاطر بسپار" منقضی شده
        $this->db->delete(
            DB_PREFIX . 'remember_tokens',
            'expires_at < NOW()'
        );

        // حذف کدهای تأیید منقضی شده
        $this->db->delete(
            DB_PREFIX . 'verification_codes',
            'expires_at < NOW()'
        );

        // حذف توکن‌های بازیابی رمز عبور منقضی شده
        $this->db->delete(
            DB_PREFIX . 'password_resets',
            'expires_at < NOW()'
        );
    }

    /**
     * دریافت اطلاعات کاربر
     * 
     * @param int $userId شناسه کاربر
     * @return array|false
     */
    public function getUser($userId) {
        return $this->db->getRow(
            "SELECT * FROM " . DB_PREFIX . "users WHERE id = ?",
            [$userId]
        );
    }

    /**
     * بررسی دسترسی به یک منبع
     * 
     * @param int $userId شناسه کاربر
     * @param string $resource نام منبع
     * @param string $action نوع عملیات
     * @return bool
     */
    public function checkAccess($userId, $resource, $action) {
        $permissions = $this->db->getColumn(
            "SELECT DISTINCT p.name FROM " . DB_PREFIX . "permissions p
            INNER JOIN " . DB_PREFIX . "role_permissions rp ON p.id = rp.permission_id
            INNER JOIN " . DB_PREFIX . "user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?",
            [$userId]
        );

        $requiredPermission = $resource . '.' . $action;
        return in_array($requiredPermission, $permissions);
    }

    /**
     * ثبت فعالیت کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $action نوع فعالیت
     * @param string $description توضیحات
     * @param array $data اطلاعات اضافی
     * @return bool
     */
    public function logActivity($userId, $action, $description = '', $data = []) {
        return $this->db->insert(DB_PREFIX . 'activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * بررسی محدودیت تلاش‌های ناموفق
     * 
     * @param string $identifier شناسه (IP یا نام کاربری)
     * @param string $type نوع محدودیت (login|reset_password)
     * @return bool
     */
    public function checkRateLimit($identifier, $type = 'login') {
        // پاکسازی رکوردهای قدیمی
        $this->db->delete(
            DB_PREFIX . 'rate_limits',
            'created_at < ?',
            [date('Y-m-d H:i:s', strtotime('-1 hour'))]
        );

        // بررسی تعداد تلاش‌ها
        $attempts = $this->db->getValue(
            "SELECT COUNT(*) FROM " . DB_PREFIX . "rate_limits 
            WHERE identifier = ? AND type = ? AND created_at > ?",
            [
                $identifier,
                $type,
                date('Y-m-d H:i:s', strtotime('-1 hour'))
            ]
        );

        // محدودیت: 5 تلاش در ساعت
        if ($attempts >= 5) {
            $this->errors[] = 'به دلیل تلاش‌های ناموفق زیاد، دسترسی شما به مدت 1 ساعت مسدود شده است.';
            return false;
        }

        // ثبت تلاش جدید
        $this->db->insert(DB_PREFIX . 'rate_limits', [
            'identifier' => $identifier,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /**
     * دریافت لیست فعالیت‌های کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param int $limit تعداد نتایج
     * @param int $offset شروع از
     * @return array
     */
    public function getUserActivities($userId, $limit = 10, $offset = 0) {
        return $this->db->getRows(
            "SELECT * FROM " . DB_PREFIX . "activity_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * دریافت تعداد کاربران آنلاین
     * 
     * @param int $minutes بازه زمانی به دقیقه
     * @return int
     */
    public function getOnlineUsers($minutes = 5) {
        return $this->db->getValue(
            "SELECT COUNT(DISTINCT user_id) FROM " . DB_PREFIX . "activity_logs 
            WHERE created_at > ?",
            [date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"))]
        );
    }
}