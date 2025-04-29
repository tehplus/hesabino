// تابع کمکی برای بررسی لود شدن کتابخانه‌ها
function checkDependencies() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        return false;
    }
    
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 is not loaded!');
        return false;
    }
    
    if (typeof Dropzone === 'undefined') {
        console.error('Dropzone is not loaded!');
        return false;
    }
    
    return true;
}

// تابع اصلی برای راه‌اندازی
function initializeComponents() {
    if (!checkDependencies()) return;
    
    // راه‌اندازی Select2
    $('.select2-element').select2({
        width: '100%',
        placeholder: 'لطفاً انتخاب کنید'
    });
    
    // راه‌اندازی Dropzone
    Dropzone.autoDiscover = false;
    new Dropzone("#upload-form", {
        url: "/upload",
        dictDefaultMessage: "فایل‌ها را اینجا رها کنید",
        dictFallbackMessage: "مرورگر شما از آپلود با درگ و دراپ پشتیبانی نمی‌کند"
    });
}

// اجرای کد پس از لود کامل صفحه
$(document).ready(function() {
    try {
        initializeComponents();
    } catch (error) {
        console.error('خطا در راه‌اندازی کامپوننت‌ها:', error);
    }
});