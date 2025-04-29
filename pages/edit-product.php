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

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش محصول</title>
    
    <!-- CSS های مورد نیاز -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">


        <!-- سایر meta tags و CSS ها -->
    
    <!-- اضافه کردن jQuery قبل از همه اسکریپت‌ها -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- اضافه کردن Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- اضافه کردن Dropzone -->
    <link href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" rel="stylesheet" type="text/css" />
    
    <!-- سایر CSS ها -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="assets/css/edit-product.css" rel="stylesheet">
</head>
<body>

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
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">نام محصول</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">بارکد</label>
                                            <div class="input-group">
                                                <input type="text" name="barcode" class="form-control" value="<?php echo htmlspecialchars($product['barcode']); ?>">
                                                <button type="button" class="btn btn-outline-secondary" onclick="generateBarcode()">
                                                    <i class="bi bi-upc"></i>
                                                    تولید بارکد
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">دسته‌بندی</label>
                                            <select name="category_id" class="form-select select2" required>
                                                <option value="">انتخاب دسته‌بندی</option>
                                                <?php
                                                $categories = $pdo->query("SELECT * FROM categories ORDER BY name");
                                                while ($category = $categories->fetch()) {
                                                    $selected = $category['id'] === $product['category_id'] ? 'selected' : '';
                                                    echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" name="inventory_control" class="form-check-input" id="inventoryControl" <?php echo $product['inventory_control'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label">کنترل موجودی</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تب قیمت‌گذاری -->
                            <div class="tab-pane fade" id="priceTab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">اطلاعات فروش</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">قیمت فروش (ریال)</label>
                                                    <input type="number" name="sales_price" class="form-control" value="<?php echo $product['sales_price']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">توضیحات فروش</label>
                                                    <textarea name="sales_description" class="form-control" rows="3"><?php echo htmlspecialchars($product['sales_description']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">اطلاعات خرید</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">قیمت خرید (ریال)</label>
                                                    <input type="number" name="purchase_price" class="form-control" value="<?php echo $product['purchase_price']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">توضیحات خرید</label>
                                                    <textarea name="purchase_description" class="form-control" rows="3"><?php echo htmlspecialchars($product['purchase_description']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تب واحدها -->
                            <div class="tab-pane fade" id="unitsTab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">واحد اصلی</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">واحد اصلی</label>
                                                    <input type="text" name="main_unit" class="form-control" value="<?php echo htmlspecialchars($product['main_unit']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">واحد فرعی</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">واحد فرعی</label>
                                                    <input type="text" name="sub_unit" class="form-control" value="<?php echo htmlspecialchars($product['sub_unit']); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">ضریب تبدیل</label>
                                                    <input type="number" name="conversion_factor" class="form-control" value="<?php echo $product['conversion_factor']; ?>" step="0.01">
                                                    <div class="form-text">هر واحد فرعی معادل چند واحد اصلی است؟</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تب موجودی -->
                            <div class="tab-pane fade" id="inventoryTab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">اطلاعات موجودی</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">موجودی اولیه</label>
                                                    <input type="number" name="initial_stock" class="form-control" value="<?php echo $product['initial_stock']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">نقطه سفارش</label>
                                                    <input type="number" name="reorder_point" class="form-control" value="<?php echo $product['reorder_point']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">حداقل موجودی</label>
                                                    <input type="number" name="minimum_stock" class="form-control" value="<?php echo $product['minimum_stock']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">حداکثر موجودی</label>
                                                    <input type="number" name="maximum_stock" class="form-control" value="<?php echo $product['maximum_stock']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">اطلاعات انبار</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">محل نگهداری</label>
                                                    <input type="text" name="storage_location" class="form-control" value="<?php echo htmlspecialchars($product['storage_location']); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">توضیحات انبار</label>
                                                    <textarea name="storage_note" class="form-control" rows="3"><?php echo htmlspecialchars($product['storage_note']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تب مالیات -->
                            <div class="tab-pane fade" id="taxTab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">مالیات فروش</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" name="is_sales_taxable" class="form-check-input" id="salesTax" <?php echo $product['is_sales_taxable'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">مشمول مالیات فروش</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">درصد مالیات فروش</label>
                                                    <input type="number" name="sales_tax" class="form-control" value="<?php echo $product['sales_tax']; ?>" step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">مالیات خرید</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" name="is_purchase_taxable" class="form-check-input" id="purchaseTax" <?php echo $product['is_purchase_taxable'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">مشمول مالیات خرید</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">درصد مالیات خرید</label>
                                                    <input type="number" name="purchase_tax" class="form-control" value="<?php echo $product['purchase_tax']; ?>" step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">اطلاعات تکمیلی مالیات</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">نوع مالیات</label>
                                                            <input type="text" name="tax_type" class="form-control" value="<?php echo htmlspecialchars($product['tax_type']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">کد مالیاتی</label>
                                                            <input type="text" name="tax_code" class="form-control" value="<?php echo htmlspecialchars($product['tax_code']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">واحد مالیاتی</label>
                                                            <input type="text" name="tax_unit" class="form-control" value="<?php echo htmlspecialchars($product['tax_unit']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
<script src="assets/js/edit-product.js"></script>

<!-- Scripts -->

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    Dropzone.autoDiscover = false;
const dropzone = new Dropzone("#productImages", {
    url: "upload.php",
    acceptedFiles: "image/*",
    maxFiles: 5,
    maxFilesize: 2,
    dictDefaultMessage: "تصاویر را اینجا بکشید و رها کنید یا کلیک کنید",
    addRemoveLinks: true,
    success: function (file, response) {
        const uploadedFilesInput = document.getElementById('uploadedFiles');
        uploadedFilesInput.value += (uploadedFilesInput.value ? ',' : '') + response.filePath;
    }
});

function removeImage(button, filePath) {
    button.parentElement.remove();
    const uploadedFilesInput = document.getElementById('uploadedFiles');
    uploadedFilesInput.value = uploadedFilesInput.value.split(',').filter(file => file !== filePath).join(',');
}

function generateBarcode() {
    const timestamp = new Date().getTime().toString().slice(-12);
    document.querySelector('input[name="barcode"]').value = timestamp;
}
function savePriceList() {
    const modal = document.getElementById('priceListModal');
    const inputs = modal.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        const mainInput = document.querySelector(`input[name="${input.name}"]`);
        if (mainInput) mainInput.value = input.value;
    });
    bootstrap.Modal.getInstance(modal).hide();
}
// پیکربندی Dropzone
Dropzone.autoDiscover = false;
$(document).ready(function() {
    // راه‌اندازی Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // پیکربندی Dropzone
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
        },
        removedfile: function(file) {
            var uploadedFiles = JSON.parse($("#uploadedFiles").val() || '[]');
            if (file.status === 'success') {
                var response = JSON.parse(file.xhr.response);
                var index = uploadedFiles.indexOf(response.filePath);
                if (index !== -1) {
                    uploadedFiles.splice(index, 1);
                    $("#uploadedFiles").val(JSON.stringify(uploadedFiles));
                    
                    // حذف فایل از سرور
                    $.post('delete-image.php', {
                        filePath: response.filePath
                    });
                }
            }
            file.previewElement.remove();
        }
    });

    // نمایش تصاویر موجود در Dropzone
    var currentImages = JSON.parse($("#uploadedFiles").val() || '[]');
    currentImages.forEach(function(filePath) {
        var mockFile = { name: filePath.split('/').pop(), size: 12345 };
        myDropzone.emit("addedfile", mockFile);
        myDropzone.emit("thumbnail", mockFile, filePath);
        myDropzone.emit("complete", mockFile);
        myDropzone.files.push(mockFile);
    });
});
</script>
</body>
</html>