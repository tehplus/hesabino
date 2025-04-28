<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">اطلاعات مالی و حسابداری</h3>
        <div class="profit-badge" id="profitStatus">
            سود ناخالص: <span>0</span> تومان
        </div>
    </div>
    <div class="card-body">
        <!-- قیمت‌های اصلی -->
        <div class="pricing-main mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="price-card">
                        <label class="form-label required">قیمت خرید (تومان)</label>
                        <div class="input-group">
                            <input type="text" class="form-control price-input" name="purchase_price" required
                                   data-price-type="purchase">
                            <button class="btn btn-outline-secondary price-history" type="button" 
                                    data-bs-toggle="modal" data-bs-target="#priceHistoryModal"
                                    data-price-type="purchase">
                                <i class="bi bi-clock-history"></i>
                            </button>
                        </div>
                        <div class="price-info">
                            آخرین خرید: <span id="lastPurchasePrice">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="price-card">
                        <label class="form-label required">قیمت فروش (تومان)</label>
                        <div class="input-group">
                            <input type="text" class="form-control price-input" name="selling_price" required
                                   data-price-type="sell">
                            <button class="btn btn-outline-secondary calculator-btn" type="button">
                                <i class="bi bi-calculator"></i>
                            </button>
                        </div>
                        <div class="price-preview mt-1" id="priceInWords"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- لیست قیمت‌ها -->
        <div class="price-lists mb-4">
            <h4 class="section-title mb-3">لیست قیمت‌ها</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">قیمت عمده (تومان)</label>
                    <input type="text" class="form-control price-input" name="wholesale_price"
                           data-price-type="wholesale">
                </div>
                <div class="col-md-4">
                    <label class="form-label">قیمت همکار (تومان)</label>
                    <input type="text" class="form-control price-input" name="partner_price"
                           data-price-type="partner">
                </div>
                <div class="col-md-4">
                    <label class="form-label">قیمت نمایندگی (تومان)</label>
                    <input type="text" class="form-control price-input" name="agency_price"
                           data-price-type="agency">
                </div>
            </div>
        </div>

        <!-- حسابداری -->
        <div class="accounting-section mb-4">
            <h4 class="section-title mb-3">اطلاعات حسابداری</h4>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">کد حسابداری</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="accounting_code" readonly
                               value="<?php echo getNextAccountingCode($pdo); ?>">
                        <div class="input-group-text">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="customAccountingCode">
                                <label class="form-check-label">کد سفارشی</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">گروه حسابداری</label>
                    <select class="form-select select2" name="accounting_group_id">
                        <option value="">انتخاب کنید</option>
                        <?php
                        $groups = $pdo->query("SELECT * FROM accounting_groups ORDER BY name");
                        while ($group = $groups->fetch()) {
                            echo "<option value='{$group['id']}'>{$group['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- مالیات و تخفیفات -->
        <div class="tax-discount-section">
            <h4 class="section-title mb-3">مالیات و تخفیفات</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="tax-card">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="hasTax" name="has_tax">
                            <label class="form-check-label">مشمول مالیات</label>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="tax_rate" value="9"
                                   min="0" max="100" step="0.1" disabled>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="discount-card">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="hasDiscount" name="has_discount">
                            <label class="form-check-label">تخفیف پیش‌فرض</label>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="default_discount"
                                   min="0" max="100" step="0.1" disabled>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="commission-card">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="hasCommission" name="has_commission">
                            <label class="form-check-label">کمیسیون فروش</label>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="commission_rate"
                                   min="0" max="100" step="0.1" disabled>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال تاریخچه قیمت -->
<div class="modal fade" id="priceHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تاریخچه قیمت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>قیمت (تومان)</th>
                                <th>نوع</th>
                                <th>کاربر</th>
                                <th>توضیحات</th>
                            </tr>
                        </thead>
                        <tbody id="priceHistoryBody">
                            <!-- تاریخچه قیمت اینجا لود می‌شود -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال محاسبه‌گر قیمت -->
<div class="modal fade" id="priceCalculatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">محاسبه‌گر قیمت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="calculator-form">
                    <div class="mb-3">
                        <label class="form-label">قیمت خرید (تومان)</label>
                        <input type="text" class="form-control price-input" id="calcPurchasePrice">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">درصد سود</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="calcProfitPercent"
                                   min="0" max="1000" step="0.1">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">هزینه‌های جانبی (تومان)</label>
                        <input type="text" class="form-control price-input" id="calcExtraCosts">
                    </div>
                    <hr>
                    <div class="result-section">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">قیمت نهایی:</label>
                                <div class="final-price" id="calcFinalPrice">0 تومان</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">سود خالص:</label>
                                <div class="net-profit" id="calcNetProfit">0 تومان</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                <button type="button" class="btn btn-primary" id="applyCalculatedPrice">
                    اعمال قیمت
                </button>
            </div>
        </div>
    </div>
</div>