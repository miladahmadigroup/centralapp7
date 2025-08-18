<?php
/**
 * خروج از داشبورد
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

$auth = new Auth();
$auth->logoutAdmin();

redirect(BASE_URL . '/admin/login');
?>