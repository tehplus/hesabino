<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابینو - سیستم حسابداری آنلاین</title>
    
    <!-- اول jQuery لود شود -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- استایل‌ها -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <?php if (isset($page) && $page == 'add-product'): ?>
        <!-- استایل‌های صفحه محصول -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
        <link href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" rel="stylesheet">
        <link href="assets/css/product.css" rel="stylesheet">
         <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>