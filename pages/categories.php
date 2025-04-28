<?php
// بررسی درخواست افزودن دسته‌بندی جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $parent_id = $_POST['parent_id'] ? $_POST['parent_id'] : null;
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $parent_id]);
        $success_message = "دسته‌بندی جدید با موفقیت افزوده شد.";
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">مدیریت دسته‌بندی‌ها</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i>
                        افزودن دسته‌بندی
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
                                    <th>نام دسته‌بندی</th>
                                    <th>دسته‌بندی والد</th>
                                    <th>تعداد محصولات</th>
                                    <th>تعداد خدمات</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT c.*, 
                                           parent.name as parent_name,
                                           COUNT(DISTINCT p.id) as product_count,
                                           COUNT(DISTINCT s.id) as service_count
                                    FROM categories c
                                    LEFT JOIN categories parent ON c.parent_id = parent.id
                                    LEFT JOIN products p ON p.category_id = c.id
                                    LEFT JOIN services s ON s.category_id = c.id
                                    GROUP BY c.id
                                    ORDER BY c.id DESC
                                ");
                                while ($row = $stmt->fetch()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['parent_name'] ?? '-'); ?></td>
                                        <td><?php echo $row['product_count']; ?></td>
                                        <td><?php echo $row['service_count']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo $row['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $row['id']; ?>)">
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

<!-- Modal افزودن دسته‌بندی -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن دسته‌بندی جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نام دسته‌بندی</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">دسته‌بندی والد</label>
                        <select name="parent_id" class="form-control">
                            <option value="">بدون والد</option>
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
                    <button type="submit" name="add_category" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>