<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">مدیریت موجودی و انبار</h3>
        <span class="stock-status" id="currentStockStatus">موجودی فعلی: 0</span>
    </div>
    <div class="card-body">
        <!-- موجودی اولیه -->
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label required">موجودی اولیه</label>
                <div class="input-group">
                    <input type="number" class="form-control" name="initial_stock" required
                           min="0" step="1" value="0"
                           data-validation="number">
                    <span class="input-group-text unit-label">عدد</span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">قیمت موجودی اولیه (تومان)</label>
                <input type="text" class="form-control price-input" name="initial_stock_price"
                       data-validation="number">
            </div>
        </div>

        <!-- حد موجودی -->
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label required">حداقل موجودی</label>
                <div class="input-group">
                    <input type="number" class="form-control" name="minimum_stock" required
                           min="0" step="1" value="1">
                    <span class="input-group-text unit-label">عدد</span>
                </div>
                <div class="form-text">هشدار موجودی کم</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">حداکثر موجودی</label>
                <div class="input-group">
                    <input type="number" class="form-control" name="maximum_stock"
                           min="0" step="1">
                    <span class="input-group-text unit-label">عدد</span>
                </div>
                <div class="form-text">برای انبارداری بهینه</div>
            </div>
            <div class="col-md-4">
                <label class="form-label required">نقطه سفارش</label>
                <div class="input-group">
                    <input type="number" class="form-control" name="reorder_point" required
                           min="0" step="1" value="2">
                    <span class="input-group-text unit-label">عدد</span>
                </div>
                <div class="form-text">سفارش مجدد</div>
            </div>
        </div>

        <!-- انبار و محل نگهداری -->
        <div class="warehouse-section mb-4">
            <h4 class="section-title mb-3">محل نگهداری در انبار</h4>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">انبار</label>
                    <select class="form-select select2" name="warehouse_id" id="warehouseSelect">
                        <option value="">انتخاب کنید</option>
                        <?php
                        $warehouses = $pdo->query("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");
                        while ($warehouse = $warehouses->fetch()) {
                            echo "<option value='{$warehouse['id']}'>{$warehouse['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">قفسه/راهرو</label>
                    <input type="text" class="form-control" name="shelf_location"
                           placeholder="مثال: راهرو A، قفسه 3">
                </div>
            </div>
        </div>

        <!-- تنظیمات سفارش -->
        <div class="order-settings mb-4">
            <h4 class="section-title mb-3">تنظیمات سفارش</h4>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">حداقل تعداد سفارش</label>
                    <input type="number" class="form-control" name="minimum_order_quantity"
                           min="1" step="1" value="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">واحد بسته‌بندی</label>
                    <input type="number" class="form-control" name="package_quantity"
                           min="1" step="1" placeholder="تعداد در هر بسته">
                </div>
                <div class="col-md-4">
                    <label class="form-label">زمان تحویل (روز)</label>
                    <input type="number" class="form-control" name="delivery_time"
                           min="0" step="1" placeholder="مدت زمان تحویل">
                </div>
            </div>
        </div>

        <!-- تامین‌کنندگان -->
        <div class="suppliers-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="section-title mb-0">تامین‌کنندگان</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addSupplier">
                    <i class="bi bi-plus"></i>
                    افزودن تامین‌کننده
                </button>
            </div>
            <div id="suppliersContainer">
                <!-- لیست تامین‌کنندگان اینجا اضافه می‌شود -->
            </div>
        </div>
    </div>
</div>