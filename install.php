<?php
/**
 * صفحه نصب اپ مرکزی
 */

// بررسی نصب قبلی
if (file_exists(__DIR__ . '/config.php')) {
    header('Location: index.php');
    exit;
}

$step = (int)($_GET['step'] ?? 1);
$errors = [];
$success = false;

// پردازش فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 2) {
        // بررسی اتصال دیتابیس
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = trim($_POST['db_pass'] ?? '');
        
        try {
            $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            
            // اگر رمز عبور خالی باشد، نباید پاس شود
            if (empty($dbPass)) {
                $pdo = new PDO($dsn, $dbUser);
            } else {
                $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            }
            
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // ذخیره اطلاعات دیتابیس در session
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['install_db'] = [
                'host' => $dbHost,
                'name' => $dbName,
                'user' => $dbUser,
                'pass' => $dbPass
            ];
            
            $step = 3;
        } catch (PDOException $e) {
            $errors[] = 'خطا در اتصال به دیتابیس: ' . $e->getMessage();
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 3) {
        // ایجاد جداول و ادمین
        if (!isset($_SESSION)) {
            session_start();
        }
        $dbInfo = $_SESSION['install_db'];
        
        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = trim($_POST['admin_pass'] ?? '');
        $siteTitle = trim($_POST['site_title'] ?? '');
        
        // اعتبارسنجی
        if (empty($adminUser) || strlen($adminUser) < 3) {
            $errors[] = 'نام کاربری باید حداقل 3 کاراکتر باشد';
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'ایمیل معتبر نیست';
        }
        if (empty($adminPass) || strlen($adminPass) < 6) {
            $errors[] = 'رمز عبور باید حداقل 6 کاراکتر باشد';
        }
        
        if (empty($errors)) {
            try {
                $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['name']};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                
                // اگر رمز عبور خالی باشد، نباید پاس شود
                if (empty($dbInfo['pass'])) {
                    $pdo = new PDO($dsn, $dbInfo['user']);
                } else {
                    $pdo = new PDO($dsn, $dbInfo['user'], $dbInfo['pass'], $options);
                }
                
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // خواندن و اجرای schema
                $schema = file_get_contents(__DIR__ . '/database/schema.sql');
                $pdo->exec($schema);
                
                // ایجاد ادمین
                $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$adminUser, $adminEmail, $hashedPass]);
                
                // ذخیره تنظیمات
                $stmt = $pdo->prepare("INSERT INTO settings (option_name, option_value) VALUES (?, ?)");
                $stmt->execute(['site_title', $siteTitle]);
                $stmt->execute(['site_url', getBaseUrl()]);
                $stmt->execute(['install_date', date('Y-m-d H:i:s')]);
                
                // ایجاد فایل config
                $baseUrl = getBaseUrl();
                $configContent = "<?php\n";
                $configContent .= "define('DB_HOST', '{$dbInfo['host']}');\n";
                $configContent .= "define('DB_NAME', '{$dbInfo['name']}');\n";
                $configContent .= "define('DB_USER', '{$dbInfo['user']}');\n";
                $configContent .= "define('DB_PASS', '{$dbInfo['pass']}');\n";
                $configContent .= "define('BASE_URL', '{$baseUrl}');\n";
                $configContent .= "define('INSTALLED', true);\n";
                $configContent .= "?>";
                
                file_put_contents(__DIR__ . '/config.php', $configContent);
                
                // پاک کردن session
                unset($_SESSION['install_db']);
                
                $success = true;
                $step = 4;
                
            } catch (Exception $e) {
                $errors[] = 'خطا در نصب: ' . $e->getMessage();
            }
        }
    }
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    return $protocol . '://' . $host . ($path === '/' ? '' : $path);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب اپ مرکزی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.fontcdn.ir/Font/Persian/Vazir/Vazir.css" rel="stylesheet">
    <style>
        body { font-family: 'Vazir', sans-serif; background: #f8f9fa; }
        .install-container { max-width: 600px; margin: 50px auto; }
        .step-indicator { margin-bottom: 30px; }
        .step { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center; 
                border-radius: 50%; margin: 0 10px; background: #6c757d; color: white; }
        .step.active { background: #0d6efd; }
        .step.completed { background: #198754; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="card">
                <div class="card-header text-center">
                    <h2>نصب اپ مرکزی</h2>
                    <div class="step-indicator">
                        <span class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</span>
                        <span class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</span>
                        <span class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</span>
                        <span class="step <?= $step >= 4 ? 'active' : '' ?>">4</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($step == 1): ?>
                        <h4>خوش آمدید</h4>
                        <p>برای نصب اپ مرکزی، ابتدا نیازمندی‌های سیستم را بررسی می‌کنیم:</p>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>PHP 7.4+</span>
                                <span class="badge bg-<?= version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'danger' ?>">
                                    <?= PHP_VERSION ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>PDO MySQL</span>
                                <span class="badge bg-<?= extension_loaded('pdo_mysql') ? 'success' : 'danger' ?>">
                                    <?= extension_loaded('pdo_mysql') ? 'موجود' : 'ناموجود' ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>فایل config.php قابل نوشتن</span>
                                <span class="badge bg-<?= is_writable(__DIR__) ? 'success' : 'danger' ?>">
                                    <?= is_writable(__DIR__) ? 'بله' : 'خیر' ?>
                                </span>
                            </li>
                        </ul>
                        <div class="mt-3">
                            <a href="?step=2" class="btn btn-primary">ادامه</a>
                        </div>

                    <?php elseif ($step == 2): ?>
                        <h4>تنظیمات دیتابیس</h4>
                        <form method="post">
                            <input type="hidden" name="step" value="2">
                            <div class="mb-3">
                                <label class="form-label">هاست دیتابیس</label>
                                <input type="text" name="db_host" class="form-control" value="localhost" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نام دیتابیس</label>
                                <input type="text" name="db_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نام کاربری دیتابیس</label>
                                <input type="text" name="db_user" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رمز عبور دیتابیس</label>
                                <input type="password" name="db_pass" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">تست اتصال</button>
                        </form>

                    <?php elseif ($step == 3): ?>
                        <h4>ایجاد حساب مدیریت</h4>
                        <form method="post">
                            <input type="hidden" name="step" value="3">
                            <div class="mb-3">
                                <label class="form-label">عنوان سایت</label>
                                <input type="text" name="site_title" class="form-control" value="اپ مرکزی" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نام کاربری مدیر</label>
                                <input type="text" name="admin_user" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ایمیل مدیر</label>
                                <input type="email" name="admin_email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رمز عبور مدیر</label>
                                <input type="password" name="admin_pass" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success">نصب</button>
                        </form>

                    <?php elseif ($step == 4 && $success): ?>
                        <div class="text-center">
                            <div class="alert alert-success">
                                <h4>✅ نصب با موفقیت انجام شد!</h4>
                                <p>اپ مرکزی شما آماده استفاده است.</p>
                            </div>
                            <a href="index.php" class="btn btn-primary">ورود به سایت</a>
                            <a href="admin/" class="btn btn-secondary">ورود به داشبورد</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>