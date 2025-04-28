<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">اطلاعات اصلی محصول</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label required">نام محصول</label>
                    <input type="text" class="form-control" name="name" required
                           data-validation="length" data-validation-length="min3"
                           data-validation-error-msg="نام محصول باید حداقل 3 حرف باشد">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label required">نام انگلیسی</label>
                    <input type="text" class="form-control" name="name_en" required
                           data-validation="alphanumeric"
                           data-validation-error-msg="فقط حروف انگلیسی و اعداد مجاز است">
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label required">برند / سازنده</label>
                    <select class="form-select select2" name="brand_id" required>
                        <option value="">انتخاب کنید</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">کد کالا</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="product_code" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="generateCode">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <div class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="customCode">
                            <label class="form-check-label ms-2" for="customCode">
                                کد سفارشی
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">بارکد</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="barcode">
                        <button class="btn btn-outline-secondary" type="button" id="generateBarcode">
                            <i class="bi bi-upc"></i>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="scanBarcode">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">شناسه کالا</label>
                    <input type="text" class="form-control" name="sku"
                           data-validation="custom"
                           data-validation-regexp="^[A-Za-z0-9-_]+$"
                           data-validation-error-msg="فقط حروف، اعداد، خط تیره و زیرخط مجاز است">
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label">خلاصه توضیحات</label>
                    <textarea class="form-control" name="short_description" rows="3"
                              maxlength="300"></textarea>
                    <div class="form-text" id="shortDescCounter">0/300</div>
                </div>
            </div>
        </div>
    </div>
</div>