<?php
/**
 * صفحه ورود به سیستم
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// بررسی دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// اگر کاربر لاگین کرده باشد، ریدایرکت به داشبورد
if ($auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . 'dashboard');
    exit;
}

$error = '';
$success = '';

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'خطا در اعتبارسنجی فرم. لطفاً مجدداً تلاش کنید.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if ($auth->login($username, $password, $remember)) {
            $redirect = $_SESSION['redirect_url'] ?? 'dashboard';
            unset($_SESSION['redirect_url']);
            header('Location: ' . SITE_URL . $redirect);
            exit;
        } else {
            $error = implode('<br>', $auth->getErrors());
        }
    }
}

// ایجاد توکن CSRF جدید
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// بارگذاری قالب
require_once BASEPATH . '/templates/header.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به <?php echo SITE_NAME; ?></title>
    
    <!-- فونت‌های ایران‌سنس -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    
    <!-- بوت‌استرپ 5 - نسخه RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    
    <!-- فونت‌آوسام -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- استایل‌های سفارشی -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --dark-color: #2d3748;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header img {
            width: 120px;
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid #e2e8f0;
            background-color: #fff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .input-group-text {
            border-radius: 8px;
            background-color: #fff;
            border: 1px solid #e2e8f0;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .social-login {
            margin-top: 2rem;
            text-align: center;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            color: #fff;
            text-decoration: none;
            transition: transform 0.2s;
        }

        .social-btn:hover {
            transform: scale(1.1);
            color: #fff;
        }

        .google-btn { background-color: #DB4437; }
        .linkedin-btn { background-color: #0077B5; }
        .github-btn { background-color: #333; }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .forgot-password:hover {
            color: var(--secondary-color);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        /* انیمیشن‌های ورودی */
        .fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container fade-in-up">
            <div class="login-header">
                <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>" class="img-fluid">
                <h4 class="mb-0">ورود به سیستم</h4>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show animate__animated animate__bounceIn" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">نام کاربری یا ایمیل</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               placeholder="نام کاربری یا ایمیل خود را وارد کنید">
                    </div>
                    <div class="invalid-feedback">
                        لطفاً نام کاربری یا ایمیل خود را وارد کنید
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">رمز عبور</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="رمز عبور خود را وارد کنید">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">
                        لطفاً رمز عبور خود را وارد کنید
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                    </div>
                    <a href="forgot-password" class="forgot-password">فراموشی رمز عبور؟</a>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>ورود
                    </button>
                </div>
            </form>

            <div class="social-login">
                <p class="text-muted mb-3">یا ورود از طریق:</p>
                <a href="auth/google" class="social-btn google-btn" title="ورود با گوگل">
                    <i class="fab fa-google"></i>
                </a>
                <a href="auth/linkedin" class="social-btn linkedin-btn" title="ورود با لینکدین">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="auth/github" class="social-btn github-btn" title="ورود با گیت‌هاب">
                    <i class="fab fa-github"></i>
                </a>
            </div>

            <div class="register-link">
                <p class="mb-0">
                    هنوز ثبت‌نام نکرده‌اید؟
                    <a href="register" class="ms-1 text-primary">ثبت‌نام کنید</a>
                </p>
            </div>
        </div>
    </div>

    <!-- اسکریپت‌های مورد نیاز -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // اعتبارسنجی فرم
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // نمایش/مخفی کردن رمز عبور
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // انیمیشن‌های ورودی
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.login-container');
            container.classList.add('animate__animated', 'animate__fadeIn');
        });

        // اعتبارسنجی پیشرفته رمز عبور
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.createElement('div');
            strengthMeter.className = 'password-strength mt-2';
            
            let strength = 0;
            const checks = {
                length: password.length >= 8,
                lower: /[a-z]/.test(password),
                upper: /[A-Z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            Object.values(checks).forEach(check => {
                if (check) strength += 20;
            });

            let color = '';
            let text = '';
            
            if (strength <= 20) {
                color = '#dc3545';
                text = 'خیلی ضعیف';
            } else if (strength <= 40) {
                color = '#ffc107';
                text = 'ضعیف';
            } else if (strength <= 60) {
                color = '#17a2b8';
                text = 'متوسط';
            } else if (strength <= 80) {
                color = '#28a745';
                text = 'قوی';
            } else {
                color = '#198754';
                text = 'بسیار قوی';
            }

            strengthMeter.style.color = color;
            strengthMeter.textContent = `قدرت رمز عبور: ${text}`;

            // حذف نشانگر قبلی اگر وجود داشته باشد
            const existingMeter = this.parentNode.querySelector('.password-strength');
            if (existingMeter) {
                existingMeter.remove();
            }

            // نمایش نشانگر جدید
            if (password.length > 0) {
                this.parentNode.appendChild(strengthMeter);
            }
        });
    </script>
</body>
</html>
<?php
// بارگذاری فوتر
require_once BASEPATH . '/templates/footer.php';