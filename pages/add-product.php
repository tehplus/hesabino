<?php
$page = 'add-product';
require_once 'includes/header.php';
$pageTitle = 'افزودن محصول جدید';



// دریافت آخرین کد حسابداری
function getLastAccountingCode($pdo) {
    $stmt = $pdo->query("SELECT accounting_code FROM products ORDER BY accounting_code DESC LIMIT 1");
    $last = $stmt->fetch();
    if ($last) {
        $num = intval(substr($last['accounting_code'], -4)) + 1;
        return sprintf("P%04d", $num);
    }
    return "P0001";
}

// بررسی درخواست افزودن محصول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        $pdo->beginTransaction();

        // آماده سازی داده‌ها
        $data = [
            'name' => $_POST['name'],
            'accounting_code' => !empty($_POST['custom_code']) ? $_POST['custom_code'] : getLastAccountingCode($pdo),
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
            'current_stock' => $_POST['initial_stock'], // موجودی فعلی برابر با موجودی اولیه
            'reorder_point' => $_POST['reorder_point'],
            'minimum_stock' => $_POST['minimum_stock'],
            'maximum_stock' => !empty($_POST['maximum_stock']) ? $_POST['maximum_stock'] : null,
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
            'inventory_control' => isset($_POST['inventory_control']) ? 1 : 0
        ];

        // ساخت query
        $fields = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO products ($fields) VALUES ($values)";
        
        // اجرای query
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        // ثبت تراکنش موجودی اولیه
        $product_id = $pdo->lastInsertId();
        
        if ($data['initial_stock'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO inventory_transactions 
                (product_id, transaction_type, quantity, previous_stock, new_stock, note)
                VALUES (?, 'adjustment', ?, 0, ?, 'موجودی اولیه')
            ");
            $stmt->execute([$product_id, $data['initial_stock'], $data['initial_stock']]);
        }

        // ذخیره تصاویر
        if (isset($_POST['uploaded_files']) && is_array($_POST['uploaded_files'])) {
            $stmt = $pdo->prepare("UPDATE products SET images = ? WHERE id = ?");
            $stmt->execute([json_encode($_POST['uploaded_files']), $product_id]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "محصول با موفقیت افزوده شد.";
        header('Location: index.php?page=products'); // تغییر مسیر به لیست محصولات
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "خطا در افزودن محصول: " . $e->getMessage();
        // برای دیباگ:
        error_log("Error adding product: " . $e->getMessage());
    }
}

?>
<!-- اول Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<!-- بعد Dropzone -->
<link href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" rel="stylesheet" type="text/css">

<!-- بعد Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">



<!-- اضافه کردن CSS های مورد نیاز -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">افزودن محصول جدید</h5>
                    <button type="button" class="btn btn-outline-light" onclick="history.back()">
                        <i class="bi bi-arrow-right"></i>
                        بازگشت
                    </button>
                </div>
                <div class="card-body">
                    <form method="POST" id="addProductForm" class="needs-validation" novalidate>
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
                        </ul>

                        <div class="tab-content">
                            <!-- تب اطلاعات اصلی -->
                            <div class="tab-pane fade show active" id="basicInfo">
                                <div class="row">
                                    <div class="col-md-6">
                                        <!-- آپلودر تصویر با Dropzone -->
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">تصاویر محصول</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="productImages" class="dropzone"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- اطلاعات پایه -->
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">نام محصول</label>
                                                    <input type="text" name="name" class="form-control" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">دسته‌بندی</label>
                                                    <select name="category_id" class="form-select select2" required>
                                                        <option value="">انتخاب دسته‌بندی</option>
                                                        <?php
                                                        $categories = $pdo->query("SELECT * FROM categories ORDER BY name");
                                                        while ($category = $categories->fetch()) {
                                                            echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="customCode">
                                                        <label class="form-check-label">کد حسابداری سفارشی</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">کد حسابداری</label>
                                                    <input type="text" name="custom_code" class="form-control" readonly>
                                                    <div class="form-text">کد پیش‌فرض: <?php echo getLastAccountingCode($pdo); ?></div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">بارکد</label>
                                                    <input type="text" name="barcode" class="form-control">
                                                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="generateBarcode()">
                                                        <i class="bi bi-upc"></i>
                                                        تولید بارکد
                                                    </button>
                                                </div>
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
                                                <h6 class="mb-0">قیمت فروش</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">قیمت فروش (ریال)</label>
                                                    <input type="number" name="sales_price" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">توضیحات فروش</label>
                                                    <textarea name="sales_description" class="form-control" rows="3"></textarea>
                                                </div>
                                                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#priceListModal">
                                                    <i class="bi bi-list-ul"></i>
                                                    لیست قیمت‌ها
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0">قیمت خرید</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">قیمت خرید (ریال)</label>
                                                    <input type="number" name="purchase_price" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">توضیحات خرید</label>
                                                    <textarea name="purchase_description" class="form-control" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تب واحدها -->
                            <div class="tab-pane fade" id="unitsTab">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">واحد اصلی</label>
                                                    <input type="text" name="main_unit" class="form-control" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" class="form-check-input" id="hasSubUnit">
                                                        <label class="form-check-label">بیش از یک واحد دارد</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="subUnitSection" style="display:none">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">واحد فرعی</label>
                                                        <input type="text" name="sub_unit" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">ضریب تبدیل</label>
                                                        <input type="number" name="conversion_factor" class="form-control" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تب موجودی -->
                            <div class="tab-pane fade" id="inventoryTab">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="form-check mb-4">
                                            <input type="checkbox" name="inventory_control" class="form-check-input" id="inventoryControl" checked>
                                            <label class="form-check-label">کنترل موجودی</label>
                                        </div>
                                        
                                        <div id="inventorySettings">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">موجودی اولیه</label>
                                                        <input type="number" name="initial_stock" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">نقطه سفارش</label>
                                                        <input type="number" name="reorder_point" class="form-control" required>
                                                        <div class="form-text">وقتی موجودی به این مقدار برسد، هشدار داده می‌شود</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">حداقل موجودی</label>
                                                        <input type="number" name="minimum_stock" class="form-control" required>
                                                        <div class="form-text">حداقل موجودی مجاز</div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">حداکثر موجودی</label>
                                                        <input type="number" name="maximum_stock" class="form-control">
                                                        <div class="form-text">حداکثر موجودی مجاز برای انبار</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">حداقل سفارش</label>
                                                        <input type="number" name="minimum_order" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">زمان انتظار (روز)</label>
                                                        <input type="number" name="wait_time" class="form-control">
                                                        <div class="form-text">مدت زمان لازم برای رسیدن سفارش جدید</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">محل نگهداری در انبار</label>
                                                        <input type="text" name="storage_location" class="form-control">
                                                        <div class="form-text">مثال: راهرو A، قفسه 3، ردیف 2</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">یادداشت انبار</label>
                                                        <textarea name="storage_note" class="form-control" rows="2"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تب مالیات -->
                            <div class="tab-pane fade" id="taxTab">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" name="is_sales_taxable" class="form-check-input" id="salesTax">
                                                    <label class="form-check-label">مشمول مالیات فروش</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">درصد مالیات فروش</label>
                                                    <input type="number" name="sales_tax" class="form-control" step="0.01">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" name="is_purchase_taxable" class="form-check-input" id="purchaseTax">
                                                    <label class="form-check-label">مشمول مالیات خرید</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">درصد مالیات خرید</label>
                                                    <input type="number" name="purchase_tax" class="form-control" step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">نوع مالیات</label>
                                                    <input type="text" name="tax_type" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">کد مالیاتی</label>
                                                    <input type="text" name="tax_code" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">واحد مالیاتی</label>
                                                    <input type="text" name="tax_unit" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="add_product" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-lg"></i>
                                ذخیره محصول
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال لیست قیمت‌ها -->
<div class="modal fade" id="priceListModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">لیست قیمت‌ها</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">قیمت همکار (ریال)</label>
                            <input type="number" name="partner_price" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">قیمت عمده (ریال)</label>
                            <input type="number" name="wholesale_price" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">قیمت دلاری ($)</label>
                            <input type="number" name="usd_price" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">قیمت پرسنل (ریال)</label>
                            <input type="number" name="staff_price" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">قیمت مغازه (ریال)</label>
                            <input type="number" name="shop_price" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                <button type="button" class="btn btn-primary" onclick="savePriceList()">ذخیره</button>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    border: none;
    margin-bottom: 25px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 15px 20px;
    border-radius: 15px 15px 0 0 !important;
}

.form-control, .form-select {
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
}

.form-control:focus, .form-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
}

