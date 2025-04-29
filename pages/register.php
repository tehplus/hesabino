<?php
/**
 * صفحه ثبت‌نام کاربران
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */
// اگر کاربر لاگین کرده باشد، ریدایرکت به داشبورد
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard');
    exit;
}
$auth = Auth::getInstance();
$error = '';
$success = '';
$formData = [];
// تنظیمات اولیه
define('BASEPATH', __DIR__);
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// شروع جلسه
session_start();





// پردازش فرم ثبت‌نام
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // بررسی CSRF توکن
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'خطا در اعتبارسنجی فرم. لطفاً مجدداً تلاش کنید.';
    } else {
        // ذخیره داده‌های فرم برای بازیابی در صورت خطا
        $formData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'full_name' => trim($_POST['full_name'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'terms' => isset($_POST['terms'])
        ];

        // بررسی پذیرش قوانین
        if (!$formData['terms']) {
            $error = 'برای ثبت‌نام باید قوانین و مقررات را بپذیرید.';
        } else {
            // ثبت‌نام کاربر
            if ($auth->register($formData)) {
                $success = 'ثبت‌نام با موفقیت انجام شد. لطفاً ایمیل خود را برای تأیید حساب کاربری بررسی کنید.';
                $formData = []; // پاک کردن داده‌های فرم
            } else {
                $error = implode('<br>', $auth->getErrors());
            }
        }
    }
}

