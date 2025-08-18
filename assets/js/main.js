/**
 * JavaScript اصلی سیستم
 */

// متغیرهای global
window.CentralApp = {
    baseUrl: window.BASE_URL || '',
    apiUrl: window.BASE_URL + '/api' || '/api',
    version: '1.0.0'
};

// اجباری کردن light theme
document.addEventListener('DOMContentLoaded', function() {
    // تنظیم theme به light
    document.documentElement.setAttribute('data-bs-theme', 'light');
    document.documentElement.style.colorScheme = 'light only';
    document.body.style.colorScheme = 'light only';
    
    // Override any Bootstrap theme detection
    const style = document.createElement('style');
    style.textContent = `
        :root { color-scheme: light only !important; }
        * { color-scheme: light only !important; }
        html, html[data-bs-theme="dark"], html[data-bs-theme="auto"] { 
            color-scheme: light only !important; 
        }
        @media (prefers-color-scheme: dark) {
            * { 
                color-scheme: light only !important;
                background-color: initial !important;
            }
            body {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
        }
    `;
    document.head.appendChild(style);
    
    // جلوگیری از تغییر theme توسط JavaScript
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes') {
                if (mutation.attributeName === 'data-bs-theme') {
                    const target = mutation.target;
                    if (target.getAttribute('data-bs-theme') !== 'light') {
                        target.setAttribute('data-bs-theme', 'light');
                    }
                }
                if (mutation.attributeName === 'style') {
                    const target = mutation.target;
                    if (target === document.documentElement || target === document.body) {
                        target.style.colorScheme = 'light only';
                    }
                }
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme', 'style'],
        subtree: true
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['data-bs-theme', 'style']
    });
    
    // Force light theme every 100ms to be sure
    setInterval(function() {
        if (document.documentElement.getAttribute('data-bs-theme') !== 'light') {
            document.documentElement.setAttribute('data-bs-theme', 'light');
        }
        document.documentElement.style.colorScheme = 'light only';
        document.body.style.colorScheme = 'light only';
    }, 100);
});

/**
 * نمایش پیام alert
 */
function showAlert(type, message, autoHide = true) {
    const alertTypes = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const alertClass = alertTypes[type] || 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // اضافه کردن alert به ابتدای container
    const container = document.querySelector('.container');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // حذف خودکار پس از 5 ثانیه
        if (autoHide) {
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }
}

/**
 * ارسال درخواست AJAX
 */
function apiCall(endpoint, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(CentralApp.apiUrl + endpoint, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('API Call Error:', error);
            throw error;
        });
}

/**
 * تایید عملیات
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * نمایش loading
 */
function showLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    
    if (element) {
        element.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">در حال بارگذاری...</span></div></div>';
    }
}

/**
 * مخفی کردن loading
 */
function hideLoading(element, originalContent = '') {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    
    if (element) {
        element.innerHTML = originalContent;
    }
}

/**
 * کپی کردن متن به clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAlert('success', 'متن کپی شد');
        }).catch(() => {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showAlert('success', 'متن کپی شد');
    } catch (err) {
        showAlert('error', 'خطا در کپی کردن');
    }
    
    document.body.removeChild(textArea);
}

/**
 * فرمت کردن تاریخ
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fa-IR');
}

/**
 * فرمت کردن اندازه فایل
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 بایت';
    
    const k = 1024;
    const sizes = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * اعتبارسنجی فرم
 */
function validateForm(formElement) {
    const inputs = formElement.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * حذف کلاس invalid از input
 */
function clearValidationErrors(formElement) {
    const inputs = formElement.querySelectorAll('.is-invalid');
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
    });
}

/**
 * تبدیل اعداد انگلیسی به فارسی
 */
function toPersianNumbers(str) {
    const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str.replace(/[0-9]/g, (w) => persianNumbers[+w]);
}

/**
 * تبدیل اعداد فارسی به انگلیسی
 */
function toEnglishNumbers(str) {
    const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    
    for (let i = 0; i < 10; i++) {
        str = str.replace(new RegExp(persianNumbers[i], 'g'), englishNumbers[i]);
    }
    
    return str;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // تنظیم tooltip ها
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // حذف خودکار alert ها
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (!alert.querySelector('.btn-close')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
    
    // event delegation برای دکمه‌های copy
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-copy')) {
            e.preventDefault();
            const text = e.target.dataset.text;
            if (text) {
                copyToClipboard(text);
            }
        }
    });
    
    // پاک کردن validation errors هنگام تایپ
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
        }
    });
});

// Export functions for global use
window.showAlert = showAlert;
window.apiCall = apiCall;
window.confirmAction = confirmAction;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.copyToClipboard = copyToClipboard;
window.formatDate = formatDate;
window.formatFileSize = formatFileSize;
window.validateForm = validateForm;
window.clearValidationErrors = clearValidationErrors;
window.toPersianNumbers = toPersianNumbers;
window.toEnglishNumbers = toEnglishNumbers;