.btn {
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 500;
}

.btn-primary {
    background-color: #3498db;
    border: none;
}

.btn-primary:hover {
    background-color: #2980b9;
}

.nav-tabs {
    border-bottom: 2px solid #f8f9fa;
    margin-bottom: 25px;
}

.nav-tabs .nav-link {
    border: none;
    color: #666;
    padding: 12px 20px;
    border-radius: 10px;
    margin-right: 5px;
}

.nav-tabs .nav-link:hover {
    background-color: #f8f9fa;
}

.nav-tabs .nav-link.active {
    background-color: #3498db;
    color: white;
}

.dropzone {
    border: 2px dashed #3498db;
    border-radius: 15px;
    background: #f8f9fa;
    min-height: 200px;
    padding: 20px;
}

.select2-container--bootstrap-5 .select2-selection {
    border-radius: 10px;
    padding: 8px;
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    margin-left: 0.5em;
}

.modal-content {
    border-radius: 15px;
}

.modal-header {
    background-color: #f8f9fa;
    border-radius: 15px 15px 0 0;
}

#inventorySettings, #subUnitSection {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-top: 15px;
}

.form-check-input:checked {
    background-color: #3498db;
    border-color: #3498db;
}
</style>

<!-- اول jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- بعد Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- بعد Dropzone -->
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

