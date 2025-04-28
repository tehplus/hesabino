class AccountingCodeGenerator {
    constructor() {
        this.codePrefix = '0001';
        this.customCodeEnabled = false;
    }

    async getLastCode() {
        try {
            const response = await fetch('ajax/get-last-product-code.php');
            const data = await response.json();
            return data.last_code || this.codePrefix;
        } catch (error) {
            console.error('Error fetching last code:', error);
            return this.codePrefix;
        }
    }

    async generateNextCode() {
        const lastCode = await this.getLastCode();
        const numericPart = parseInt(lastCode);
        return String(numericPart + 1).padStart(4, '0');
    }

    toggleCustomCode() {
        this.customCodeEnabled = !this.customCodeEnabled;
        const codeInput = document.getElementById('productCode');
        const generateBtn = document.getElementById('generateCodeBtn');
        
        codeInput.readOnly = !this.customCodeEnabled;
        generateBtn.disabled = this.customCodeEnabled;
        
        if (!this.customCodeEnabled) {
            this.generateNextCode().then(code => {
                codeInput.value = code;
            });
        }
    }
}