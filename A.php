<?php
$page = 'edit-product';
require_once 'includes/header.php';
$pageTitle = 'ویرایش محصول';

// بررسی وجود شناسه محصول
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>شناسه محصول معتبر نیست.</div>";
    exit;
}

$productId = (int) $_GET['id'];

// دریافت اطلاعات محصول از پایگاه داده
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='alert alert-danger'>محصول مورد نظر یافت نشد.</div>";
    exit;
}

// بررسی ارسال فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    try {
        $pdo->beginTransaction();

        // آماده‌سازی داده‌ها
        $data = [
            'name' => $_POST['name'],
            'barcode' => $_POST['barcode'],
            'category_id' => $_POST['category_id'],
            'sales_price' => $_POST['sales_price'],
            'sales_description' => $_POST['sales_description'],
            'purchase_price' => $_POST['purchase_price'],
            'purchase_description' => $_POST['purchase_description'],
            'main_unit' => $_POST['main_unit'],
            'sub_unit' => $_POST['sub_unit'] ?? null,
            'conversion_factor' => $_POST['conversion_factor'] ?? null,
            'initial_stock' => $_POST['initial_stock'],
            'reorder_point' => $_POST['reorder_point'],
            'minimum_stock' => $_POST['minimum_stock'],
            'maximum_stock' => $_POST['maximum_stock'] ?? null,
            'minimum_order' => $_POST['minimum_order'],
            'wait_time' => $_POST['wait_time'],
            'storage_location' => $_POST['storage_location'] ?? null,
            'storage_note' => $_POST['storage_note'] ?? null,
            'sales_tax' => $_POST['sales_tax'] ?? 0,
            'purchase_tax' => $_POST['purchase_tax'] ?? 0,
            'tax_type' => $_POST['tax_type'] ?? null,
            'tax_code' => $_POST['tax_code'] ?? null,
            'tax_unit' => $_POST['tax_unit'] ?? null,
            'is_sales_taxable' => isset($_POST['is_sales_taxable']) ? 1 : 0,
            'is_purchase_taxable' => isset($_POST['is_purchase_taxable']) ? 1 : 0,
            'inventory_control' => isset($_POST['inventory_control']) ? 1 : 0,
            'id' => $productId
        ];

        // بروزرسانی محصول
        $sql = "UPDATE products SET 
            name = ?, 
            barcode = ?, 
            category_id = ?, 
            sales_price = ?, 
            sales_description = ?, 
            purchase_price = ?, 
            purchase_description = ?, 
            main_unit = ?, 
            sub_unit = ?, 
            conversion_factor = ?, 
            initial_stock = ?, 
            reorder_point = ?, 
            minimum_stock = ?, 
            maximum_stock = ?, 
            minimum_order = ?, 
            wait_time = ?, 
            storage_location = ?, 
            storage_note = ?, 
            sales_tax = ?, 
            purchase_tax = ?, 
            tax_type = ?, 
            tax_code = ?, 
            tax_unit = ?, 
            is_sales_taxable = ?, 
            is_purchase_taxable = ?, 
            inventory_control = ? 
            WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));

        // ذخیره تصاویر
        if (isset($_POST['uploaded_files']) && is_array($_POST['uploaded_files'])) {
            $stmt = $pdo->prepare("UPDATE products SET images = ? WHERE id = ?");
            $stmt->execute([json_encode($_POST['uploaded_files']), $productId]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "محصول با موفقیت بروزرسانی شد.";
        header('Location: index.php?page=products');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "خطا در بروزرسانی محصول: " . $e->getMessage();
    }
}

// نمایش تصاویر فعلی محصول
$currentImages = !empty($product['images']) ? json_decode($product['images'], true) : [];
?>

<!-- CSS های مورد نیاز -->
<link href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ویرایش محصول</h5>
                    <button type="button" class="btn btn-outline-light" onclick="history.back()">
                        <i class="bi bi-arrow-right"></i>
                        بازگشت
                    </button>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="editProductForm" class="needs-validation" novalidate>
                        <!-- تب‌های محصول -->
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#basicInfo">
                                    <i class="bi bi-info-circle"></i>
                                    اطلاعات اصلی
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#priceTab">
                                    <i class="bi bi-currency-dollar"></i>
                                    قیمت‌گذاری
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#unitsTab">
                                    <i class="bi bi-box"></i>
                                    واحدها
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#inventoryTab">
                                    <i class="bi bi-clipboard-data"></i>
                                    موجودی
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#taxTab">
                                    <i class="bi bi-percent"></i>
                                    مالیات
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#imagesTab">
                                    <i class="bi bi-images"></i>
                                    تصاویر
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- تب اطلاعات اصلی -->
                            <div class="tab-pane fade show active" id="basicInfo">
                                <!-- اطلاعات اصلی -->
                                <!-- مشابه اطلاعات اصلی در صفحه افزودن محصول -->
                            </div>

                            <!-- تب قیمت‌گذاری -->
                            <div class="tab-pane fade" id="priceTab">
                                <!-- قیمت‌گذاری -->
                                <!-- مشابه قیمت‌گذاری در صفحه افزودن محصول -->
                            </div>

                            <!-- تب واحدها -->
                            <div class="tab-pane fade" id="unitsTab">
                                <!-- واحدها -->
                                <!-- مشابه واحدها در صفحه افزودن محصول -->
                            </div>

                            <!-- تب موجودی -->
                            <div class="tab-pane fade" id="inventoryTab">
                                <!-- موجودی -->
                                <!-- مشابه موجودی در صفحه افزودن محصول -->
                            </div>

                            <!-- تب مالیات -->
                            <div class="tab-pane fade" id="taxTab">
                                <!-- مالیات -->
                                <!-- مشابه مالیات در صفحه افزودن محصول -->
                            </div>

                            <!-- تب تصاویر -->
                            <div class="tab-pane fade" id="imagesTab">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">تصاویر محصول</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="currentImages" class="mb-3">
                                            <?php foreach ($currentImages as $image): ?>
                                            <div class="d-inline-block position-relative m-2">
                                                <img src="<?php echo $image; ?>" alt="Product Image" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeImage(this, '<?php echo $image; ?>')">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div id="productImages" class="dropzone"></div>
                                        <input type="hidden" name="uploaded_files[]" id="uploadedFiles">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="update_product" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-lg"></i>
                                ذخیره تغییرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script>

</script>