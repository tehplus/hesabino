<?php
/**
 * فایل اصلی برنامه - نقطه ورودی تمام درخواست‌ها
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// تعریف مسیر پایه
define('BASEPATH', __DIR__);

// بررسی نصب بودن برنامه
if (!file_exists(BASEPATH . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// لود کردن تنظیمات
require_once BASEPATH . '/config/config.php';

// تنظیم error reporting براساس محیط
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === 'www.localhost') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// تنظیمات session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // در حالت توسعه 0 و در حالت تولید 1
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);


// لود کردن فایل‌های مورد نیاز
require_once BASEPATH . '/includes/functions.php';
require_once BASEPATH . '/includes/db.php';
require_once BASEPATH . '/includes/auth.php';


session_start();

// تنظیم session lifetime

session_set_cookie_params(SESSION_LIFETIME);

// دریافت مسیر درخواستی
$route = $_GET['route'] ?? 'home';
$route = rtrim($route, '/');

// مسیریابی
$routes = [
    // صفحات عمومی
    'home' => ['file' => 'pages/home.php', 'auth' => false],
    'login' => ['file' => 'pages/login.php', 'auth' => false],
    'register' => ['file' => 'pages/register.php', 'auth' => false],
    'forgot-password' => ['file' => 'pages/forgot-password.php', 'auth' => false],
    'reset-password' => ['file' => 'pages/reset-password.php', 'auth' => false],
    'verify-email' => ['file' => 'pages/verify-email.php', 'auth' => false],
    'logout' => ['file' => 'pages/logout.php', 'auth' => true],
    
    // داشبورد و پروفایل
    'dashboard' => ['file' => 'pages/dashboard.php', 'auth' => true],
    'profile' => ['file' => 'pages/profile.php', 'auth' => true],
    'settings' => ['file' => 'pages/settings.php', 'auth' => true],
    
    // مدیریت محصولات
    'products' => ['file' => 'pages/products/index.php', 'auth' => true],
    'products/create' => ['file' => 'pages/products/create.php', 'auth' => true],
    'products/edit' => ['file' => 'pages/products/edit.php', 'auth' => true],
    'products/delete' => ['file' => 'pages/products/delete.php', 'auth' => true],
    
    // مدیریت مشتریان
    'customers' => ['file' => 'pages/customers/index.php', 'auth' => true],
    'customers/create' => ['file' => 'pages/customers/create.php', 'auth' => true],
    'customers/edit' => ['file' => 'pages/customers/edit.php', 'auth' => true],
    'customers/delete' => ['file' => 'pages/customers/delete.php', 'auth' => true],
    
    // مدیریت فاکتورها
    'invoices' => ['file' => 'pages/invoices/index.php', 'auth' => true],
    'invoices/create' => ['file' => 'pages/invoices/create.php', 'auth' => true],
    'invoices/edit' => ['file' => 'pages/invoices/edit.php', 'auth' => true],
    'invoices/delete' => ['file' => 'pages/invoices/delete.php', 'auth' => true],
    'invoices/print' => ['file' => 'pages/invoices/print.php', 'auth' => true],
    
    // گزارش‌ها
    'reports/sales' => ['file' => 'pages/reports/sales.php', 'auth' => true],
    'reports/customers' => ['file' => 'pages/reports/customers.php', 'auth' => true],
    'reports/products' => ['file' => 'pages/reports/products.php', 'auth' => true],
    
    // پنل مدیریت
    'admin' => ['file' => 'pages/admin/index.php', 'auth' => true, 'admin' => true],
    'admin/users' => ['file' => 'pages/admin/users.php', 'auth' => true, 'admin' => true],
    'admin/settings' => ['file' => 'pages/admin/settings.php', 'auth' => true, 'admin' => true],
    
    // API endpoints
    'api/auth' => ['file' => 'api/auth.php', 'auth' => false],
    'api/users' => ['file' => 'api/users.php', 'auth' => true],
    'api/products' => ['file' => 'api/products.php', 'auth' => true],
    'api/customers' => ['file' => 'api/customers.php', 'auth' => true],
    'api/invoices' => ['file' => 'api/invoices.php', 'auth' => true],
];

// بررسی وجود مسیر
if (!isset($routes[$route])) {
    http_response_code(404);
    require_once BASEPATH . '/pages/errors/404.php';
    exit;
}

// دریافت اطلاعات مسیر
$route_info = $routes[$route];
$auth = Auth::getInstance();

// بررسی نیاز به احراز هویت
if ($route_info['auth'] && !$auth->isLoggedIn()) {
    $_SESSION['redirect_url'] = $route;
    header('Location: ' . SITE_URL . 'login');
    exit;
}

// بررسی دسترسی ادمین
if (isset($route_info['admin']) && $route_info['admin'] && !$auth->hasPermission('admin')) {
    http_response_code(403);
    require_once BASEPATH . '/pages/errors/403.php';
    exit;
}

// اجرای فایل مربوطه
require_once BASEPATH . '/' . $route_info['file'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - سیستم حسابداری آنلاین</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="سیستم حسابداری آنلاین برای کسب و کارهای کوچک و متوسط">
    <meta name="keywords" content="حسابداری، نرم افزار حسابداری، حسابداری آنلاین، مدیریت مالی">
    
    <!-- فونت‌های ایران‌سنس -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    
    <!-- بوت‌استرپ 5 - نسخه RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- فونت‌آوسام -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <!-- AOS - Animate On Scroll -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Swiper -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    
    <!-- استایل‌های سفارشی -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --light-color: #f8f9fa;
            --dark-color: #2d3748;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            display: block;
        }

        /* نوار ناوبری */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .navbar-brand img {
            height: 40px;
        }

        /* بخش هدر */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('assets/images/pattern.svg') repeat;
            opacity: 0.1;
        }

        /* بخش ویژگی‌ها */
        .features-section {
            padding: 100px 0;
            background: var(--light-color);
        }

        .feature-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }

        /* بخش قیمت‌گذاری */
        .pricing-section {
            padding: 100px 0;
        }

        .price-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .price-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .price-card.popular {
            border: 2px solid var(--primary-color);
        }

        /* بخش نظرات مشتریان */
        .testimonials-section {
            padding: 100px 0;
            background: var(--light-color);
        }

        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px;
        }

        .client-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* بخش تماس */
        .contact-section {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .contact-form {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        /* فوتر */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 50px 0;
        }

        /* دکمه‌ها */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
        }

        .btn-outline-light {
            border-radius: 30px;
            padding: 12px 30px;
        }

        /* انیمیشن‌ها */
        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- نوار ناوبری -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">ویژگی‌ها</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">تعرفه‌ها</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">نظرات مشتریان</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">تماس با ما</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard" class="btn btn-primary me-2">
                            <i class="fas fa-tachometer-alt me-1"></i>
                            داشبورد
                        </a>
                        <a href="logout" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i>
                            خروج
                        </a>
                    <?php else: ?>
                        <a href="login" class="btn btn-primary me-2">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            ورود
                        </a>
                        <a href="register" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-1"></i>
                            ثبت‌نام
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- بخش هدر -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="display-4 fw-bold mb-4">مدیریت هوشمند امور مالی کسب و کار شما</h1>
                    <p class="lead mb-4">با سیستم حسابداری <?php echo SITE_NAME; ?>، به راحتی امور مالی کسب و کار خود را مدیریت کنید. صدور فاکتور، ثبت هزینه‌ها، گزارش‌گیری و بسیاری امکانات دیگر.</p>
                    <div class="d-flex gap-3">
                        <a href="register" class="btn btn-light">
                            <i class="fas fa-rocket me-1"></i>
                            شروع رایگان
                        </a>
                        <a href="#features" class="btn btn-outline-light">
                            <i class="fas fa-info-circle me-1"></i>
                            اطلاعات بیشتر
                        </a>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <img src="assets/images/hero-image.png" alt="حسابداری آنلاین" class="img-fluid floating">
                </div>
            </div>
        </div>
    </section>

    <!-- بخش ویژگی‌ها -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">ویژگی‌های سیستم</h2>
                <p class="text-muted">امکانات پیشرفته برای مدیریت بهتر کسب و کار شما</p>
            </div>
            <div class="row g-4">
                <!-- ویژگی 1 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h4>صدور فاکتور</h4>
                            <p>صدور آسان و سریع فاکتور با قالب‌های متنوع و امکان ارسال مستقیم به مشتری</p>
                        </div>
                    </div>
                </div>
                
                <!-- ویژگی 2 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4>گزارش‌های مالی</h4>
                            <p>گزارش‌های متنوع و کاربردی برای تحلیل عملکرد مالی کسب و کار</p>
                        </div>
                    </div>
                </div>
                
                <!-- ویژگی 3 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <h4>مدیریت موجودی</h4>
                            <p>کنترل موجودی انبار و محصولات با سیستم هشدار خودکار</p>
                        </div>
                    </div>
                </div>
                
                <!-- ویژگی 4 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4>مدیریت مشتریان</h4>
                            <p>ثبت و پیگیری اطلاعات مشتریان و سوابق خرید آنها</p>
                        </div>
                    </div>
                </div>
                
                <!-- ویژگی 5 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h4>حسابداری دوبل</h4>
                            <p>سیستم حسابداری دوطرفه با ثبت خودکار سند حسابداری</p>
                        </div>
                    </div>
                </div>
                
                <!-- ویژگی 6 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4>نسخه موبایل</h4>
                            <p>دسترسی به سیستم از طریق گوشی موبایل و تبلت</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- بخش قیمت‌گذاری -->
    <section id="pricing" class="pricing-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">تعرفه‌های اشتراک</h2>
                <p class="text-muted">پلن متناسب با نیاز خود را انتخاب کنید</p>
            </div>
            <div class="row g-4">
                <!-- پلن پایه -->
                <div class="col-lg-4" data-aos="fade-up">
                    <div class="card price-card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title">پایه</h3>
                            <div class="py-4">
                                <h1 class="display-4 fw-bold">رایگان</h1>
                                <p class="text-muted">برای همیشه</p>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>صدور 10 فاکتور در ماه</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>گزارش‌های پایه</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>پشتیبانی ایمیلی</li>
                                <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>حسابداری دوبل</li>
                                <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>اپلیکیشن موبایل</li>
                            </ul>
                            <a href="register" class="btn btn-outline-primary w-100">شروع کنید</a>
                        </div>
                    </div>
                </div>

                <!-- پلن حرفه‌ای -->
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card price-card popular h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title">حرفه‌ای</h3>
                            <div class="py-4">
                                <h1 class="display-4 fw-bold">199,000</h1>
                                <p class="text-muted">تومان / ماهانه</p>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>صدور نامحدود فاکتور</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>تمام گزارش‌ها</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>پشتیبانی تلفنی</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>حسابداری دوبل</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>اپلیکیشن موبایل</li>
                            </ul>
                            <a href="register" class="btn btn-primary w-100">انتخاب پلن</a>
                        </div>
                    </div>
                </div>

                <!-- پلن سازمانی -->
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card price-card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title">سازمانی</h3>
                            <div class="py-4">
                                <h1 class="display-4 fw-bold">تماس بگیرید</h1>
                                <p class="text-muted">قیمت توافقی</p>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>تمام امکانات حرفه‌ای</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>شخصی‌سازی کامل</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>پشتیبانی اختصاصی</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>آموزش حضوری</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>نصب در شبکه داخلی</li>
                            </ul>
                            <a href="#contact" class="btn btn-outline-primary w-100">تماس با ما</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- بخش نظرات مشتریان -->
    <section id="testimonials" class="testimonials-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">نظرات مشتریان</h2>
                <p class="text-muted">آنچه مشتریان ما درباره <?php echo SITE_NAME; ?> می‌گویند</p>
            </div>
            <div class="swiper testimonials-slider">
                <div class="swiper-wrapper">
                    <!-- نظر 1 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/client1.jpg" alt="Client 1" class="client-image me-3">
                                <div>
                                    <h5 class="mb-1">علی محمدی</h5>
                                    <p class="text-muted mb-0">مدیر فروشگاه زنجیره‌ای</p>
                                </div>
                            </div>
                            <p class="mb-0">استفاده از این سیستم حسابداری باعث شد مدیریت مالی فروشگاه‌های ما بسیار راحت‌تر شود. گزارش‌های دقیق و پشتیبانی عالی.</p>
                        </div>
                    </div>

                    <!-- نظر 2 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/client2.jpg" alt="Client 2" class="client-image me-3">
                                <div>
                                    <h5 class="mb-1">مریم حسینی</h5>
                                    <p class="text-muted mb-0">صاحب استارتاپ</p>
                                </div>
                            </div>
                            <p class="mb-0">رابط کاربری ساده و امکانات کامل این نرم‌افزار کمک زیادی به رشد کسب و کار ما کرد. واقعاً راضی هستیم.</p>
                        </div>
                    </div>

                    <!-- نظر 3 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/client3.jpg" alt="Client 3" class="client-image me-3">
                                <div>
                                    <h5 class="mb-1">رضا کریمی</h5>
                                    <p class="text-muted mb-0">حسابدار شرکت</p>
                                </div>
                            </div>
                            <p class="mb-0">به عنوان یک حسابدار حرفه‌ای، این بهترین نرم‌افزاری است که تا به حال استفاده کرده‌ام. دقیق، سریع و کامل.</p>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- بخش تماس -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-4">تماس با ما</h2>
                    <p class="mb-4">برای دریافت مشاوره رایگان و اطلاعات بیشتر با ما در تماس باشید.</p>
                    <div class="d-flex mb-3">
                        <i class="fas fa-map-marker-alt fa-2x me-3"></i>
                        <div>
                            <h5>آدرس</h5>
                            <p>تهران، خیابان ولیعصر، ساختمان نور، طبقه 4</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <i class="fas fa-phone fa-2x me-3"></i>
                        <div>
                            <h5>تلفن تماس</h5>
                            <p>021-12345678</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <i class="fas fa-envelope fa-2x me-3"></i>
                        <div>
                            <h5>ایمیل</h5>
                            <p>info@hesabino.com</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="contact-form">
                        <form id="contactForm">
                            <div class="mb-3">
                                <label for="name" class="form-label text-dark">نام و نام خانوادگی</label>
                                <input type="text" class="form-control" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label text-dark">ایمیل</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label text-dark">موضوع</label>
                                <input type="text" class="form-control" id="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label text-dark">پیام</label>
                                <textarea class="form-control" id="message" rows="4" required></textarea>
                            </div>
                                                        <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>ارسال پیام
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- فوتر -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <img src="assets/images/logo-light.png" alt="<?php echo SITE_NAME; ?>" class="mb-4" style="height: 40px;">
                    <p>سیستم حسابداری آنلاین برای مدیریت هوشمند امور مالی کسب و کارها</p>
                    <div class="mt-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-telegram fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="mb-4">دسترسی سریع</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features" class="text-white text-decoration-none">امکانات</a></li>
                        <li class="mb-2"><a href="#pricing" class="text-white text-decoration-none">تعرفه‌ها</a></li>
                        <li class="mb-2"><a href="#testimonials" class="text-white text-decoration-none">نظرات مشتریان</a></li>
                        <li class="mb-2"><a href="#contact" class="text-white text-decoration-none">تماس با ما</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="mb-4">خدمات ما</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">حسابداری شخصی</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">حسابداری شرکتی</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">مشاوره مالیاتی</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">آموزش حسابداری</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="mb-4">خبرنامه</h5>
                    <p>برای دریافت آخرین اخبار و تخفیف‌ها عضو خبرنامه ما شوید</p>
                    <form class="mt-4">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="ایمیل خود را وارد کنید">
                            <button class="btn btn-primary" type="submit">عضویت</button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="mt-5 mb-4 border-light">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. تمامی حقوق محفوظ است.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <a href="#" class="text-white text-decoration-none me-3">قوانین و مقررات</a>
                    <a href="#" class="text-white text-decoration-none">حریم خصوصی</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- دکمه بازگشت به بالا -->
    <button id="backToTop" class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4" style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- اسکریپت‌های مورد نیاز -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        // راه‌اندازی AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // راه‌اندازی Swiper برای نظرات مشتریان
        new Swiper('.testimonials-slider', {
            slidesPerView: 1,
            spaceBetween: 30,
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            breakpoints: {
                768: {
                    slidesPerView: 2
                },
                1024: {
                    slidesPerView: 3
                }
            },
            autoplay: {
                delay: 5000
            }
        });

        // دکمه بازگشت به بالا
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 200) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        });
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // اسکرول نرم برای لینک‌های ناوبری
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // فرم تماس
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // نمایش loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>در حال ارسال...';
            submitBtn.disabled = true;

            try {
                // ارسال فرم با fetch
                const formData = new FormData(this);
                const response = await fetch('includes/send-contact.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    // نمایش پیام موفقیت
                    Swal.fire({
                        icon: 'success',
                        title: 'پیام شما با موفقیت ارسال شد',
                        text: 'در اسرع وقت با شما تماس خواهیم گرفت.',
                        confirmButtonText: 'باشه'
                    });
                    this.reset();
                } else {
                    throw new Error('خطا در ارسال پیام');
                }
            } catch (error) {
                // نمایش پیام خطا
                Swal.fire({
                    icon: 'error',
                    title: 'خطا',
                    text: 'متأسفانه مشکلی در ارسال پیام پیش آمده. لطفاً مجدداً تلاش کنید.',
                    confirmButtonText: 'باشه'
                });
            } finally {
                // بازگرداندن دکمه به حالت اولیه
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // اعتبارسنجی فرم خبرنامه
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            if (email) {
                // ارسال ایمیل به سرور
                // اینجا کد مربوط به ثبت ایمیل در خبرنامه قرار می‌گیرد
                Swal.fire({
                    icon: 'success',
                    title: 'تبریک!',
                    text: 'ایمیل شما با موفقیت در خبرنامه ثبت شد.',
                    confirmButtonText: 'باشه'
                });
                this.reset();
            }
        });
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>