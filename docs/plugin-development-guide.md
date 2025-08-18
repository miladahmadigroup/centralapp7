# راهنمای کامل توسعه افزونه‌ها
## سیستم اپ مرکزی

### فهرست مطالب
1. [نمای کلی](#نمای-کلی)
2. [امکانات هسته](#امکانات-هسته)
3. [شروع پروژه](#شروع-پروژه)
4. [ساختار فایل‌ها](#ساختار-فایل-ها)
5. [تنظیمات افزونه](#تنظیمات-افزونه)
6. [مستندات افزونه](#مستندات-افزونه)
7. [توسعه API](#توسعه-api)
8. [توسعه Views](#توسعه-views)
9. [صفحات مدیریت](#صفحات-مدیریت)
10. [Hooks و Events](#hooks-و-events)
11. [مدیریت دیتابیس](#مدیریت-دیتابیس)
12. [امنیت](#امنیت)
13. [تست و دیباگ](#تست-و-دیباگ)
14. [بهترین شیوه‌ها](#بهترین-شیوه-ها)
15. [مثال‌های عملی](#مثال-های-عملی)

---

## نمای کلی

### خوش آمدید به دنیای توسعه افزونه!

سیستم اپ مرکزی امکان ایجاد افزونه‌های قدرتمند و منعطف را فراهم می‌کند. شما می‌توانید عملکردهای جدید اضافه کنید، API های سفارشی ایجاد کنید و ویوهای تعاملی بسازید.

### چرا افزونه بسازیم؟

- **قابلیت توسعه:** افزودن عملکردهای جدید بدون تغییر هسته
- **ماژولار بودن:** هر افزونه مستقل و قابل فعال/غیرفعال کردن
- **سازگاری:** تعامل آسان با سایر افزونه‌ها
- **به‌روزرسانی آسان:** بروزرسانی بدون از دست دادن تغییرات

### قبل از شروع

⚠️ **نکات مهم:**
- حتماً مستندات سایر افزونه‌های نصب شده را مطالعه کنید
- از تداخل با عملکردهای سایر افزونه‌ها جلوگیری کنید
- از استانداردهای کدنویسی پیروی کنید
- همواره امنیت را در نظر بگیرید

---

## امکانات هسته

### ویژگی‌های اصلی

#### 🗄️ مدیریت دیتابیس
دسترسی آسان به دیتابیس با کلاس Database و امکان اجرای کوئری‌های پیچیده

#### 🔐 سیستم احراز هویت  
مدیریت کاربران، JWT tokens، و سیستم دسترسی‌های چندسطحه

#### 💾 سیستم کش
کش خودکار برای API و Views با قابلیت تنظیم زمان انقضا

#### 🔌 Hook System
سیستم رویدادها برای ارتباط بین افزونه‌ها و اجرای همزمان عملیات

#### 🌐 API Framework
ساختار آماده برای ایجاد REST API های قدرتمند

#### 👁️ View Engine
سیستم قالب‌بندی برای ایجاد رابط‌های کاربری تعاملی

### منوی مدیریت (مانند WordPress)

```php
// در فایل main.php افزونه
class MyPlugin {
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }
    
    public function addAdminMenu($menuItems) {
        $menuItems[] = [
            'title' => 'مدیریت کاربران',
            'url' => BASE_URL . '/admin/my-plugin/users',
            'icon' => 'fas fa-users',
            'badge' => '5', // اختیاری
            'capability' => 'manage_users' // اختیاری
        ];
        
        return $menuItems;
    }
}
```

### مدیریت رویدادهای همزمان

```php
// ثبت رویداد
add_action('user_registered', function($userData) {
    // ارسال ایمیل خوش‌آمدگویی
    sendWelcomeEmail($userData['email']);
}, 10); // اولویت 10

// اجرای رویداد
do_action('user_registered', $newUserData);

// فیلتر داده‌ها
add_filter('user_display_name', function($name, $user) {
    return strtoupper($name);
}, 10, 2);

$displayName = apply_filters('user_display_name', $user['name'], $user);
```

---

## شروع پروژه

### گام 1: انتخاب نام افزونه
نام باید منحصر به فرد، کوتاه و توصیفی باشد. از حروف کوچک و خط تیره استفاده کنید.

**مثال‌های مناسب:**
- user-management
- online-shop  
- payment-gateway
- email-marketing

### گام 2: ایجاد پوشه افزونه

```bash
mkdir plugins/my-awesome-plugin
cd plugins/my-awesome-plugin
```

### گام 3: بررسی افزونه‌های موجود
قبل از شروع، مستندات افزونه‌های نصب شده را مطالعه کنید:
- لیست API های موجود
- ویوهای قابل استفاده
- Hook های در دسترس
- جداول دیتابیس موجود

---

## ساختار فایل‌ها

```
plugins/my-plugin/
├── plugin.json          # تنظیمات افزونه (الزامی)
├── main.php             # کلاس اصلی افزونه (الزامی)
├── docs.json            # مستندات کامل (الزامی)
├── api.php              # API endpoints (اختیاری)
├── install.sql          # جداول دیتابیس (اختیاری)
├── uninstall.php        # حذف داده‌ها (اختیاری)
├── admin/               # صفحات مدیریت
│   ├── index.php
│   ├── settings.php
│   └── users.php
├── views/               # ویوهای اپ کلاینت
│   ├── login.php
│   ├── register.php
│   └── profile.php
├── assets/              # فایل‌های استاتیک
│   ├── css/
│   ├── js/
│   └── images/
├── includes/            # فایل‌های کمکی
│   ├── functions.php
│   └── class-helper.php
└── languages/           # فایل‌های ترجمه
    ├── fa_IR.po
    └── en_US.po
```

### فایل‌های الزامی

✅ بدون این سه فایل، افزونه قابل نصب نیست:
- **plugin.json** - اطلاعات اساسی افزونه
- **main.php** - کلاس اصلی و منطق افزونه  
- **docs.json** - مستندات کامل افزونه

---

## تنظیمات افزونه

### فایل plugin.json

```json
{
  "name": "user-management",
  "version": "1.0.0",
  "description": "سیستم کامل مدیریت کاربران",
  "author": "نام شما",
  "website": "https://yourwebsite.com",
  "license": "MIT",
  "dependencies": [
    "email-service"
  ],
  "api_endpoints": [
    "login",
    "register", 
    "profile",
    "logout"
  ],
  "views": [
    "login",
    "register",
    "profile"
  ],
  "admin_pages": [
    "users",
    "settings"
  ],
  "database_tables": [
    "users",
    "user_sessions"
  ],
  "hooks": [
    "user_registered",
    "user_login",
    "user_logout"
  ],
  "permissions": [
    "manage_users",
    "edit_user_profiles"
  ]
}
```

### فیلدهای مهم:
- **name:** شناسه یکتای افزونه (فقط حروف، اعداد و خط تیره)
- **dependencies:** افزونه‌هایی که باید فعال باشند
- **api_endpoints:** لیست API های ارائه شده
- **views:** لیست ویوهای قابل استفاده
- **hooks:** رویدادهایی که افزونه ایجاد می‌کند

---

## مستندات افزونه

### فایل docs.json (الزامی)

⚠️ **نکته مهم:** بدون فایل docs.json، افزونه قابل نصب نیست.

```json
{
  "plugin": {
    "name": "user-management",
    "display_name": "مدیریت کاربران", 
    "version": "1.0.0",
    "description": "سیستم کامل مدیریت کاربران",
    "author": "نام شما"
  },
  "api_endpoints": [
    {
      "name": "login",
      "method": "POST",
      "path": "/login",
      "description": "ورود کاربر به سیستم",
      "parameters": {
        "required": [
          {
            "name": "email",
            "type": "string",
            "description": "آدرس ایمیل"
          }
        ]
      },
      "example_request": "callCentralAPI('user-management', 'login', data)"
    }
  ],
  "views": [
    {
      "name": "login",
      "description": "فرم ورود کاربر",
      "usage_example": "getViewFromCentral('user-management', 'login')"
    }
  ],
  "setup": {
    "installation": [
      "فایل‌های افزونه را در پوشه plugins کپی کنید",
      "افزونه را از داشبورد نصب کنید"
    ]
  }
}
```

---

## توسعه API

### فایل api.php

```php
<?php
// دریافت action و data از global
$action = $GLOBALS['plugin_action'] ?? '';
$data = $GLOBALS['plugin_data'] ?? [];

// مسیریابی درخواست‌ها
switch ($action) {
    case 'login':
        handleLogin($data);
        break;
        
    case 'register':
        handleRegister($data);
        break;
        
    case 'profile':
        handleProfile($data);
        break;
        
    default:
        jsonResponse([
            'success' => false,
            'message' => 'عملیات نامعتبر'
        ], 400);
}

function handleLogin($data) {
    global $db;
    
    // اعتبارسنجی
    if (empty($data['email']) || empty($data['password'])) {
        jsonResponse([
            'success' => false,
            'message' => 'ایمیل و رمز عبور الزامی است'
        ], 400);
    }
    
    try {
        // بررسی کاربر
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($data['password'], $user['password'])) {
            // تولید JWT token
            $auth = new Auth();
            $token = $auth->generateJWT([
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);
            
            // اجرای hook
            do_action('user_login', $user);
            
            jsonResponse([
                'success' => true,
                'message' => 'ورود موفق',
                'data' => [
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['name']
                    ],
                    'token' => $token
                ]
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'message' => 'اطلاعات ورود اشتباه است'
            ], 401);
        }
    } catch (Exception $e) {
        logError('Login error: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => 'خطای داخلی سرور'
        ], 500);
    }
}
?>
```

### بهترین شیوه‌های API:
- همیشه اعتبارسنجی ورودی‌ها را انجام دهید
- از try-catch برای مدیریت خطاها استفاده کنید
- کدهای وضعیت HTTP مناسب برگردانید
- خطاها را لاگ کنید
- از Hook ها برای اطلاع‌رسانی به سایر افزونه‌ها استفاده کنید

---

## توسعه Views

ویوها فایل‌های PHP هستند که HTML تولید می‌کنند برای استفاده در اپ‌های کلاینت:

```php
<?php
// views/login.php

// دریافت پارامترها
$redirect_url = $redirect_url ?? '/dashboard';
$theme = $theme ?? 'light';
?>

<div class="central-widget login-widget" data-theme="<?php echo htmlspecialchars($theme); ?>">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">ورود به سیستم</h5>
        </div>
        <div class="card-body">
            <form id="centralLoginForm" data-central-widget="login" data-redirect-url="<?php echo htmlspecialchars($redirect_url); ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">ایمیل</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">رمز عبور</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">ورود</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('centralLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    callCentralAPI('user-management', 'login', data)
        .then(response => {
            if (response.success) {
                localStorage.setItem('central_auth_token', response.data.token);
                window.location.href = this.dataset.redirectUrl || '/';
            } else {
                showAlert('error', response.message);
            }
        });
});
</script>

<style>
.login-widget[data-theme="dark"] {
    background-color: #343a40;
    color: #fff;
}
</style>
```

### نکات مهم Views:
- همیشه پارامترها را sanitize کنید
- از Bootstrap classes استفاده کنید
- RTL را پشتیبانی کنید
- JavaScript را inline بنویسید
- CSS سفارشی را در همان فایل قرار دهید

---

## صفحات مدیریت

صفحات مدیریت در پوشه `admin/` قرار می‌گیرند:

```php
<?php
// admin/users.php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../core/functions.php';

// بررسی دسترسی ادمین
$auth = new Auth();
$auth->requireAdminLogin();

$pageTitle = 'مدیریت کاربران';

// دریافت لیست کاربران
try {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت کاربران</h1>
    <button class="btn btn-primary">افزودن کاربر</button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>ایمیل</th>
                    <th>تاریخ عضویت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
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
include __DIR__ . '/../../admin/layout.php';
?>
```

### اضافه کردن به منوی ادمین:

```php
// در فایل main.php
add_filter('admin_menu_items', function($items) {
    $items[] = [
        'title' => 'کاربران',
        'url' => BASE_URL . '/plugins/user-management/admin/users.php',
        'icon' => 'fas fa-users',
        'badge' => '5'
    ];
    return $items;
});
```

---

## Hooks و Events

### انواع Hooks:

#### Actions
برای اجرای کدهای خاص در نقاط معین

```php
// ثبت action
add_action('user_registered', 'sendWelcomeEmail');

// اجرای action  
do_action('user_registered', $userData);
```

#### Filters  
برای تغییر یا فیلتر کردن داده‌ها

```php
// ثبت filter
add_filter('user_display_name', 'formatUserName');

// اعمال filter
$name = apply_filters('user_display_name', $rawName);
```

### Hook های پیش‌تعریف شده سیستم:
- `admin_menu_items` - اضافه کردن منو به ادمین
- `plugin_activated` - هنگام فعال شدن افزونه
- `plugin_deactivated` - هنگام غیرفعال شدن افزونه
- `api_request_before` - قبل از پردازش API
- `api_request_after` - بعد از پردازش API

### مثال کاربردی:

```php
// افزونه A: مدیریت کاربران
class UserManagement {
    public function __construct() {
        add_action('user_registered', [$this, 'onUserRegister']);
    }
    
    public function registerUser($data) {
        $userId = $this->saveUser($data);
        
        // اجرای hook برای سایر افزونه‌ها
        do_action('user_registered', [
            'user_id' => $userId,
            'email' => $data['email'],
            'name' => $data['name']
        ]);
    }
}

// افزونه B: سیستم ایمیل
class EmailService {
    public function __construct() {
        add_action('user_registered', [$this, 'sendWelcomeEmail']);
    }
    
    public function sendWelcomeEmail($userData) {
        $this->sendEmail(
            $userData['email'],
            'خوش آمدید',
            "سلام {$userData['name']}, به سایت ما خوش آمدید!"
        );
    }
}
```

---

## مدیریت دیتابیس

### فایل install.sql

```sql
-- جدول کاربران
CREATE TABLE IF NOT EXISTS `plugin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول نشست‌های کاربر
CREATE TABLE IF NOT EXISTS `plugin_user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  FOREIGN KEY (`user_id`) REFERENCES `plugin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### استفاده از دیتابیس:

```php
// دریافت اتصال دیتابیس
global $db;

// Insert
$stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$result = $stmt->execute([$name, $email, $hashedPassword]);
$userId = $db->lastInsertId();

// Select
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Update
$stmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
$stmt->execute([$newName, $userId]);

// Delete
$stmt = $db->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);
```

---

## امنیت

### اعتبارسنجی ورودی‌ها:

```php
function validateAndSanitize($data) {
    $clean = [];
    
    // ایمیل
    if (isset($data['email'])) {
        $clean['email'] = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        if (!$clean['email']) {
            throw new Exception('ایمیل معتبر نیست');
        }
    }
    
    // نام
    if (isset($data['name'])) {
        $clean['name'] = trim(strip_tags($data['name']));
        if (strlen($clean['name']) < 2) {
            throw new Exception('نام باید حداقل 2 کاراکتر باشد');
        }
    }
    
    return $clean;
}
```

### محافظت از SQL Injection:

```php
// درست ✅
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = ?");
$stmt->execute([$email, $status]);

// غلط ❌
$query = "SELECT * FROM users WHERE email = '$email'";
$result = $db->query($query);
```

### محافظت از XSS:

```php
// در PHP
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// در JavaScript
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
```

---

## تست و دیباگ

### ابزارهای تست:
1. **فایل تست اعتبارسنجی:** `plugin_validation_test.php`
2. **لاگ خطاها:** `logs/error.log`  
3. **API Testing:** Postman یا curl

### تست API با curl:

```bash
# تست login
curl -X POST "http://localhost/centralapp/api/plugins/user-management/login" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456"}'

# تست profile
curl -X GET "http://localhost/centralapp/api/plugins/user-management/profile" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### دیباگ کردن:

```php
function debugLog($message, $data = null) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data) {
        $log .= ' - Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    file_put_contents(__DIR__ . '/debug.log', $log . PHP_EOL, FILE_APPEND);
}

// استفاده
debugLog('User login attempt', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
```

---

## بهترین شیوه‌ها

### کدنویسی
- از PSR-4 استفاده کنید
- کامنت‌های مفیدی بنویسید  
- متغیرها را معنادار نام‌گذاری کنید
- از try-catch استفاده کنید

### دیتابیس
- همیشه از prepared statements استفاده کنید
- ایندکس‌های مناسب تعریف کنید
- فیلدهای حساس را رمزنگاری کنید
- backup منظم داشته باشید

### تجربه کاربری
- پیام‌های خطای واضح
- Loading states مناسب
- طراحی ریسپانسیو
- پشتیبانی از RTL

### عملکرد
- از کش استفاده کنید
- کوئری‌های بهینه بنویسید
- فایل‌های CSS/JS را minify کنید
- تصاویر را بهینه کنید

---

## مثال‌های عملی

### مثال 1: افزونه ساده شمارنده بازدید

```php
// main.php
class VisitCounterPlugin {
    public function __construct() {
        add_action('page_view', [$this, 'incrementCounter']);
    }
    
    public function incrementCounter($pageId) {
        global $db;
        
        $stmt = $db->prepare("
            INSERT INTO page_visits (page_id, visit_date, visits) 
            VALUES (?, CURDATE(), 1)
            ON DUPLICATE KEY UPDATE visits = visits + 1
        ");
        $stmt->execute([$pageId]);
    }
    
    public function getVisitCount($pageId) {
        global $db;
        
        $stmt = $db->prepare("SELECT SUM(visits) FROM page_visits WHERE page_id = ?");
        $stmt->execute([$pageId]);
        return $stmt->fetchColumn() ?: 0;
    }
}

new VisitCounterPlugin();
```

### مثال 2: ویجت نمایش آب و هوا

```php
// views/weather.php
<?php
$city = $city ?? 'Tehran';
$apiKey = getSetting('weather_api_key');

if ($apiKey) {
    $weatherData = getWeatherData($city, $apiKey);
}

function getWeatherData($city, $apiKey) {
    $url = "http://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
    $response = file_get_contents($url);
    return json_decode($response, true);
}
?>

<div class="weather-widget central-widget">
    <?php if (isset($weatherData) && $weatherData): ?>
    <div class="weather-info">
        <h5><?php echo htmlspecialchars($weatherData['name']); ?></h5>
        <div class="temperature"><?php echo round($weatherData['main']['temp']); ?>°C</div>
        <div class="description"><?php echo htmlspecialchars($weatherData['weather'][0]['description']); ?></div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">اطلاعات آب و هوا در دسترس نیست</div>