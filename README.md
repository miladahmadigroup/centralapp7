# راهنمای توسعه افزونه - اپ مرکزی

این راهنما همه چیزی که برای نوشتن افزونه نیاز دارید را شرح می‌دهد.

## مفاهیم کلیدی

**اپ مرکزی** یک سیستم API متمرکز است که:
- API یکپارچه برای چندین اپ کلاینت فراهم می‌کند
- افزونه‌ها عملکردهای جدید اضافه می‌کنند
- هر افزونه می‌تواند API و رابط کاربری داشته باشد

## ساختار افزونه

هر افزونه در پوشه‌ای در `plugins/` قرار می‌گیرد:

```
plugins/my-plugin/
├── plugin.json    # تنظیمات (الزامی)
├── main.php       # کلاس اصلی (الزامی)  
├── docs.json      # مستندات (الزامی)
├── api.php        # API endpoints
├── admin/
│   └── dashboard.php
└── views/
    └── greeting.php
```

## گام 1: ایجاد پوشه و فایل‌های الزامی

### پوشه افزونه
```bash
mkdir plugins/user-manager
cd plugins/user-manager
```

### فایل plugin.json
```json
{
  "name": "user-manager",
  "version": "1.0.0",
  "description": "مدیریت کاربران",
  "author": "نام شما",
  "api_endpoints": ["register", "login"],
  "views": ["login-form"]
}
```

**نکات:**
- `name` باید دقیقاً مطابق نام پوشه باشد
- `api_endpoints` لیست APIهای ارائه شده
- `views` لیست ویوهای قابل استفاده

### فایل main.php
```php
<?php
class UserManagerPlugin {
    private $pluginDir;
    
    public function __construct() {
        $this->pluginDir = basename(dirname(__FILE__));
        add_filter('admin_menu_items', [$this, 'addAdminMenu']);
    }
    
    public function addAdminMenu($menuItems) {
        $menuItems[] = [
            'title' => 'مدیریت کاربران',
            'url' => BASE_URL . '/plugins/' . $this->pluginDir . '/admin/dashboard.php',
            'icon' => 'fas fa-users'
        ];
        return $menuItems;
    }
}

new UserManagerPlugin();
```

**نکات:**
- `basename(dirname(__FILE__))` نام پوشه را تشخیص می‌دهد
- `add_filter('admin_menu_items')` منو به داشبورد اضافه می‌کند

### فایل docs.json
```json
{
  "plugin": {
    "name": "user-manager",
    "display_name": "مدیریت کاربران",
    "version": "1.0.0",
    "description": "سیستم کامل مدیریت کاربران"
  },
  "api_endpoints": [
    {
      "name": "register",
      "method": "POST",
      "description": "ثبت‌نام کاربر جدید",
      "parameters": {
        "required": [
          {"name": "email", "type": "string"},
          {"name": "password", "type": "string"}
        ]
      },
      "example_request": "callCentralAPI('user-manager', 'register', {email: 'test@test.com', password: '123456'})"
    }
  ],
  "views": [
    {
      "name": "login-form",
      "description": "فرم ورود کاربر"
    }
  ]
}
```

## گام 2: ایجاد API

فایل `api.php`:
```php
<?php
$action = $GLOBALS['plugin_action'] ?? '';
$data = $GLOBALS['plugin_data'] ?? [];

switch ($action) {
    case 'register':
        handleRegister($data);
        break;
    case 'login':
        handleLogin($data);
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'عملیات نامعتبر'], 404);
}

function handleRegister($data) {
    global $db;
    
    if (empty($data['email']) || empty($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'ایمیل و رمز عبور الزامی است'], 400);
    }
    
    try {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$data['email'], $hashedPassword]);
        
        jsonResponse(['success' => true, 'message' => 'ثبت‌نام موفق']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'خطا در ثبت‌نام'], 500);
    }
}

function handleLogin($data) {
    global $db;
    
    if (empty($data['email']) || empty($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'ایمیل و رمز عبور الزامی است'], 400);
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($data['password'], $user['password'])) {
            jsonResponse([
                'success' => true,
                'message' => 'ورود موفق',
                'data' => ['user_id' => $user['id'], 'email' => $user['email']]
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'اطلاعات اشتباه است'], 401);
        }
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'خطا در ورود'], 500);
    }
}
```

**نکات:**
- `$GLOBALS['plugin_action']` نام عملیات دریافتی
- `$GLOBALS['plugin_data']` داده‌های ارسالی
- `jsonResponse()` پاسخ JSON برمی‌گرداند
- `global $db` دسترسی به دیتابیس

