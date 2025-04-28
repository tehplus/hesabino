<?php
// بررسی درخواست ثبت فروش
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    try {
        $pdo->beginTransaction();
        
        // ایجاد فاکتور
        $invoice_number = date('Ymd') . rand(1000, 9999);
        $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, customer_name, total_amount, discount, final_amount, status) VALUES (?, ?, ?, ?, ?, 'final')");
        $stmt->execute([
            $invoice_number,
            $_POST['customer_name'],
            $_POST['total_amount'],
            $_POST['discount'],
            $_POST['final_amount']
        ]);
        
        $invoice_id = $pdo->lastInsertId();
        
        // ثبت آیتم‌های فاکتور
        $items = json_decode($_POST['invoice_items'], true);
        $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, item_type, item_id, quantity, price, discount) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $stmt->execute([
                $invoice_id,
                $item['type'],
                $item['id'],
                $item['quantity'],
                $item['price'],
                $item['discount'] ?? 0
            ]);
        }
        
        $pdo->commit();
        $success_message = "فاکتور با موفقیت ثبت شد.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "خطا در ثبت فاکتور: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">فروش جدید</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form id="saleForm" method="POST">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">نام مشتری</label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6>افزودن آیتم</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <select id="itemType" class="form-select">
                                        <option value="product">محصول</option>
                                        <option value="service">خدمت</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select id="itemId" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="itemQuantity" class="form-control" value="1" min="1">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-secondary w-100" onclick="addItemToInvoice()">
                                        افزودن به فاکتور
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table" id="invoiceItems">
                                <thead>
                                    <tr>
                                        <th>نوع</th>
                                        <th>نام</th>
                                        <th>تعداد</th>
                                        <th>قیمت واحد</th>
                                        <th>تخفیف</th>
                                        <th>قیمت کل</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-start">جمع کل:</td>
                                        <td colspan="2">
                                            <span id="totalAmount">0</span> تومان
                                            <input type="hidden" name="total_amount" value="0">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-start">تخفیف:</td>
                                        <td colspan="2">
                                            <input type="number" name="discount" class="form-control" value="0" onchange="calculateFinal()">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-start">مبلغ نهایی:</td>
                                        <td colspan="2">
                                            <span id="finalAmount">0</span> تومان
                                            <input type="hidden" name="final_amount" value="0">
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <input type="hidden" name="invoice_items" id="invoiceItemsData">
                        
                        <div class="text-end mt-4">
                            <button type="submit" name="create_invoice" class="btn btn-primary">
                                ثبت فاکتور
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">راهنمای ثبت فاکتور</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>مراحل ثبت فاکتور:</h6>
                        <ol>
                            <li>نام مشتری را وارد کنید</li>
                            <li>نوع آیتم (محصول یا خدمت) را انتخاب کنید</li>
                            <li>آیتم مورد نظر را از لیست انتخاب کنید</li>
                            <li>تعداد را مشخص کنید</li>
                            <li>روی دکمه "افزودن به فاکتور" کلیک کنید</li>
                            <li>در صورت نیاز تخفیف را وارد کنید</li>
                            <li>در نهایت فاکتور را ثبت کنید</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let items = [];

// بارگذاری آیتم‌ها بر اساس نوع انتخاب شده
document.getElementById('itemType').addEventListener('change', function() {
    const type = this.value;
    const select = document.getElementById('itemId');
    
    fetch(`ajax/get_items.php?type=${type}`)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">انتخاب کنید</option>';
            data.forEach(item => {
                select.innerHTML += `<option value="${item.id}" data-price="${item.price}">${item.name}</option>`;
            });
        });
});

function addItemToInvoice() {
    const type = document.getElementById('itemType').value;
    const select = document.getElementById('itemId');
    const quantity = parseInt(document.getElementById('itemQuantity').value);
    
    if (!select.value) return;
    
    const option = select.options[select.selectedIndex];
    const price = parseFloat(option.dataset.price);
    
    const item = {
        type: type,
        id: select.value,
        name: option.text,
        quantity: quantity,
        price: price,
        total: price * quantity
    };
    
    items.push(item);
    updateInvoiceTable();
}

function updateInvoiceTable() {
    const tbody = document.querySelector('#invoiceItems tbody');
    tbody.innerHTML = '';
    
    let total = 0;
    
    items.forEach((item, index) => {
        tbody.innerHTML += `
            <tr>
                <td>${item.type === 'product' ? 'محصول' : 'خدمت'}</td>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>${number_format(item.price)} تومان</td>
                <td>0</td>
                <td>${number_format(item.total)} تومان</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        total += item.total;
    });
    
    document.getElementById('totalAmount').textContent = number_format(total);
    document.querySelector('input[name="total_amount"]').value = total;
    document.getElementById('invoiceItemsData').value = JSON.stringify(items);
    
    calculateFinal();
}

function calculateFinal() {
    const total = parseFloat(document.querySelector('input[name="total_amount"]').value);
    const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
    const final = total - discount;
    
    document.getElementById('finalAmount').textContent = number_format(final);
    document.querySelector('input[name="final_amount"]').value = final;
}

function removeItem(index) {
    items.splice(index, 1);
    updateInvoiceTable();
}

function number_format(number) {
    return new Intl.NumberFormat('fa-IR').format(number);
}
</script>