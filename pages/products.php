<?php
$page = 'products';
require_once 'includes/header.php';
$pageTitle = 'لیست محصولات';

// تنظیمات پیج‌بندی
$itemsPerPage = 10;
$currentPage = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// فیلترهای جستجو
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// ساخت query برای جستجوی محصولات
$params = [];
$where = [];

if (!empty($searchQuery)) {
    $where[] = "(p.name LIKE :search OR p.barcode LIKE :search OR p.accounting_code LIKE :search)";
    $params[':search'] = "%{$searchQuery}%";
}

if ($categoryFilter > 0) {
    $where[] = "p.category_id = :category";
    $params[':category'] = $categoryFilter;
}

if ($stockFilter === 'low') {
    $where[] = "p.current_stock <= p.reorder_point";
} elseif ($stockFilter === 'out') {
    $where[] = "p.current_stock = 0";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// دریافت تعداد کل محصولات
$countQuery = "SELECT COUNT(*) FROM products p $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// دریافت لیست محصولات
$query = "SELECT 
    p.*, 
    c.name as category_name,
    COALESCE(SUM(CASE WHEN t.transaction_type = 'sale' THEN t.quantity ELSE 0 END), 0) as total_sales
FROM products p 
LEFT JOIN categories c ON p.category_id = c.id 
LEFT JOIN inventory_transactions t ON p.id = t.product_id 
$whereClause 
GROUP BY p.id 
ORDER BY $sortBy $sortOrder 
LIMIT :offset, :limit";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$products = $stmt->fetchAll();

// افزودن محصولات انتخاب شده به سبد فروش
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $selectedProducts = $_POST['selected_products'] ?? [];
    foreach ($selectedProducts as $productId) {
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 1;
        }
    }
    
    $_SESSION['success_message'] = "محصولات انتخاب شده به سبد فروش اضافه شدند.";
    header('Location: index.php?page=sales');
    exit;
}
?>

<!-- CSS های اضافی -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
.product-card {
    transition: all 0.3s ease;
    border-radius: 15px;
    overflow: hidden;
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
}

.product-image {
    height: 200px;
    object-fit: cover;
    border-radius: 15px 15px 0 0;
}

.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    border-radius: 20px;
    padding: 5px 15px;
}

.category-tag {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(52, 152, 219, 0.9);
    color: white;
    border-radius: 20px;
    padding: 5px 15px;
    font-size: 0.8rem;
}

.product-price {
    font-size: 1.2rem;
    font-weight: bold;
    color: #2ecc71;
}

.product-details {
    padding: 15px;
}

