// اجرای کد پس از لود کامل صفحه
document.addEventListener('DOMContentLoaded', function() {
    // تنظیمات Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dir: 'rtl',
        language: {
            noResults: function() {
                return "نتیجه‌ای یافت نشد";
            }
        }
    });

    // تنظیمات Dropzone
    Dropzone.autoDiscover = false;
    const dropzone = new Dropzone("#productImages", {
        url: "upload.php",
        acceptedFiles: "image/*",
        maxFiles: 5,
        maxFilesize: 2, // مگابایت
        dictDefaultMessage: "تصاویر را اینجا بکشید و رها کنید یا کلیک کنید",
        dictFileTooBig: "حجم فایل بیش از حد مجاز است ({{filesize}}MB). حداکثر حجم مجاز: {{maxFilesize}}MB.",
        dictInvalidFileType: "این نوع فایل مجاز نیست.",
        dictMaxFilesExceeded: "نمی‌توانید بیش از {{maxFiles}} فایل آپلود کنید.",
        dictRemoveFile: "حذف",
        dictCancelUpload: "لغو آپلود",
        addRemoveLinks: true,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        init: function() {
            this.on("success", function(file, response) {
                const uploadedFilesInput = document.getElementById('uploadedFiles');
                const currentFiles = uploadedFilesInput.value ? uploadedFilesInput.value.split(',') : [];
                currentFiles.push(response.filePath);
                uploadedFilesInput.value = currentFiles.join(',');
                
                // اضافه کردن شناسه فایل به عنوان data attribute
                file.previewElement.setAttribute('data-file-path', response.filePath);
            });

            this.on("removedfile", function(file) {
                const filePath = file.previewElement.getAttribute('data-file-path');
                if (filePath) {
                    removeImageFromServer(filePath);
                }
            });
        }
    });

    // فرم اعتبارسنجی
    const form = document.getElementById('editProductForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }

    // مدیریت فیلدهای مالیات
    const salesTaxCheckbox = document.getElementById('salesTax');
    const purchaseTaxCheckbox = document.getElementById('purchaseTax');
    
    if (salesTaxCheckbox) {
        salesTaxCheckbox.addEventListener('change', function() {
            const salesTaxInput = document.querySelector('input[name="sales_tax"]');
            if (salesTaxInput) {
                salesTaxInput.disabled = !this.checked;
                if (!this.checked) salesTaxInput.value = '0';
            }
        });
    }

    if (purchaseTaxCheckbox) {
        purchaseTaxCheckbox.addEventListener('change', function() {
            const purchaseTaxInput = document.querySelector('input[name="purchase_tax"]');
            if (purchaseTaxInput) {
                purchaseTaxInput.disabled = !this.checked;
                if (!this.checked) purchaseTaxInput.value = '0';
            }
        });
    }
});

// تابع تولید بارکد
function generateBarcode() {
    const timestamp = Date.now().toString();
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    const barcode = timestamp.slice(-10) + random;
    document.querySelector('input[name="barcode"]').value = barcode;
}

// تابع حذف تصویر
function removeImage(button, filePath) {
    if (confirm('آیا از حذف این تصویر اطمینان دارید؟')) {
        button.parentElement.remove();
        removeImageFromServer(filePath);
        
        // به‌روزرسانی لیست تصاویر در input مخفی
        const uploadedFilesInput = document.getElementById('uploadedFiles');
        const currentFiles = uploadedFilesInput.value ? uploadedFilesInput.value.split(',') : [];
        const updatedFiles = currentFiles.filter(file => file !== filePath);
        uploadedFilesInput.value = updatedFiles.join(',');
    }
}

// تابع حذف تصویر از سرور
function removeImageFromServer(filePath) {
    fetch('delete-image.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({ filePath: filePath })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('خطا در حذف تصویر:', data.message);
        }
    })
    .catch(error => {
        console.error('خطا در ارتباط با سرور:', error);
    });
}

// تابع فرمت‌بندی اعداد
function formatNumber(input) {
    // حذف همه کاراکترهای غیر عددی
    let value = input.value.replace(/[^\d]/g, '');
    
    // اضافه کردن کاما به اعداد
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    
    // به‌روزرسانی مقدار input
    input.value = value;
}

// تابع محاسبه قیمت با مالیات
function calculateTaxPrice(price, taxPercentage) {
    const taxAmount = (price * taxPercentage) / 100;
    return price + taxAmount;
}

// تابع بررسی موجودی
function checkInventory() {
    const currentStock = parseInt(document.querySelector('input[name="initial_stock"]').value) || 0;
    const minStock = parseInt(document.querySelector('input[name="minimum_stock"]').value) || 0;
    const reorderPoint = parseInt(document.querySelector('input[name="reorder_point"]').value) || 0;

    if (currentStock <= minStock) {
        showAlert('هشدار موجودی', 'موجودی محصول کمتر از حداقل موجودی است!', 'warning');
    } else if (currentStock <= reorderPoint) {
        showAlert('هشدار موجودی', 'موجودی محصول به نقطه سفارش رسیده است!', 'info');
    }
}

// تابع نمایش هشدار
function showAlert(title, message, icon) {
    Swal.fire({
        title: title,
        text: message,
        icon: icon,
        confirmButtonText: 'تایید',
        customClass: {
            container: 'rtl-alert',
            title: 'rtl-alert-title',
            content: 'rtl-alert-content',
            confirmButton: 'rtl-alert-button'
        }
    });
}

// گوش دادن به تغییرات فیلدهای عددی برای فرمت‌بندی
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function() {
        formatNumber(this);
    });
});

// بررسی موجودی هنگام تغییر مقادیر مرتبط
document.querySelectorAll('input[name="initial_stock"], input[name="minimum_stock"], input[name="reorder_point"]')
    .forEach(input => {
        input.addEventListener('change', checkInventory);
    });

// تنظیم رویداد برای محاسبه خودکار قیمت با مالیات
document.querySelectorAll('input[name="sales_price"], input[name="sales_tax"]').forEach(input => {
    input.addEventListener('input', function() {
        const price = parseFloat(document.querySelector('input[name="sales_price"]').value.replace(/,/g, '')) || 0;
        const tax = parseFloat(document.querySelector('input[name="sales_tax"]').value) || 0;
        
        if (document.getElementById('salesTax').checked) {
            const finalPrice = calculateTaxPrice(price, tax);
            document.getElementById('finalSalesPrice').textContent = formatNumber(finalPrice);
        }
    });
});