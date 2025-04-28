<?php
// بررسی درخواست ثبت فاکتور سریع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quick_invoice'])) {
    try {
        $pdo->beginTransaction();
        
        $invoice_number = date('Ymd') . rand(1000, 9999);
        $customer_name = $_POST['customer_name'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $description = $_POST['description'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, customer_name, total_amount, final_amount, status, description) VALUES (?, ?, ?, ?, 'final', ?)");
        $stmt->execute([$invoice_number, $customer_name, $amount, $amount, $description]);
        
        $pdo->commit();
        $success_message = "فاکتور سریع با موفقیت ثبت شد.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "خطا در ثبت فاکتور: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">صدور فاکتور سریع</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">نام مشتری</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">مبلغ (تومان)</label>
                            <input type="number" name="amount" class="form-control" required min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">توضیحات</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="create_quick_invoice" class="btn btn-primary">
                                صدور فاکتور
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>