.filters-section {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

.sort-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.view-toggle-btn {
    border-radius: 10px;
    padding: 8px 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.view-toggle-btn.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination {
    margin-top: 20px;
}

.pagination .page-link {
    border-radius: 10px;
    margin: 0 3px;
    color: #3498db;
}

.pagination .page-item.active .page-link {
    background-color: #3498db;
    border-color: #3498db;
}

.product-checkbox {
    width: 20px;
    height: 20px;
    margin: 10px;
}

.stats-card {
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
}

.stats-icon {
    font-size: 2rem;
    margin-bottom: 10px;
}

.stats-title {
    font-size: 0.9rem;
    opacity: 0.8;
}

.stats-value {
    font-size: 1.5rem;
    font-weight: bold;
}

.table-view th {
    background-color: #f8f9fa;
}

.table-view tr:hover {
    background-color: #f8f9fa;
}

</style>

<div class="container-fluid py-4">
    <!-- آمار کلی -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-box-seam stats-icon"></i>
                <div class="stats-title">کل محصولات</div>
                <div class="stats-value"><?php echo $totalItems; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(45deg, #2ecc71, #27ae60);">
                <i class="bi bi-graph-up stats-icon"></i>
                <div class="stats-title">محصولات فعال</div>
                <div class="stats-value">
                    <?php
                    $activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE current_stock > 0")->fetchColumn();
                    echo $activeProducts;
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(45deg, #e74c3c, #c0392b);">
                <i class="bi bi-exclamation-triangle stats-icon"></i>
                <div class="stats-title">موجودی کم</div>
                <div class="stats-value">
                    <?php
                    $lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE current_stock <= reorder_point")->fetchColumn();
                    echo $lowStock;
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(45deg, #f39c12, #d35400);">
                <i class="bi bi-currency-dollar stats-icon"></i>
                <div class="stats-title">ارزش موجودی</div>
                <div class="stats-value">
                    <?php
                    $totalValue = $pdo->query("SELECT SUM(current_stock * sales_price) FROM products")->fetchColumn();
                    echo number_format($totalValue) . ' ریال';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- فیلترها و جستجو -->
    <div class="filters-section">
        <form method="GET" class="row align-items-end">
            <input type="hidden" name="page" value="products">
            <div class="col-md-3">
                <label class="form-label">جستجو</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="نام، بارکد یا کد محصول..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">دسته‌بندی</label>
                <select name="category" class="form-select">
                    <option value="">همه</option>
                    <?php
                    $categories = $pdo->query("SELECT * FROM categories ORDER BY name");
                    while ($category = $categories->fetch()) {
                        $selected = $categoryFilter == $category['id'] ? 'selected' : '';
                        echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">وضعیت موجودی</label>
                <select name="stock" class="form-select">
                    <option value="">همه</option>
                    <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>موجودی کم</option>
                    <option value="out" <?php echo $stockFilter === 'out' ? 'selected' : ''; ?>>ناموجود</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">مرتب‌سازی</label>
                <select name="sort" class="form-select">
                    <option value="id" <?php echo $sortBy === 'id' ? 'selected' : ''; ?>>جدیدترین</option>
                    <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>نام</option>
                    <option value="current_stock" <?php echo $sortBy === 'current_stock' ? 'selected' : ''; ?>>موجودی</option>
                    <option value="sales_price" <?php echo $sortBy === 'sales_price' ? 'selected' : ''; ?>>قیمت</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">ترتیب</label>
                <select name="order" class="form-select">
                    <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>نزولی</option>
                    <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>صعودی</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i>
                    اعمال فیلتر
                </button>
                <a href="?page=products" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- نوار ابزار -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2">
            <a href="index.php?page=add-product" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                افزودن محصول
            </a>
            <button type="button" class="btn btn-success" id="addToCartBtn" style="display: none;">
                <i class="bi bi-cart-plus"></i>
                افزودن به سبد فروش
            </button>
        </div>
        <div class="sort-section">
            <span>نمایش:</span>
            <button type="button" class="view-toggle-btn active" data-view="grid">
                <i class="bi bi-grid"></i>
            </button>
            <button type="button" class="view-toggle-btn" data-view="table">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <form method="POST" id="productsForm">
        <!-- نمای جدولی محصولات -->
        <div class="table-view" style="display: none;">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>تصویر</th>
                            <th>نام محصول</th>
                            <th>کد</th>
                            <th>دسته‌بندی</th>
                            <th>قیمت فروش</th>
                            <th>موجودی</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox">
                            </td>
                            <td>
                                <?php if (!empty($product['images'])): ?>
                                <img src="<?php echo json_decode($product['images'], true)[0]; ?>" width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                <div class="bg-light text-center" style="width: 50px; height: 50px; border-radius: 5px;">
                                    <i class="bi bi-image text-muted" style="font-size: 1.5rem; line-height: 50px;"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['accounting_code']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo number_format($product['sales_price']); ?> ریال</td>
                            <td><?php echo $product['current_stock']; ?></td>
                            <td>
                                <?php if ($product['current_stock'] <= 0): ?>
                                <span class="badge bg-danger">ناموجود</span>
                                <?php elseif ($product['current_stock'] <= $product['reorder_point']): ?>
                                <span class="badge bg-warning">موجودی کم</span>
                                <?php else: ?>
                                <span class="badge bg-success">موجود</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="index.php?page=edit-product&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- نمای کارتی محصولات -->
        <div class="grid-view">
            <div class="row">
                <?php foreach ($products as $product): ?>
                <div class="col-md-3 mb-4">
                    <div class="product-card">
                        <div class="position-relative">
                            <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox position-absolute" style="z-index: 1;">
                            
                            <?php if (!empty($product['images'])): ?>
                            <img src="<?php echo json_decode($product['images'], true)[0]; ?>" class="product-image w-100">
                            <?php else: ?>
                            <div class="bg-light text-center product-image w-100">
                                <i class="bi bi-image text-muted" style="font-size: 3rem; line-height: 200px;"></i>
                            </div>
                            <?php endif; ?>

                            <?php if ($product['current_stock'] <= 0): ?>
                            <span class="stock-badge bg-danger">ناموجود</span>
                            <?php elseif ($product['current_stock'] <= $product['reorder_point']): ?>
                            <span class="stock-badge bg-warning">موجودی کم</span>
                            <?php else: ?>
                            <span class="stock-badge bg-success">موجود</span>
                            <?php endif; ?>

                            <span class="category-tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        </div>

                        <div class="product-details">
                            <h5 class="mb-2"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-muted small">کد: <?php echo htmlspecialchars($product['accounting_code']); ?></div>
                                <div class="product-price"><?php echo number_format($product['sales_price']); ?> ریال</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">موجودی: <?php echo $product['current_stock']; ?></div>
                                <div class="btn-group">
                                    <a href="index.php?page=edit-product&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <input type="hidden" name="add_to_cart" value="1">
    </form>

    <!-- پیج‌بندی -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="صفحه‌بندی" class="d-flex justify-content-center">
        <ul class="pagination">
            <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=products&page_num=<?php echo ($currentPage - 1); ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo !empty($stockFilter) ? '&stock=' . $stockFilter : ''; ?><?php echo !empty($sortBy) ? '&sort=' . $sortBy : ''; ?><?php echo !empty($sortOrder) ? '&order=' . $sortOrder : ''; ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>

            <?php
            $start = max(1, min($currentPage - 2, $totalPages - 4));
            $end = min($totalPages, max(5, $currentPage + 2));
            
            for ($i = $start; $i <= $end; $i++):
            ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="?page=products&page_num=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo !empty($stockFilter) ? '&stock=' . $stockFilter : ''; ?><?php echo !empty($sortBy) ? '&sort=' . $sortBy : ''; ?><?php echo !empty($sortOrder) ? '&order=' . $sortOrder : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=products&page_num=<?php echo ($currentPage + 1); ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo !empty($stockFilter) ? '&stock=' . $stockFilter : ''; ?><?php echo !empty($sortBy) ? '&sort=' . $sortBy : ''; ?><?php echo !empty($sortOrder) ? '&order=' . $sortOrder : ''; ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // تغییر نمای نمایش (جدولی/کارتی)
    $('.view-toggle-btn').click(function() {
        $('.view-toggle-btn').removeClass('active');
        $(this).addClass('active');
        
        const view = $(this).data('view');
        if (view === 'grid') {
            $('.grid-view').show();
            $('.table-view').hide();
        } else {
            $('.grid-view').hide();
            $('.table-view').show();
        }
    });

    // انتخاب همه محصولات
    $('#selectAll').change(function() {
        $('.product-checkbox').prop('checked', $(this).prop('checked'));
        updateAddToCartButton();
    });

    // بررسی وضعیت چک‌باکس‌ها
    $('.product-checkbox').change(function() {
        updateAddToCartButton();
    });

    // نمایش/مخفی کردن دکمه افزودن به سبد
    function updateAddToCartButton() {
        const checkedCount = $('.product-checkbox:checked').length;
        $('#addToCartBtn').toggle(checkedCount > 0);
    }

    // افزودن به سبد فروش
    $('#addToCartBtn').click(function() {
        $('#productsForm').submit();
    });
});

// حذف محصول
function deleteProduct(id) {
    Swal.fire({
        title: 'آیا مطمئن هستید؟',
        text: "این عملیات قابل بازگشت نیست!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'بله، حذف شود',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?page=delete-product&id=${id}`;
        }
    });
}
</script>