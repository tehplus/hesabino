/**
 * اسکریپت‌های اصلی برنامه
 */

// اجرای کد پس از لود شدن صفحه
document.addEventListener('DOMContentLoaded', function() {
    // فعال‌سازی تولتیپ‌ها
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // فعال‌سازی پاپ‌اورها
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // اعتبارسنجی فرم‌ها
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

    // نمایش/مخفی کردن رمز عبور
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function(e) {
            const input = document.querySelector(this.dataset.target);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // بررسی قدرت رمز عبور
    document.querySelectorAll('.password-strength').forEach(function(input) {
        input.addEventListener('input', function() {
            const value = this.value;
            const progressBar = document.querySelector(this.dataset.strengthBar);
            const feedback = document.querySelector(this.dataset.strengthText);
            
            if (!progressBar || !feedback) return;
            
            const strength = checkPasswordStrength(value);
            
            progressBar.style.width = strength.score * 25 + '%';
            progressBar.className = 'progress-bar ' + strength.class;
            feedback.textContent = strength.message;
        });
    });

    // دکمه برگشت به بالا
    var backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 100) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });

        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // انیمیشن‌های ورود عناصر
    AOS.init({
        duration: 800,
        once: true
    });
});

/**
 * بررسی قدرت رمز عبور
 * 
 * @param {string} password رمز عبور
 * @returns {object} نتیجه بررسی
 */
function checkPasswordStrength(password) {
    let score = 0;
    let message = '';
    let className = 'bg-danger';

    // طول رمز عبور
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;

    // حروف بزرگ و کوچک
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;

    // اعداد و کاراکترهای خاص
    if (/\d/.test(password)) score++;
    if (/[!@#$%^&*]/.test(password)) score++;

    // تعیین پیام و کلاس
    switch (score) {
        case 0:
            message = 'خیلی ضعیف';
            className = 'bg-danger';
            break;
        case 1:
            message = 'ضعیف';
            className = 'bg-warning';
            break;
        case 2:
            message = 'متوسط';
            className = 'bg-info';
            break;
        case 3:
            message = 'قوی';
            className = 'bg-primary';
            break;
        case 4:
        case 5:
            message = 'خیلی قوی';
            className = 'bg-success';
            break;
    }

    return {
        score: score,
        message: message,
        class: className
    };
}

/**
 * نمایش پیام با SweetAlert2
 * 
 * @param {string} type نوع پیام (success/error/warning/info)
 * @param {string} title عنوان پیام
 * @param {string} text متن پیام
 */
function showMessage(type, title, text) {
    Swal.fire({
        icon: type,
        title: title,
        text: text,
        confirmButtonText: 'باشه'
    });
}

/**
 * ارسال فرم با AJAX
 * 
 * @param {string} url آدرس API
 * @param {object} data داده‌های فرم
 * @param {function} callback تابع callback
 */
function submitForm(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        showMessage('error', 'خطا', 'مشکلی در ارتباط با سرور پیش آمده است.');
        console.error('Error:', error);
    });
}

/**
 * تبدیل اعداد انگلیسی به فارسی
 * 
 * @param {string} str رشته ورودی
 * @returns {string} رشته با اعداد فارسی
 */
function toPersianNumbers(str) {
    const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str.toString().replace(/[0-9]/g, function(w) {
        return persianNumbers[+w];
    });
}

/**
 * تبدیل اعداد فارسی به انگلیسی
 * 
 * @param {string} str رشته ورودی
 * @returns {string} رشته با اعداد انگلیسی
 */
function toEnglishNumbers(str) {
    return str.toString().replace(/[۰-۹]/g, function(w) {
        return w.charCodeAt(0) - '۰'.charCodeAt(0);
    });
}

/**
 * فرمت‌بندی قیمت
 * 
 * @param {number} price قیمت
 * @param {boolean} showCurrency نمایش واحد پول
 * @returns {string} قیمت فرمت‌بندی شده
 */
function formatPrice(price, showCurrency = true) {
    price = parseInt(price).toString();
    price = price.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return showCurrency ? price + ' تومان' : price;
}

/**
 * نمایش لودینگ روی دکمه
 * 
 * @param {HTMLElement} button دکمه
 * @param {boolean} loading وضعیت لودینگ
 */
function buttonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>در حال پردازش...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText;
    }
}