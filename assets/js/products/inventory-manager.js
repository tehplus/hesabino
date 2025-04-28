class ProductInventoryManager {
    constructor() {
        this.suppliers = [];
        this.currentWarehouse = null;
        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeSelect2();
        this.updateStockStatus();
    }

    initializeEventListeners() {
        // کنترل موجودی
        document.querySelector('[name="initial_stock"]').addEventListener('change', (e) => {
            this.validateStock(e.target.value);
            this.updateStockStatus();
        });

        // حد موجودی
        document.querySelector('[name="minimum_stock"]').addEventListener('change', (e) => {
            this.validateMinimumStock(e.target.value);
        });

        // انتخاب انبار
        document.getElementById('warehouseSelect').addEventListener('change', (e) => {
            this.handleWarehouseChange(e.target.value);
        });

        // افزودن تامین‌کننده
        document.getElementById('addSupplier').addEventListener('click', () => {
            this.addSupplierRow();
        });
    }

    validateStock(value) {
        const stock = parseInt(value);
        const minStock = parseInt(document.querySelector('[name="minimum_stock"]').value);
        const reorderPoint = parseInt(document.querySelector('[name="reorder_point"]').value);

        if (stock <= minStock) {
            showToast('هشدار: موجودی کمتر از حداقل مجاز است!', 'warning');
        } else if (stock <= reorderPoint) {
            showToast('توجه: موجودی به نقطه سفارش رسیده است.', 'info');
        }
    }

    updateStockStatus() {
        const stock = parseInt(document.querySelector('[name="initial_stock"]').value) || 0;
        const statusElement = document.getElementById('currentStockStatus');
        
        let statusClass = 'text-success';
        if (stock <= parseInt(document.querySelector('[name="minimum_stock"]').value)) {
            statusClass = 'text-danger';
        } else if (stock <= parseInt(document.querySelector('[name="reorder_point"]').value)) {
            statusClass = 'text-warning';
        }

        statusElement.className = `stock-status ${statusClass}`;
        statusElement.textContent = `موجودی فعلی: ${new Intl.NumberFormat('fa-IR').format(stock)}`;
    }

    handleWarehouseChange(warehouseId) {
        if (warehouseId) {
            // دریافت اطلاعات انبار
            fetch(`/api/warehouses/${warehouseId}`)
                .then(response => response.json())
                .then(data => {
                    this.currentWarehouse = data;
                    this.updateShelfOptions();
                });
        }
    }

    addSupplierRow() {
        const container = document.getElementById('suppliersContainer');
        const rowId = this.suppliers.length + 1;
        
        const template = `
            <div class="supplier-row mb-3" data-id="${rowId}">
                <div class="row g-2">
                    <div class="col-md-4">
                        <select class="form-select select2-supplier" name="suppliers[${rowId}][id]" required>
                            <option value="">انتخاب تامین‌کننده</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="suppliers[${rowId}][price]"
                               placeholder="قیمت خرید">
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="suppliers[${rowId}][delivery_time]"
                               placeholder="زمان تحویل (روز)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                                onclick="productInventoryManager.removeSupplier(${rowId})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', template);
        this.initializeSelect2ForSupplier(rowId);
        this.suppliers.push(rowId);
    }

    removeSupplier(rowId) {
        const row = document.querySelector(`.supplier-row[data-id="${rowId}"]`);
        if (row) {
            Swal.fire({
                title: 'حذف تامین‌کننده',
                text: 'آیا از حذف این تامین‌کننده اطمینان دارید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف'
            }).then((result) => {
                if (result.isConfirmed) {
                    row.remove();
                    this.suppliers = this.suppliers.filter(id => id !== rowId);
                    showToast('تامین‌کننده با موفقیت حذف شد', 'success');
                }
            });
        }
    }

    // ... سایر متدها
}

// راه‌اندازی
document.addEventListener('DOMContentLoaded', () => {
    window.productInventoryManager = new ProductInventoryManager();
});