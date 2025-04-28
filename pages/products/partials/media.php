<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">تصاویر و ویدیوهای محصول</h3>
        <div class="card-actions">
            <a href="#" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mediaManagerModal">
                <i class="bi bi-images me-2"></i>
                مدیریت رسانه‌ها
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- آپلودر تصاویر -->
        <div class="media-uploader mb-4">
            <div class="dropzone-wrapper">
                <div id="productImageDropzone" class="dropzone">
                    <div class="dz-message">
                        <div class="upload-icon">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <h4>تصاویر را اینجا رها کنید</h4>
                        <p>یا کلیک کنید</p>
                        <small class="text-muted">
                            حداکثر 10 تصویر | فرمت‌های مجاز: JPG, PNG, WEBP | حداکثر حجم: 2MB
                        </small>
                    </div>
                </div>
            </div>
            <div id="imagePreviewContainer" class="preview-container row g-2 mt-2"></div>
        </div>

        <!-- آپلودر ویدیو -->
        <div class="video-uploader">
            <div class="row">
                <div class="col-md-6">
                    <div class="video-preview" id="videoPreview">
                        <div class="video-placeholder">
                            <i class="bi bi-camera-video"></i>
                            <span>پیش‌نمایش ویدیو</span>
                        </div>
                        <video controls style="display: none;"></video>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="upload-zone" id="videoDropzone">
                        <div class="dz-message">
                            <i class="bi bi-upload"></i>
                            <h5>ویدیوی محصول را آپلود کنید</h5>
                            <p>حداکثر حجم: 50MB | فرمت‌های مجاز: MP4, WEBM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>