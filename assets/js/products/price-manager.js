class ProductPriceManager {
    constructor() {
        this.specialPrices = [];
        this.init();
    }

    init() {
        this.initPriceInputs();
        this.initCalculator();
        this.bindEvents();
    }

    initPriceInputs() {
        document.querySelectorAll('.price-input').forEach(input => {
            input.addEventListener('input', (e) => {
                this.formatPrice(e.target);
                this.updateProfitMargin();
            });
        });
    }

    formatPrice(input) {
        let value = input.value.replace(/[^\d]/g, '');
        input.value = new Intl.NumberFormat('fa-IR').format(value);
        
        if (input.dataset.priceType === 'main') {
            this.updatePriceInWords(value);
        }
    }

    updatePriceInWords(price) {
        const words = this.numberToWords(price);
        document.getElementById('priceInWords').textContent = words;
    }

    updateProfitMargin() {
        const sellingPrice = this.getPriceValue('selling_price');
        const purchasePrice = this.getPriceValue('purchase_price');
        
        if (purchasePrice > 0 && sellingPrice > 0) {
            const profit = sellingPrice - purchasePrice;
            const margin = (profit / purchasePrice) * 100;
            
            document.getElementById('profitMargin').textContent = `سود: ${margin.toFixed(1)}%`;
            document.getElementById('profitAmount').textContent = 
                `مبلغ سود: ${new Intl.NumberFormat('fa-IR').format(profit)} تومان`;
        }
    }

    addSpecialPrice() {
        const container = document.getElementById('specialPricesContainer');
        const template = this.getSpecialPriceTemplate(this.specialPrices.length);
        container.insertAdjacentHTML('beforeend', template);
        
        // فعال‌سازی تقویم شمسی
        const newRow = container.lastElementChild;
        const dateInputs = newRow.querySelectorAll('.jalali-datepicker');
        dateInputs.forEach(input => {
            new JDate(input, {
                format: 'YYYY/MM/DD',
                autoClose: true,
                position: 'auto'
            });
        });
    }

    // ... ادامه متدها
}

// راه‌اندازی
document.addEventListener('DOMContentLoaded', () => {
    window.productPriceManager = new ProductPriceManager();
});