// ایجاد توکن CSRF جدید
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام در <?php echo SITE_NAME; ?></title>
    
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
            padding: 40px 20px;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            padding: 2rem;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header img {
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

        .progress {
            height: 6px;
            border-radius: 3px;
            margin-top: 8px;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .requirement-item i {
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }

        .requirement-met {
            color: #198754;
        }

        .requirement-unmet {
            color: #dc3545;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .form-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            margin: 0 1rem;
            color: #666;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.5rem;
            font-weight: bold;
            color: #fff;
        }

        .step.active {
            color: var(--primary-color);
        }

        .step.active .step-number {
            background-color: var(--primary-color);
        }

        .step.completed .step-number {
            background-color: #198754;
        }

        @media (max-width: 768px) {
            .register-container {
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
        <div class="register-container fade-in-up">
            <div class="register-header">
                <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>" class="img-fluid">
                <h4 class="mb-0">ثبت‌نام در سیستم</h4>
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
                <div class="text-center">
                    <a href="login" class="btn btn-primary">ورود به سیستم</a>
                </div>
            <?php else: ?>
                <div class="form-steps mb-4">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-text">اطلاعات حساب</div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-text">اطلاعات شخصی</div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-text">تأیید نهایی</div>
                    </div>
                </div>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">نام کاربری</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required 
                                       minlength="3" maxlength="50" pattern="^[a-zA-Z][a-zA-Z0-9_.-]*$"
                                       value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>"
                                       placeholder="فقط حروف انگلیسی، اعداد و - . _">
                            </div>
                            <div class="invalid-feedback">
                                نام کاربری باید حداقل 3 کاراکتر و شامل حروف انگلیسی، اعداد و - . _ باشد
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">ایمیل</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                       placeholder="example@domain.com">
                            </div>
                            <div class="invalid-feedback">
                                لطفاً یک ایمیل معتبر وارد کنید
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">رمز عبور</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required
                                       minlength="8" placeholder="حداقل 8 کاراکتر">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="password-requirements mt-2">
                                <div class="requirement-item">
                                    <i class="fas fa-circle"></i>
                                    حداقل 8 کاراکتر
                                </div>
                                <div class="requirement-item">
                                    <i class="fas fa-circle"></i>
                                    حداقل یک حرف بزرگ انگلیسی
                                </div>
                                <div class="requirement-item">
                                    <i class="fas fa-circle"></i>
                                    حداقل یک حرف کوچک انگلیسی
                                </div>
                                <div class="requirement-item">
                                    <i class="fas fa-circle"></i>
                                    حداقل یک عدد
                                </div>
                                <div class="requirement-item">
                                    <i class="fas fa-circle"></i>
                                    حداقل یک کاراکتر خاص
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">تکرار رمز عبور</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                       required placeholder="رمز عبور را مجدداً وارد کنید">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                رمز عبور و تکرار آن باید یکسان باشند
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">نام و نام خانوادگی</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name" required
                                       value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>"
                                       placeholder="نام و نام خانوادگی خود را وارد کنید">
                            </div>
                            <div class="invalid-feedback">
                                لطفاً نام و نام خانوادگی خود را وارد کنید
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">شماره موبایل</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                <input type="tel" class="form-control" id="mobile" name="mobile"
                                       pattern="^09[0-9]{9}$"
                                       value="<?php echo htmlspecialchars($formData['mobile'] ?? ''); ?>"
                                       placeholder="09xxxxxxxxx">
                            </div>
                            <div class="invalid-feedback">
                                لطفاً یک شماره موبایل معتبر وارد کنید
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="company" class="form-label">نام شرکت یا کسب و کار</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="company" name="company"
                                   value="<?php echo htmlspecialchars($formData['company'] ?? ''); ?>"
                                   placeholder="اختیاری">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required
                                   <?php echo isset($formData['terms']) && $formData['terms'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="terms">
                                <a href="terms" target="_blank" class="text-decoration-none">قوانین و مقررات</a> را مطالعه کرده و می‌پذیرم
                            </label>
                            <div class="invalid-feedback">
                                برای ثبت‌نام باید قوانین و مقررات را بپذیرید
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>ثبت‌نام
                        </button>
                    </div>
                </form>

                <div class="login-link">
                    <p class="mb-0">
                        قبلاً ثبت‌نام کرده‌اید؟
                        <a href="login" class="ms-1 text-primary">وارد شوید</a>
                    </p>
                </div>
            <?php endif; ?>
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

        // توابع نمایش/مخفی کردن رمز عبور
        function togglePasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('togglePassword').addEventListener('click', () => {
            togglePasswordVisibility('password', 'togglePassword');
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', () => {
            togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
        });

        // بررسی قدرت رمز عبور و نمایش الزامات
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const progressBar = document.querySelector('.progress-bar');
        const requirements = document.querySelectorAll('.requirement-item i');

        password.addEventListener('input', function() {
            const value = this.value;
            
            // بررسی الزامات
            const checks = {
                length: value.length >= 8,
                upper: /[A-Z]/.test(value),
                lower: /[a-z]/.test(value),
                number: /\d/.test(value),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(value)
            };

            // بروزرسانی نشانگرهای الزامات
            Object.values(checks).forEach((check, index) => {
                requirements[index].className = check ? 
                    'fas fa-check-circle requirement-met' : 
                    'fas fa-times-circle requirement-unmet';
            });

            // محاسبه قدرت رمز عبور
            const strength = Object.values(checks).filter(Boolean).length * 20;
            progressBar.style.width = strength + '%';
            
            // تنظیم رنگ نوار پیشرفت
            if (strength <= 20) {
                progressBar.className = 'progress-bar bg-danger';
            } else if (strength <= 40) {
                progressBar.className = 'progress-bar bg-warning';
            } else if (strength <= 60) {
                progressBar.className = 'progress-bar bg-info';
            } else if (strength <= 80) {
                progressBar.className = 'progress-bar bg-primary';
            } else {
                progressBar.className = 'progress-bar bg-success';
            }
        });

        // بررسی تطابق رمز عبور
        confirmPassword.addEventListener('input', function() {
            if (this.value === password.value) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity('رمز عبور و تکرار آن مطابقت ندارند.');
            }
        });

        // بررسی فرمت شماره موبایل
        const mobile = document.getElementById('mobile');
        mobile.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substr(0, 11);
            if (this.value.length === 11 && !this.value.startsWith('09')) {
                this.setCustomValidity('شماره موبایل باید با 09 شروع شود.');
            } else if (this.value.length === 11) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity('شماره موبایل باید 11 رقم باشد.');
            }
        });

        // انیمیشن‌های ورودی
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.register-container');
            container.classList.add('animate__animated', 'animate__fadeIn');
        });

        // به‌روزرسانی مراحل فرم
        const formSteps = document.querySelectorAll('.step');
        const formInputs = document.querySelectorAll('input[required]');
        let currentStep = 0;

        function updateSteps() {
            const validInputs = Array.from(formInputs).filter(input => input.checkValidity());
            const progress = Math.floor((validInputs.length / formInputs.length) * 3);
            
            formSteps.forEach((step, index) => {
                if (index < progress) {
                    step.classList.add('completed');
                    step.querySelector('.step-number').innerHTML = '<i class="fas fa-check"></i>';
                } else {
                    step.classList.remove('completed');
                    step.querySelector('.step-number').textContent = index + 1;
                }
            });
        }

        formInputs.forEach(input => {
            input.addEventListener('change', updateSteps);
        });
    </script>
</body>
</html>