    <!-- اسکریپت‌های اصلی -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <?php if (isset($page) && in_array($page, ['add-product', 'edit-product'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <?php endif; ?>

    <script>
    // این اسکریپت فقط یک بار تعریف می‌شود
    const backToTop = document.createElement('button');
    backToTop.id = 'back-to-top';
    backToTop.innerHTML = '<i class="bi bi-arrow-up"></i>';
    document.body.appendChild(backToTop);

    window.onscroll = function() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            backToTop.style.display = "block";
        } else {
            backToTop.style.display = "none";
        }
    };

    backToTop.addEventListener('click', function() {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    });

    <?php if (isset($page) && in_array($page, ['add-product', 'edit-product'])): ?>
    $(document).ready(function() {
        // راه‌اندازی Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            dir: 'rtl'
        });

        // پیکربندی Dropzone
        Dropzone.autoDiscover = false;
        var myDropzone = new Dropzone("#productImages", {
            url: "upload.php",
            acceptedFiles: "image/*",
            maxFilesize: 2,
            maxFiles: 5,
            addRemoveLinks: true,
            dictDefaultMessage: "فایل‌ها را اینجا بکشید و رها کنید یا کلیک کنید",
            dictRemoveFile: "حذف فایل",
            success: function(file, response) {
                if (response.success) {
                    var uploadedFiles = JSON.parse($("#uploadedFiles").val() || '[]');
                    uploadedFiles.push(response.filePath);
                    $("#uploadedFiles").val(JSON.stringify(uploadedFiles));
                    
                    // نمایش پیام موفقیت با SweetAlert2
                    Swal.fire({
                        title: 'موفقیت',
                        text: 'فایل با موفقیت آپلود شد',
                        icon: 'success',
                        confirmButtonText: 'تایید'
                    });
                }
            },
            error: function(file, response) {
                // نمایش پیام خطا با SweetAlert2
                Swal.fire({
                    title: 'خطا',
                    text: 'خطا در آپلود فایل',
                    icon: 'error',
                    confirmButtonText: 'تایید'
                });
            }
        });
    });
    <?php endif; ?>
    </script>

    <?php
    // نمایش پیام‌های موفقیت
    if (isset($_SESSION['success_message'])): ?>
    <script>
        Swal.fire({
            title: 'موفقیت',
            text: '<?php echo $_SESSION['success_message']; ?>',
            icon: 'success',
            confirmButtonText: 'تایید'
        });
    </script>
    <?php 
    unset($_SESSION['success_message']);
    endif; 
    
    // نمایش پیام‌های خطا
    if (isset($_SESSION['error_message'])): ?>
    <script>
        Swal.fire({
            title: 'خطا',
            text: '<?php echo $_SESSION['error_message']; ?>',
            icon: 'error',
            confirmButtonText: 'تایید'
        });
    </script>
    <?php 
    unset($_SESSION['error_message']);
    endif; 
    ?>
</body>
</html>