// اطمینان از یکبار اجرا شدن
let dropzoneInitialized = false;

// منتظر لود شدن jQuery
$(document).ready(function() {
    initializeComponents();
});

function initializeComponents() {
    initSelect2();
    initDropzone();
    initInventoryControls();
    setupFormValidation();
}

function initSelect2() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl'
    });
}

function initDropzone() {
    if (!dropzoneInitialized) {
        Dropzone.autoDiscover = false;
        
        const dropzoneElement = document.getElementById('productImages');
        if (dropzoneElement) {
            const myDropzone = new Dropzone("#productImages", {
                url: "upload.php",
                paramName: "file",
                maxFiles: 5,
                maxFilesize: 2,
                acceptedFiles: "image/*",
                dictDefaultMessage: '<div class="text-center"><i class="bi bi-cloud-upload display-4"></i><br>تصاویر را اینجا رها کنید یا کلیک کنید</div>',
                addRemoveLinks: true,
                dictRemoveFile: "حذف",
                dictCancelUpload: "لغو",
                init: function() {
                    this.on("success", function(file, response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                file.serverFileName = data.filename;
                                $('<input>').attr({
                                    type: 'hidden',
                                    name: 'uploaded_files[]',
                                    value: data.filename
                                }).appendTo('#addProductForm');
                            }
                        } catch (e) {
                            console.error('Error parsing upload response:', e);
                        }
                    });
                }
            });
            dropzoneInitialized = true;
        }
    }
}

function initInventoryControls() {
    // نمایش همیشگی تنظیمات موجودی
    $('#inventorySettings').show();
    
    // مدیریت فیلدهای عددی موجودی
    $('input[type="number"]').on('input', function() {
        validateInventoryNumbers(this);
    });
}

function validateInventoryNumbers(input) {
    const value = parseInt(input.value);
    const min = parseInt(input.min) || 0;
    const max = parseInt(input.max) || Infinity;
    
    if (value < min) input.value = min;
    if (value > max) input.value = max;
    
    // بررسی موجودی و نمایش هشدار
    if (input.name === 'initial_stock') {
        const reorderPoint = parseInt($('input[name="reorder_point"]').val()) || 0;
        const minimumStock = parseInt($('input[name="minimum_stock"]').val()) || 0;
        
        if (value <= minimumStock) {
            showInventoryAlert('danger', 'موجودی کمتر از حداقل مجاز است!');
        } else if (value <= reorderPoint) {
            showInventoryAlert('warning', 'موجودی به نقطه سفارش رسیده است.');
        }
    }
}

function showInventoryAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('#inventorySettings').prepend(alertHtml);
}

// منتظر لود شدن کامل صفحه

document.addEventListener('DOMContentLoaded', function () {
    initializeComponents();
});

