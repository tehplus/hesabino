<?php
header('Content-Type: application/json');

try {
    if (!isset($_FILES['file'])) {
        throw new Exception('هیچ فایلی آپلود نشده است');
    }

    $file = $_FILES['file'];
    $fileName = uniqid() . '_' . basename($file['name']);
    $uploadDir = 'uploads/products/';
    
    // اطمینان از وجود دایرکتوری
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadPath = $uploadDir . $fileName;

    // بررسی نوع فایل
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('نوع فایل معتبر نیست. فقط تصاویر JPEG، PNG، GIF و WebP مجاز هستند');
    }

    // بررسی سایز فایل (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('حجم فایل بیش از حد مجاز است (حداکثر 2 مگابایت)');
    }

    // بررسی ابعاد تصویر
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('فایل آپلود شده یک تصویر معتبر نیست');
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // بهینه‌سازی تصویر
        $image = null;
        switch ($file['type']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($uploadPath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($uploadPath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($uploadPath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($uploadPath);
                break;
        }

        if ($image) {
            // فشرده‌سازی و ذخیره مجدد
            switch ($file['type']) {
                case 'image/jpeg':
                    imagejpeg($image, $uploadPath, 85);
                    break;
                case 'image/png':
                    imagepng($image, $uploadPath, 8);
                    break;
                case 'image/gif':
                    imagegif($image, $uploadPath);
                    break;
                case 'image/webp':
                    imagewebp($image, $uploadPath, 85);
                    break;
            }
            imagedestroy($image);
        }

        echo json_encode([
            'success' => true,
            'filePath' => $uploadPath,
            'message' => 'فایل با موفقیت آپلود شد'
        ]);
    } else {
        throw new Exception('خطا در آپلود فایل');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}