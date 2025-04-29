<?php
/**
 * قالب هدر
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// دریافت نمونه کلاس Auth
$auth = Auth::getInstance();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo SITE_DESC; ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    
    <!-- فونت‌های ایران‌سنس -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    
    <!-- بوت‌استرپ 5 - نسخه RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- فونت‌آوسام -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <!-- استایل‌های سفارشی -->
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
    
    <!-- استایل‌های اختصاصی صفحه -->
    <?php if (isset($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link href="<?php echo asset('css/' . $style); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- نوار ناوبری -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url(); ?>">
                <img src="<?php echo asset('images/logo.png'); ?>" alt="<?php echo SITE_NAME; ?>">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('dashboard'); ?>">داشبورد</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('products'); ?>">محصولات</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('customers'); ?>">مشتریان</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('invoices'); ?>">فاکتورها</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('reports'); ?>">گزارش‌ها</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#features">ویژگی‌ها</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#pricing">تعرفه‌ها</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">تماس با ما</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex">
                    <?php if ($auth->isLoggedIn()): ?>
                        <div class="dropdown">
                            <button class="btn btn-link nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?php $user = $auth->getUser(); ?>
                                <img src="<?php echo checkImage('uploads/avatars/' . $user['avatar']); ?>" 
                                     alt="<?php echo $user['full_name']; ?>" 
                                     class="rounded-circle me-1" 
                                     style="width: 32px; height: 32px; object-fit: cover;">
                                <?php echo $user['full_name']; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('profile'); ?>">
                                        <i class="fas fa-user me-1"></i>
                                        پروفایل
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('settings'); ?>">
                                        <i class="fas fa-cog me-1"></i>
                                        تنظیمات
                                    </a>
                                </li>
                                <?php if ($auth->hasRole('admin')): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('admin'); ?>">
                                            <i class="fas fa-users-cog me-1"></i>
                                            پنل مدیریت
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('logout'); ?>">
                                        <i class="fas fa-sign-out-alt me-1"></i>
                                        خروج
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo url('login'); ?>" class="btn btn-primary me-2">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            ورود
                        </a>
                        <a href="<?php echo url('register'); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-1"></i>
                            ثبت‌نام
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- پیام‌های فلش -->
    <?php
    if ($flashMessage = getFlashMessage()) {
        echo $flashMessage;
    }
    ?>

    <!-- محتوای اصلی -->
    <main>