$(document).ready(function() {
    // راه‌اندازی Select2 برای دسته‌بندی‌ها
    $('#categories').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
        allowClear: true,
        language: {
            noResults: function() {
                return "موردی یافت نشد";
            }
        }
    });
    
    // راه‌اندازی CKEditor برای توضیحات
    if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.replace('description', {
            language: 'fa',
            height: 300,
            removeButtons: 'Source',
            toolbarGroups: [
                { name: 'basicstyles', groups: ['basicstyles', 'cleanup'] },
                { name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align'] },
                { name: 'links' },
                { name: 'colors' }
            ]
        });
    }
    
    // راه‌اندازی Dropzone برای آپلود تصاویر
    Dropzone.autoDiscover = false;
    const productDropzone = new Dropzone("#product-images", {
        url: "actions/upload-product-image.php",
        paramName: "image",
        maxFilesize: 2, // 2MB
        acceptedFiles: "image/*",
        addRemoveLinks: true,
        dictDefaultMessage: "تصاویر را اینجا رها کنید",
        dictRemoveFile: "حذف",
        dictMaxFilesExceeded: "امکان آپلود بیش از 5 تصویر وجود ندارد",
        dictInvalidFileType: "فرمت فایل نامعتبر است",
        dictFileTooBig: "حجم فایل بیش از حد مجاز است",
        maxFiles: 5,
        headers: {
            'X-CSRF-TOKEN': $('input[name="csrf_token"]').val()
        },
        init: function() {
            this.on("sending", function(file, xhr, formData) {
                formData.append("product_id", $('input[name="product_id"]').val());
            });
            
            this.on("success", function(file, response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        file.serverId = result.image_id;
                        // افزودن تصویر جدید به گالری
                        addImageToGallery(result.image_id, result.filename);
                    } else {
                        this.removeFile(file);
                        showError(result.message || 'خطا در آپلود فایل');
                    }
                } catch (e) {
                    this.removeFile(file);
                    showError('خطا در آپلود فایل');
                }
            });
            
            this.on("error", function(file, message) {
                showError(message);
                this.removeFile(file);
            });
        }
    });
    
    // مدیریت حذف تصاویر
    let imageIdToDelete = null;
    
    $('.delete-image').on('click', function(e) {
        e.preventDefault();
        imageIdToDelete = $(this).data('image-id');
        $('#deleteImageModal').modal('show');
    });
    
    $('#confirmDeleteImage').on('click', function() {
        if (!imageIdToDelete) return;
        
        $.ajax({
            url: 'actions/delete-product-image.php',
            type: 'POST',
            data: {
                image_id: imageIdToDelete,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $(`#image-${imageIdToDelete}`).fadeOut(300, function() {
                            $(this).remove();
                        });
                        showSuccess('تصویر با موفقیت حذف شد');
                    } else {
                        showError(result.message || 'خطا در حذف تصویر');
                    }
                } catch (e) {
                    showError('خطا در حذف تصویر');
                }
                $('#deleteImageModal').modal('hide');
            },
            error: function() {
                showError('خطا در ارتباط با سرور');
                $('#deleteImageModal').modal('hide');
            }
        });
    });
    
    // ارسال فرم
    $('#edit-product-form').on('submit', function(e) {
        e.preventDefault();
        
        // اعتبارسنجی فرم
        if (!validateForm()) {
            return false;
        }
        
        // جمع‌آوری داده‌ها
        const formData = new FormData(this);
        if (typeof CKEDITOR !== 'undefined') {
            formData.set('description', CKEDITOR.instances.description.getData());
        }
        
        // ارسال درخواست
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        showSuccess('تغییرات با موفقیت ذخیره شد');
                        setTimeout(() => {
                            window.location.href = 'index.php?page=products';
                        }, 1500);
                    } else {
                        showError(result.message || 'خطا در ذخیره تغییرات');
                    }
                } catch (e) {
                    showError('خطا در ذخیره تغییرات');
                }
            },
            error: function() {
                showError('خطا در ارتباط با سرور');
            }
        });
    });
    
    // توابع کمکی
    function validateForm() {
        let isValid = true;
        const requiredFields = ['name', 'sku', 'price', 'stock'];
        
        requiredFields.forEach(field => {
            const value = $(`#${field}`).val().trim();
            if (!value) {
                showError(`لطفاً ${getFieldLabel(field)} را وارد کنید`);
                isValid = false;
            }
        });
        
        if ($('#price').val() < 0) {
            showError('قیمت نمی‌تواند منفی باشد');
            isValid = false;
        }
        
        if ($('#stock').val() < 0) {
            showError('موجودی نمی‌تواند منفی باشد');
            isValid = false;
        }
        
        return isValid;
    }
    
    function getFieldLabel(field) {
        const labels = {
            name: 'نام محصول',
            sku: 'کد محصول',
            price: 'قیمت',
            stock: 'موجودی'
        };
        return labels[field] || field;
    }
    
    function addImageToGallery(imageId, filename) {
        const template = `
            <div class="col-6" id="image-${imageId}">
                <div class="card">
                    <img src="uploads/products/${filename}" class="card-img-top" alt="تصویر محصول">
                    <div class="card-body p-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 delete-image" 
                                data-image-id="${imageId}">
                            حذف
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#current-images').append(template);
        
        // اضافه کردن مجدد event listener برای دکمه حذف
        $(`#image-${imageId} .delete-image`).on('click', function(e) {
            e.preventDefault();
            imageIdToDelete = $(this).data('image-id');
            $('#deleteImageModal').modal('show');
        });
    }
    
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'موفقیت',
            text: message,
            confirmButtonText: 'تأیید'
        });
    }
    
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: message,
            confirmButtonText: 'تأیید'
        });
    }
});