function initializeComponents() {
    // تنظیمات Select2
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            dir: 'rtl'
        });
    }

    // تنظیمات Dropzone
    if (typeof Dropzone !== 'undefined') {
        Dropzone.autoDiscover = false;
        
        const dropzoneElement = document.getElementById('productImages');
        if (dropzoneElement) {
            const myDropzone = new Dropzone("#productImages", {
                url: "upload.php",
                paramName: "file",
                maxFiles: 5,
                maxFilesize: 2,
                acceptedFiles: "image/*",
                dictDefaultMessage: '<div class="text-center"><i class="bi bi-cloud-upload display-4"></i><br>تصاویر را اینجا رها کنید یا کلیک کنید</div>',
                addRemoveLinks: true,
                dictRemoveFile: "حذف",
                dictCancelUpload: "لغو",
                init: function() {
                    this.on("success", function(file, response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                file.serverFileName = data.filename;
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'uploaded_files[]';
                                input.value = data.filename;
                                document.getElementById('addProductForm').appendChild(input);
                            }
                        } catch (e) {
                            console.error('Error parsing upload response:', e);
                        }
                    });
                }
            });
        }
    }

    // مدیریت واحد فرعی
    const hasSubUnitCheckbox = document.getElementById('hasSubUnit');
    if (hasSubUnitCheckbox) {
        hasSubUnitCheckbox.addEventListener('change', function() {
            const subUnitSection = document.getElementById('subUnitSection');
            if (subUnitSection) {
                subUnitSection.style.display = this.checked ? 'block' : 'none';
            }
        });
    }

    // مدیریت کنترل موجودی
    const inventoryControl = document.getElementById('inventoryControl');
    const inventorySettings = document.getElementById('inventorySettings');
    if (inventoryControl && inventorySettings) {
        inventoryControl.checked = true;
        inventoryControl.disabled = true;
        inventorySettings.style.display = 'block';
    }

    // مدیریت کد حسابداری
    const customCodeCheckbox = document.getElementById('customCode');
    if (customCodeCheckbox) {
        customCodeCheckbox.addEventListener('change', function() {
            const codeInput = document.querySelector('input[name="custom_code"]');
            if (codeInput) {
                codeInput.readOnly = !this.checked;
                if (!this.checked) {
                    fetch('ajax/get_last_code.php')
                        .then(response => response.json())
                        .then(data => codeInput.value = data.code)
                        .catch(error => console.error('Error:', error));
                } else {
                    codeInput.value = '';
                }
            }
        });
    }

    // مدیریت مالیات
    setupTaxHandlers();
    
    // اعتبارسنجی فرم
    setupFormValidation();
}

function setupTaxHandlers() {
    const salesTaxCheckbox = document.getElementById('salesTax');
    const purchaseTaxCheckbox = document.getElementById('purchaseTax');

    if (salesTaxCheckbox) {
        salesTaxCheckbox.addEventListener('change', function() {
            const salesTaxInput = document.querySelector('input[name="sales_tax"]');
            if (salesTaxInput) {
                salesTaxInput.disabled = !this.checked;
                if (!this.checked) salesTaxInput.value = '';
            }
        });
    }

    if (purchaseTaxCheckbox) {
        purchaseTaxCheckbox.addEventListener('change', function() {
            const purchaseTaxInput = document.querySelector('input[name="purchase_tax"]');
            if (purchaseTaxInput) {
                purchaseTaxInput.disabled = !this.checked;
                if (!this.checked) purchaseTaxInput.value = '';
            }
        });
    }
}

function setupFormValidation() {
    const form = document.getElementById('addProductForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
}

function generateBarcode() {
    const timestamp = new Date().getTime().toString().slice(-12);
    const barcodeInput = document.querySelector('input[name="barcode"]');
    if (barcodeInput) {
        barcodeInput.value = timestamp;
    }
}

function savePriceList() {
    const modal = document.getElementById('priceListModal');
    if (modal) {
        const inputs = modal.querySelectorAll('input[type="number"]');
        inputs.forEach(input => {
            const mainInput = document.querySelector(`input[name="${input.name}"]`);
            if (mainInput) {
                mainInput.value = input.value;
            }
        });
        bootstrap.Modal.getInstance(modal).hide();
    }
}

// فرمت‌بندی اعداد
function formatNumber(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        input.value = parseInt(value).toLocaleString('fa-IR');
    }
}
// اضافه کردن در انتهای فایل
function initMediaPreviews() {
    // پیش‌نمایش تصویر
    $('#imageUpload').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#productImage').attr('src', e.target.result);
                Swal.fire({
                    icon: 'success',
                    title: 'تصویر آپلود شد',
                    text: 'تصویر با موفقیت بارگذاری شد',
                    confirmButtonText: 'تایید'
                });
            }
            reader.readAsDataURL(file);
        }
    });

    // پیش‌نمایش ویدیو
    $('#videoUpload').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#productVideo').attr('src', e.target.result);
                Swal.fire({
                    icon: 'success',
                    title: 'ویدیو آپلود شد',
                    text: 'ویدیو با موفقیت بارگذاری شد',
                    confirmButtonText: 'تایید'
                });
            }
            reader.readAsDataURL(file);
        }
    });
}