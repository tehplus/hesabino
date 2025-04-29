<?php
/**
 * اسکریپت نصب حسابینو
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// تنظیم زمان و کاراکترست
date_default_timezone_set('Asia/Tehran');
header('Content-Type: text/html; charset=utf-8');

// مسیر پایه
define('BASEPATH', dirname(__DIR__));

// بررسی نصب بودن برنامه
if (file_exists(BASEPATH . '/config/config.php')) {
    die('برنامه قبلاً نصب شده است. برای نصب مجدد، فایل config/config.php را حذف کنید.');
}

// کلاس نصب کننده
class Installer {
    private $step = 1;
    private $errors = [];
    private $success = [];
    private $db = null;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        session_start();
        
        // دریافت مرحله فعلی
        if (isset($_GET['step'])) {
            $this->step = (int)$_GET['step'];
        } elseif (isset($_SESSION['install_step'])) {
            $this->step = $_SESSION['install_step'];
        }
        
        // بررسی دسترسی به مرحله
        if ($this->step > 1 && !isset($_SESSION['requirements_checked'])) {
            $this->step = 1;
        }
        if ($this->step > 2 && !isset($_SESSION['db_connected'])) {
            $this->step = 2;
        }
        if ($this->step > 3 && !isset($_SESSION['tables_created'])) {
            $this->step = 3;
        }
        if ($this->step > 4 && !isset($_SESSION['admin_created'])) {
            $this->step = 4;
        }
        
        $_SESSION['install_step'] = $this->step;
    }
    
    /**
     * اجرای نصب کننده
     */
    public function run() {
        switch ($this->step) {
            case 1:
                $this->checkRequirements();
                break;
            case 2:
                $this->setupDatabase();
                break;
            case 3:
                $this->createTables();
                break;
            case 4:
                $this->createAdmin();
                break;
            case 5:
                $this->finish();
                break;
            default:
                $this->step = 1;
                $this->checkRequirements();
        }
        
        $this->render();
    }
    
    /**
     * بررسی نیازمندی‌ها
     */
    private function checkRequirements() {
        // بررسی نسخه PHP
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $this->errors[] = 'نسخه PHP باید 8.0.0 یا بالاتر باشد. نسخه فعلی: ' . PHP_VERSION;
        }
        
        // بررسی افزونه‌های مورد نیاز
        $required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'gd'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->errors[] = "افزونه $ext نصب نشده است.";
            }
        }
        
        // بررسی دسترسی نوشتن پوشه‌ها
        $writable_paths = [
            BASEPATH . '/config',
            BASEPATH . '/uploads',
            BASEPATH . '/cache',
            BASEPATH . '/logs'
        ];
        
        foreach ($writable_paths as $path) {
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            if (!is_writable($path)) {
                $this->errors[] = "پوشه $path قابل نوشتن نیست.";
            }
        }
        
        if (empty($this->errors)) {
            $_SESSION['requirements_checked'] = true;
            $this->success[] = 'تمام نیازمندی‌ها برقرار است.';
        }
    }
    
    /**
     * تنظیم دیتابیس
     */
    private function setupDatabase() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $host = $_POST['db_host'] ?? '';
            $name = $_POST['db_name'] ?? '';
            $user = $_POST['db_user'] ?? '';
            $pass = $_POST['db_pass'] ?? '';
            
            // اعتبارسنجی
            if (empty($host) || empty($name) || empty($user)) {
                $this->errors[] = 'لطفاً تمام فیلدهای اجباری را پر کنید.';
                return;
            }
            
            try {
                // تست اتصال
                $dsn = "mysql:host=$host;charset=utf8mb4";
                $this->db = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_persian_ci"
                ]);
                
                // ایجاد دیتابیس
                $this->db->exec("CREATE DATABASE IF NOT EXISTS `$name` 
                               DEFAULT CHARACTER SET utf8mb4 
                               COLLATE utf8mb4_persian_ci");
                
                $this->db->exec("USE `$name`");
                
                // ذخیره اطلاعات در session
                $_SESSION['db_config'] = [
                    'host' => $host,
                    'name' => $name,
                    'user' => $user,
                    'pass' => $pass
                ];
                
                $_SESSION['db_connected'] = true;
                $this->success[] = 'اتصال به دیتابیس با موفقیت برقرار شد.';
                
                // ایجاد فایل کانفیگ
                $config = $this->generateConfig($host, $name, $user, $pass);
                file_put_contents(BASEPATH . '/config/config.php', $config);
                
                $this->step++;
                header('Location: ?step=' . $this->step);
                exit;
                
            } catch (PDOException $e) {
                $this->errors[] = 'خطا در اتصال به دیتابیس: ' . $e->getMessage();
            }
        }
    }
    
    /**
     * ایجاد جداول
     */
    private function createTables() {
        if (!isset($_SESSION['db_config'])) {
            $this->step = 2;
            header('Location: ?step=2');
            exit;
        }
        
        try {
            $config = $_SESSION['db_config'];
            $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
            $this->db = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_persian_ci"
            ]);
            
            // خواندن فایل SQL
            $sql = file_get_contents(BASEPATH . '/install/database.sql');
            
            // اجرای کوئری‌ها
            $this->db->exec($sql);
            
            $_SESSION['tables_created'] = true;
            $this->success[] = 'جداول دیتابیس با موفقیت ایجاد شدند.';
            
            $this->step++;
            header('Location: ?step=' . $this->step);
            exit;
            
        } catch (PDOException $e) {
            $this->errors[] = 'خطا در ایجاد جداول: ' . $e->getMessage();
        }
    }
    
    /**
     * ایجاد مدیر اصلی
     */
    private function createAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $fullname = $_POST['full_name'] ?? '';
            
            // اعتبارسنجی
            if (empty($username) || empty($email) || empty($password) || empty($confirm) || empty($fullname)) {
                $this->errors[] = 'لطفاً تمام فیلدها را پر کنید.';
                return;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = 'لطفاً یک ایمیل معتبر وارد کنید.';
                return;
            }
            
            if (strlen($password) < 8) {
                $this->errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
                return;
            }
            
            if ($password !== $confirm) {
                $this->errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
                return;
            }
            
            try {
                $config = $_SESSION['db_config'];
                $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
                $this->db = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // درج کاربر مدیر
                $stmt = $this->db->prepare("
                    INSERT INTO hb_users (
                        username, email, password, full_name, status,
                        email_verified_at, created_at, updated_at
                    ) VALUES (
                        :username, :email, :password, :fullname, 'active',
                        NOW(), NOW(), NOW()
                    )
                ");
                
                $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]),
                    'fullname' => $fullname
                ]);
                
                $userId = $this->db->lastInsertId();
                
                // اختصاص نقش مدیر
                $stmt = $this->db->prepare("
                    INSERT INTO hb_user_roles (user_id, role_id, created_at)
                    SELECT :user_id, id, NOW()
                    FROM hb_roles
                    WHERE name = 'admin'
                ");
                
                $stmt->execute(['user_id' => $userId]);
                
                $_SESSION['admin_created'] = true;
                $this->success[] = 'کاربر مدیر با موفقیت ایجاد شد.';
                
                $this->step++;
                header('Location: ?step=' . $this->step);
                exit;
                
            } catch (PDOException $e) {
                $this->errors[] = 'خطا در ایجاد کاربر مدیر: ' . $e->getMessage();
            }
        }
    }
    
    /**
     * پایان نصب
     */
    private function finish() {
        // حذف متغیرهای نصب
        unset(
            $_SESSION['install_step'],
            $_SESSION['requirements_checked'],
            $_SESSION['db_config'],
            $_SESSION['db_connected'],
            $_SESSION['tables_created'],
            $_SESSION['admin_created']
        );
        
        // ایجاد فایل .htaccess
        $htaccess = $this->generateHtaccess();
        file_put_contents(BASEPATH . '/.htaccess', $htaccess);
        
        $this->success[] = 'نصب برنامه با موفقیت به پایان رسید.';
    }
    
    /**
     * تولید محتوای فایل کانفیگ
     */
    private function generateConfig($host, $name, $user, $pass) {
        return '<?php
/**
 * تنظیمات اصلی برنامه
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined(\'BASEPATH\')) {
    exit(\'No direct script access allowed\');
}

// تنظیم منطقه زمانی
date_default_timezone_set(\'Asia/Tehran\');

// نمایش خطاها در محیط توسعه
if ($_SERVER[\'SERVER_NAME\'] === \'localhost\' || $_SERVER[\'SERVER_NAME\'] === \'www.localhost\') {
    ini_set(\'display_errors\', 1);
    ini_set(\'display_startup_errors\', 1);
    error_reporting(E_ALL);
} else {
    ini_set(\'display_errors\', 0);
    error_reporting(0);
}

// تنظیمات پایه سایت
define(\'SITE_NAME\', \'حسابینو\');
define(\'SITE_DESC\', \'سیستم حسابداری آنلاین\');
define(\'SITE_URL\', \'http://\' . $_SERVER[\'HTTP_HOST\'] . \'/hesabino/\');
define(\'ADMIN_EMAIL\', \'admin@hesabino.com\');

// تنظیمات دیتابیس
define(\'DB_HOST\', \'' . $host . '\');
define(\'DB_NAME\', \'' . $name . '\');
define(\'DB_USER\', \'' . $user . '\');
define(\'DB_PASS\', \'' . $pass . '\');
define(\'DB_CHARSET\', \'utf8mb4\');
define(\'DB_PREFIX\', \'hb_\');

// تنظیمات امنیتی
define(\'HASH_COST\', 12);
define(\'SESSION_LIFETIME\', 7200);
define(\'CSRF_EXPIRY\', 7200);
define(\'REMEMBER_COOKIE_NAME\', \'hesabino_remember\');
define(\'REMEMBER_COOKIE_EXPIRY\', 2592000);

// تنظیمات آپلود
define(\'UPLOAD_PATH\', BASEPATH . \'/uploads/\');
define(\'ALLOWED_TYPES\', [
    \'image/jpeg\',
    \'image/png\',
    \'image/gif\',
    \'application/pdf\',
    \'application/msword\',
    \'application/vnd.openxmlformats-officedocument.wordprocessingml.document\'
]);
define(\'MAX_UPLOAD_SIZE\', 10 * 1024 * 1024);

// تنظیمات نمایش
define(\'ITEMS_PER_PAGE\', 20);
define(\'DATE_FORMAT\', \'Y/m/d\');
define(\'TIME_FORMAT\', \'H:i:s\');
define(\'DATETIME_FORMAT\', \'Y/m/d H:i:s\');
define(\'THOUSAND_SEPARATOR\', \',\');
define(\'DECIMAL_SEPARATOR\', \'.\');

// تنظیمات ایمیل
define(\'MAIL_FROM\', \'noreply@hesabino.com\');
define(\'MAIL_FROM_NAME\', SITE_NAME);
define(\'MAIL_REPLY_TO\', \'support@hesabino.com\');

return true;';
    }
    
    /**
     * تولید محتوای فایل htaccess
     */
    private function generateHtaccess() {
        return '# Enable rewrite engine
RewriteEngine On

# Set the base directory
RewriteBase /hesabino/

# Prevent directory listing
Options -Indexes

# Set default character set
AddDefaultCharset UTF-8

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header set Referrer-Policy "same-origin"
</IfModule>

# Block access to sensitive files
<FilesMatch "^(\.htaccess|\.htpasswd|\.git|\.env|composer\.json|composer\.lock)">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Protect config directory
<IfModule mod_alias.c>
    RedirectMatch 403 ^/hesabino/config/.*$
</IfModule>

# URL Rewriting Rules
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]';
    }
    
    /**
     * نمایش قالب
     */
    private function render() {
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب حسابینو</title>
    
    <!-- فونت ایران‌سنس -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    
    <!-- بوت‌استرپ 5 - نسخه RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- فونت‌آوسام -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f8f9fa;
        }
        
        .installer-wrapper {
            max-width: 800px;
            margin: 50px auto;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 20px;
            position: relative;
            z-index: 1;
        }
        
        .step.active {
            background-color: #667eea;
            color: white;
        }
        
        .step.completed {
            background-color: #28a745;
            color: white;
        }
        
        .step-line {
            height: 3px;
            background-color: #e9ecef;
            flex-grow: 1;
            margin-top: 20px;
        }
        
        .step-line.completed {
            background-color: #28a745;
        }
        
        .installer-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="installer-wrapper">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="حسابینو" height="60">
            <h2 class="mt-3">نصب حسابینو</h2>
            <p class="text-muted">نسخه 1.0.0</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $this->step >= 1 ? 'completed' : ''; ?> <?php echo $this->step === 1 ? 'active' : ''; ?>">1</div>
            <div class="step-line <?php echo $this->step > 1 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $this->step >= 2 ? 'completed' : ''; ?> <?php echo $this->step === 2 ? 'active' : ''; ?>">2</div>
            <div class="step-line <?php echo $this->step > 2 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $this->step >= 3 ? 'completed' : ''; ?> <?php echo $this->step === 3 ? 'active' : ''; ?>">3</div>
            <div class="step-line <?php echo $this->step > 3 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $this->step >= 4 ? 'completed' : ''; ?> <?php echo $this->step === 4 ? 'active' : ''; ?>">4</div>
            <div class="step-line <?php echo $this->step > 4 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $this->step >= 5 ? 'completed' : ''; ?> <?php echo $this->step === 5 ? 'active' : ''; ?>">5</div>
        </div>
        
        <div class="installer-card">
            <?php if (!empty($this->errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($this->errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($this->success)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <?php foreach ($this->success as $message): ?>
                            <li><?php echo $message; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php switch ($this->step): 
                case 1: // بررسی نیازمندی‌ها ?>
                    <h4 class="mb-4">بررسی نیازمندی‌ها</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>نسخه PHP</td>
                                    <td><?php echo PHP_VERSION; ?></td>
                                    <td>
                                        <?php if (version_compare(PHP_VERSION, '8.0.0', '>=')): ?>
                                            <i class="fas fa-check text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                                $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'gd'];
                                foreach ($extensions as $ext):
                                ?>
                                <tr>
                                    <td>افزونه <?php echo $ext; ?></td>
                                    <td><?php echo extension_loaded($ext) ? 'نصب شده' : 'نصب نشده'; ?></td>
                                    <td>
                                        <?php if (extension_loaded($ext)): ?>
                                            <i class="fas fa-check text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php
                                $paths = ['/config', '/uploads', '/cache', '/logs'];
                                foreach ($paths as $path):
                                    $fullPath = BASEPATH . $path;
                                ?>
                                <tr>
                                    <td>دسترسی نوشتن <?php echo $path; ?></td>
                                    <td><?php echo is_writable($fullPath) ? 'قابل نوشتن' : 'غیرقابل نوشتن'; ?></td>
                                    <td>
                                        <?php if (is_writable($fullPath)): ?>
                                            <i class="fas fa-check text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($this->errors)): ?>
                        <div class="text-end">
                            <a href="?step=2" class="btn btn-primary">
                                مرحله بعد 
                                <i class="fas fa-arrow-left me-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-end">
                            <a href="?step=1" class="btn btn-warning">
                                بررسی مجدد
                                <i class="fas fa-sync-alt me-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                <?php break;
                
                case 2: // تنظیم دیتابیس ?>
                    <h4 class="mb-4">تنظیمات دیتابیس</h4>
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="db_host" class="form-label">آدرس هاست <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_name" class="form-label">نام دیتابیس <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_name" name="db_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_user" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_user" name="db_user" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_pass" class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass">
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                مرحله بعد
                                <i class="fas fa-arrow-left me-1"></i>
                            </button>
                        </div>
                    </form>
                    
                <?php break;
                
                case 3: // ایجاد جداول ?>
                    <h4 class="mb-4">ایجاد جداول دیتابیس</h4>
                    <p>در حال ایجاد جداول مورد نیاز...</p>
                    <div class="progress mb-4">
                        <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                    </div>
                    
                    <script>
                        // ارسال خودکار به مرحله بعد
                        window.location.href = '?step=3';
                    </script>
                    
                <?php break;
                
                case 4: // ایجاد مدیر ?>
                    <h4 class="mb-4">ایجاد کاربر مدیر</h4>
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">ایمیل <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">رمز عبور <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">تکرار رمز عبور <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                مرحله بعد
                                <i class="fas fa-arrow-left me-1"></i>
                            </button>
                        </div>
                    </form>
                    
                <?php break;
                
                case 5: // پایان نصب ?>
                    <h4 class="mb-4">پایان نصب</h4>
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5>تبریک!</h5>
                        <p>نصب برنامه با موفقیت به پایان رسید.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            لطفاً پوشه install را حذف کنید.
                        </div>
                        <a href="../" class="btn btn-primary">
                            ورود به برنامه
                            <i class="fas fa-arrow-left me-1"></i>
                        </a>
                    </div>
                    
                <?php break;
                
            endswitch; ?>
        </div>
    </div>
    
    <!-- اسکریپت‌های مورد نیاز -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // اعتبارسنجی فرم‌ها
        (function() {
            'use strict';
            
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
<?php
    }
}

// اجرای نصب کننده
$installer = new Installer();
$installer->run();