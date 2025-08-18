<?php
/**
 * صفحه ورود به داشبورد
 */

// بارگذاری فایل‌های مورد نیاز
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/functions.php';

// شروع session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// بارگذاری کلاس‌ها
spl_autoload_register(function ($className) {
    $file = __DIR__ . '/../core/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// اتصال به دیتابیس
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die('خطا در اتصال به دیتابیس: ' . $e->getMessage());
}

// اگر قبلاً لاگین است، هدایت به داشبورد
if (isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin');
}

$error = '';

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'نام کاربری و رمز عبور الزامی است';
    } else {
        $auth = new Auth();
        if ($auth->loginAdmin($username, $password)) {
            $redirectUrl = $_SESSION['redirect_after_login'] ?? BASE_URL . '/admin';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectUrl);
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است';
        }
    }
}

$pageTitle = 'ورود به داشبورد';
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap 5 RTL CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- فونت وزیر CDN -->
    <link href="https://cdn.fontcdn.ir/Font/Persian/Vazir/Vazir.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2 class="mb-0">ورود به سیستم</h2>
                <p class="mb-0 mt-2 opacity-75"><?php echo getSetting('site_title', 'اپ مرکزی'); ?></p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">نام کاربری یا ایمیل</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required 
                               autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">رمز عبور</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        ورود به داشبورد
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="<?php echo BASE_URL; ?>" class="text-muted text-decoration-none">
                        بازگشت به صفحه اصلی
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // افکت های تعاملی
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function() {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>در حال ورود...';
                submitBtn.disabled = true;
            });
            
            // افکت focus روی input ها
            const inputs = form.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>