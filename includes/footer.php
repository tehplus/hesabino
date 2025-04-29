    <!-- اسکریپت‌های اصلی -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (in_array($page, ['add-product', 'edit-product'])): ?>
        <!-- اول Select2 را لود می‌کنیم -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <!-- بعد Dropzone -->
        <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
        <!-- بعد SweetAlert -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
        <!-- در آخر اسکریپت‌های خودمان -->
        <script>
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
                    }
                }
            });
        });
        </script>
    <?php endif; ?>
</body>
</html>