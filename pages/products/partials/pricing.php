<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">قیمت‌گذاری</h3>
    </div>
    <div class="card-body">
        <!-- قیمت اصلی -->
        <div class="mb-4">
            <label class="form-label required">قیمت فروش (تومان)</label>
            <div class="input-group">
                <input type="text" class="form-control price-input" name="selling_price" required
                       data-validation="number" data-validation-allowing="float"
                       data-price-type="main">
                <button class="btn btn-outline-secondary" type="button" id="priceCalculator">
                    <i class="bi bi-calculator"></i>
                </button>
            </div>
            <div class="price-preview mt-1">معادل: <span id="priceInWords"></span> تومان</div>
        </div>

        <!-- قیمت خرید -->
        <div class="mb-4">
            <label class="form-label">قیمت خرید (تومان)</label>
            <input type="text" class="form-control price-input" name="purchase_price"
                   data-validation="number" data-validation-allowing="float"
                   data-price-type="purchase">
            <div class="price-info mt-1">
                <span class="profit-margin" id="profitMargin">سود: 0%</span>
                <span class="profit-amount" id="profitAmount">مبلغ سود: 0 تومان</span>
            </div>
        </div>

        <!-- قیمت‌های ویژه -->
        <div class="special-prices mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">قیمت‌های ویژه</label>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addSpecialPrice">
                    <i class="bi bi-plus"></i>
                    افزودن
                </button>
            </div>
            <div id="specialPricesContainer">
                <!-- قیمت‌های ویژه اینجا اضافه می‌شوند -->
            </div>
        </div>

        <!-- تنظیمات قیمت -->
        <div class="price-settings">
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" id="taxIncluded" name="tax_included">
                <label class="form-check-label" for="taxIncluded">
                    مالیات در قیمت محاسبه شده است
                </label>
            </div>
            
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">درصد مالیات</label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control" name="tax_rate" value="9"
                               min="0" max="100" step="0.1">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">تخفیف</label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control" name="discount"
                               min="0" max="100" step="0.1">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>