<!-- و در آخر Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- اضافه کردن اسکریپت‌های مورد نیاز -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// تنظیمات Dropzone
Dropzone.autoDiscover = false;
new Dropzone("#productImages", {
    url: "upload.php",
    acceptedFiles: "image/*",
    maxFiles: 5,
    maxFilesize: 2,
    dictDefaultMessage: "تصاویر را اینجا رها کنید یا کلیک کنید",
    addRemoveLinks: true
});

// فعال‌سازی Select2
$(document).ready(function() {
    $('.select2').select2({
        theme: "bootstrap-5",
        dir: "rtl"
    });
});

// مدیریت کد حسابداری سفارشی
document.getElementById('customCode').addEventListener('change', function() {
    const codeInput = document.querySelector('input[name="custom_code"]');
    codeInput.readOnly = !this.checked;
    if (!this.checked) {
        codeInput.value = '<?php echo getLastAccountingCode($pdo); ?>';
    } else {
        codeInput.value = '';
    }
});

// مدیریت واحد فرعی
document.getElementById('hasSubUnit').addEventListener('change', function() {
    document.getElementById('subUnitSection').style.display = this.checked ? 'block' : 'none';
});

// مدیریت کنترل موجودی
document.getElementById('inventoryControl').addEventListener('change', function() {
    document.getElementById('inventorySettings').style.display = this.checked ? 'block' : 'none';
});

// تولید بارکد
function generateBarcode() {
    const timestamp = new Date().getTime().toString().slice(-12);
    document.querySelector('input[name="barcode"]').value = timestamp;
}

// ذخیره لیست قیمت
function savePriceList() {
    // انتقال مقادیر به فرم اصلی
    const modal = document.getElementById('priceListModal');
    const inputs = modal.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        const mainInput = document.querySelector(`input[name="${input.name}"]`);
        if (mainInput) mainInput.value = input.value;
    });
    
    // بستن مودال
    bootstrap.Modal.getInstance(modal).hide();
}

// اعتبارسنجی فرم
(function () {
    'use strict'
    const form = document.getElementById('addProductForm');
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>