<?php
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['filePath'])) {
        throw new Exception('مسیر فایل مشخص نشده است');
    }

    $filePath = $data['filePath'];
    
    // بررسی امنیتی مسیر فایل
    if (strpos($filePath, '..') !== false || !strpos($filePath, 'uploads/products/')) {
        throw new Exception('مسیر فایل نامعتبر است');
    }

    if (file_exists($filePath) && unlink($filePath)) {
        echo json_encode([
            'success' => true,
            'message' => 'فایل با موفقیت حذف شد'
        ]);
    } else {
        throw new Exception('خطا در حذف فایل');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}