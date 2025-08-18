/**
 * JavaScript ویجت‌های اپ مرکزی
 */

window.CentralWidgets = {
    apiKey: null,
    baseUrl: null,
    
    init: function(config) {
        this.apiKey = config.apiKey;
        this.baseUrl = config.baseUrl;
        
        this.initLoginWidget();
        this.initWalletWidget();
        this.initHeaderWidget();
    },
    
    apiCall: function(endpoint, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + this.apiKey
            }
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        return fetch(this.baseUrl + '/api/' + endpoint, finalOptions)
            .then(response => response.json())
            .catch(error => {
                console.error('Central API Error:', error);
                throw error;
            });
    },
    
    initLoginWidget: function() {
        const loginForms = document.querySelectorAll('[data-central-widget="login"]');
        
        loginForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin(form);
            });
        });
    },
    
    handleLogin: function(form) {
        const email = form.querySelector('[name="email"]').value;
        const password = form.querySelector('[name="password"]').value;
        const submitBtn = form.querySelector('[type="submit"]');
        
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'در حال ورود...';
        submitBtn.disabled = true;
        
        this.apiCall('plugins/user-management/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        })
        .then(response => {
            if (response.success) {
                localStorage.setItem('central_auth_token', response.data.token);
                localStorage.setItem('central_user_data', JSON.stringify(response.data.user));
                
                if (window.onCentralLogin) {
                    window.onCentralLogin(response.data);
                }
                
                if (form.dataset.redirectUrl) {
                    window.location.href = form.dataset.redirectUrl;
                } else {
                    window.location.reload();
                }
            } else {
                this.showError(form, response.message || 'خطا در ورود');
            }
        })
        .catch(error => {
            this.showError(form, 'خطا در ارتباط با سرور');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    },
    
    initWalletWidget: function() {
        const walletElements = document.querySelectorAll('[data-central-widget="wallet"]');
        
        walletElements.forEach(element => {
            this.loadWalletData(element);
        });
    },
    
    loadWalletData: function(element) {
        const userId = element.dataset.userId;
        if (!userId) return;
        
        element.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>';
        
        this.apiCall(`plugins/wallet/balance/${userId}`)
        .then(response => {
            if (response.success) {
                this.renderWalletData(element, response.data);
            } else {
                element.innerHTML = '<div class="text-danger">خطا در بارگذاری اطلاعات</div>';
            }
        })
        .catch(error => {
            element.innerHTML = '<div class="text-danger">خطا در ارتباط با سرور</div>';
        });
    },
    
    renderWalletData: function(element, data) {
        const template = element.dataset.template || 'simple';
        
        if (template === 'simple') {
            element.innerHTML = `
                <div class="wallet-widget">
                    <div class="balance">
                        <span class="amount">${this.formatMoney(data.balance)}</span>
                        <span class="currency">تومان</span>
                    </div>
                </div>
            `;
        } else if (template === 'detailed') {
            element.innerHTML = `
                <div class="wallet-widget">
                    <div class="wallet-header">
                        <h6>کیف پول</h6>
                    </div>
                    <div class="wallet-body">
                        <div class="balance mb-2">
                            <span class="amount">${this.formatMoney(data.balance)}</span>
                            <span class="currency">تومان</span>
                        </div>
                        <div class="wallet-actions">
                            <button class="btn btn-sm btn-primary" onclick="CentralWidgets.showDepositModal()">واریز</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="CentralWidgets.showTransactionsModal()">تراکنش‌ها</button>
                        </div>
                    </div>
                </div>
            `;
        }
    },
    
    initHeaderWidget: function() {
        const headerElements = document.querySelectorAll('[data-central-widget="header"]');
        
        headerElements.forEach(element => {
            this.loadHeaderData(element);
        });
    },
    
    loadHeaderData: function(element) {
        this.apiCall('views/header/main')
        .then(response => {
            if (response.success) {
                element.innerHTML = response.data;
            }
        })
        .catch(error => {
            console.error('Header widget error:', error);
        });
    },
    
    logout: function() {
        const token = localStorage.getItem('central_auth_token');
        
        if (token) {
            this.apiCall('plugins/user-management/logout', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            });
        }
        
        localStorage.removeItem('central_auth_token');
        localStorage.removeItem('central_user_data');
        
        if (window.onCentralLogout) {
            window.onCentralLogout();
        }
        
        window.location.reload();
    },
    
    isLoggedIn: function() {
        const token = localStorage.getItem('central_auth_token');
        return !!token;
    },
    
    getCurrentUser: function() {
        const userData = localStorage.getItem('central_user_data');
        return userData ? JSON.parse(userData) : null;
    },
    
    showError: function(element, message) {
        const errorDiv = element.querySelector('.error-message') || document.createElement('div');
        errorDiv.className = 'error-message alert alert-danger alert-dismissible mt-2';
        errorDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        if (!element.querySelector('.error-message')) {
            element.appendChild(errorDiv);
        }
    },
    
    formatMoney: function(amount) {
        return new Intl.NumberFormat('fa-IR').format(amount);
    },
    
    showDepositModal: function() {
        alert('مودال واریز - TODO');
    },
    
    showTransactionsModal: function() {
        alert('مودال تراکنش‌ها - TODO');
    }
};

window.getViewFromCentral = function(plugin, view, params = {}) {
    return new Promise((resolve, reject) => {
        const queryParams = new URLSearchParams(params);
        const url = `${CentralWidgets.baseUrl}/api/views/${plugin}/${view}?${queryParams}`;
        
        fetch(url, {
            headers: {
                'Authorization': 'Bearer ' + CentralWidgets.apiKey
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch view');
            }
            return response.text();
        })
        .then(html => resolve(html))
        .catch(error => reject(error));
    });
};

window.callCentralAPI = function(plugin, action, data = {}) {
    return CentralWidgets.apiCall(`plugins/${plugin}/${action}`, {
        method: 'POST',
        body: JSON.stringify(data)
    });
};

document.addEventListener('DOMContentLoaded', function() {
    if (window.CENTRAL_CONFIG) {
        CentralWidgets.init(window.CENTRAL_CONFIG);
    }
});