## گام 3: ایجاد View

فایل `views/login-form.php`:
```php
<?php
$redirect = $redirect ?? '/dashboard';
$theme = $theme ?? 'light';
?>
<div class="login-widget" data-theme="<?php echo $theme; ?>">
    <div class="card">
        <div class="card-header">
            <h5>ورود به سیستم</h5>
        </div>
        <div class="card-body">
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">ایمیل</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">رمز عبور</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">ورود</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    callCentralAPI('user-manager', 'login', data)
        .then(response => {
            if (response.success) {
                localStorage.setItem('user_data', JSON.stringify(response.data));
                window.location.href = '<?php echo $redirect; ?>';
            } else {
                alert(response.message);
            }
        });
});
</script>
```

**نکات:**
- پارامترها با `extract()` در دسترس هستند
- JavaScript باید inline باشد
- از Bootstrap classes استفاده کنید

## گام 4: صفحه مدیریت

فایل `admin/dashboard.php`:
```php
<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function ($className) {
    $file = __DIR__ . '/../../../core/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

global $db;
$db = Database::getInstance()->getConnection();

$auth = new Auth();
$auth->requireAdminLogin();

$pageTitle = 'مدیریت کاربران';

// دریافت لیست کاربران
try {
    $stmt = $db->prepare("SELECT id, email, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}

ob_start();
?>

<h1>مدیریت کاربران</h1>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>ایمیل</th>
                    <th>تاریخ عضویت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../../admin/layout.php';
?>
```

**نکات:**
- مسیر `../../../` برای رسیدن به ریشه
- `spl_autoload_register` برای بارگذاری کلاس‌ها
- `ob_start()` و `ob_get_clean()` برای capture کردن HTML
- `admin/layout.php` برای استفاده از layout سیستم

## گام 5: جدول دیتابیس (اختیاری)

فایل `install.sql`:
```sql
CREATE TABLE IF NOT EXISTS users (
    id int AUTO_INCREMENT PRIMARY KEY,
    email varchar(255) NOT NULL UNIQUE,
    password varchar(255) NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP
);
```

## نصب و استفاده

### نصب افزونه
1. فایل‌های افزونه را در `plugins/user-manager/` کپی کنید
2. به `/admin/plugins` بروید
3. روی "نصب" کلیک کنید
4. روی "فعال‌سازی" کلیک کنید

### استفاده در کلاینت

#### تنظیم اولیه
```javascript
window.CENTRAL_CONFIG = {
    apiKey: 'YOUR_API_KEY',
    baseUrl: 'https://your-central-app.com'
};
```

#### فراخوانی API
```javascript
// ثبت‌نام
callCentralAPI('user-manager', 'register', {
    email: 'user@example.com',
    password: '123456'
}).then(response => {
    console.log(response.message);
});

// ورود
callCentralAPI('user-manager', 'login', {
    email: 'user@example.com',
    password: '123456'
}).then(response => {
    if (response.success) {
        localStorage.setItem('user_data', JSON.stringify(response.data));
    }
});
```

#### استفاده از View
```javascript
getViewFromCentral('user-manager', 'login-form', {
    redirect: '/dashboard',
    theme: 'dark'
}).then(html => {
    document.getElementById('login-container').innerHTML = html;
});
```

## مشکلات رایج و راه‌حل

### خطای "Class Auth not found"
مسیر require_once اشتباه است. از `../../../` استفاده کنید.

### منو در sidebar نمایش داده نمی‌شود
- نام پوشه باید با `name` در plugin.json یکسان باشد
- افزونه باید نصب و فعال باشد

### API کار نمی‌کند
- `api_endpoints` در plugin.json تعریف شده باشد
- فایل api.php موجود باشد
- کلید API معتبر استفاده کنید

## قوانین مهم

1. **نام پوشه = نام افزونه**: دقیقاً مطابق باشند
2. **سه فایل الزامی**: plugin.json، main.php، docs.json
3. **مسیرهای نسبی**: در admin از `../../../` استفاده کنید
4. **امنیت**: همیشه ورودی‌ها را sanitize کنید
5. **خطاها**: از try-catch استفاده کنید

این راهنما کافی است تا افزونه کاملی بنویسید. برای مثال‌های پیشرفته‌تر، فایل `docs/plugin-development-guide.md` را مطالعه کنید.