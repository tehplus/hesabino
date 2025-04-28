<?php
// بررسی درخواست افزودن خدمت جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!empty($name) && !empty($price)) {
        $stmt = $pdo->prepare("INSERT INTO services (name, price, category_id, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $category, $description]);
        $success_message = "خدمت جدید با موفقیت افزوده شد.";
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">لیست خدمات</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="bi bi-plus-lg"></i>
                        افزودن خدمت
                    </button>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>کد</th>
                                    <th>نام خدمت</th>
                                    <th>قیمت</th>
                                    <th>دسته‌بندی</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT s.*, c.name as category_name 
                                                   FROM services s 
                                                   LEFT JOIN categories c ON s.category_id = c.id
                                                   ORDER BY s.id DESC");
                                while ($row = $stmt->fetch()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo number_format($row['price']); ?> تومان</td>
                                        <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editService(<?php echo $row['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteService(<?php echo $row['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal افزودن خدمت -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن خدمت جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نام خدمت</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">قیمت (تومان)</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">دسته‌بندی</label>
                        <select name="category" class="form-control">
                            <option value="">انتخاب دسته‌بندی</option>
                            <?php
                            $categories = $pdo->query("SELECT * FROM categories ORDER BY name");
                            while ($category = $categories->fetch()) {
                                echo "<option value='{$category['id']}'>{$category['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">توضیحات</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_service" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>