<?php
/**
 * اپ مرکزی - نقطه ورود اصلی
 */

// بررسی نصب بودن سیستم
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

// بارگذاری تنظیمات
require_once 'config.php';
require_once 'core/functions.php';

// شروع session
session_start();

// بارگذاری کلاس‌های اصلی
spl_autoload_register(function ($className) {
    $file = __DIR__ . '/core/' . $className . '.php';
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

// ایجاد PluginManager global و بارگذاری افزونه‌ها
global $pluginManager;
$pluginManager = new PluginManager();

// شروع Router
try {
    $router = new Router();
    $router->handleRequest();
} catch (Exception $e) {
    logError('Router error: ' . $e->getMessage());
    
    // نمایش صفحه خطا
    http_response_code(500);
    include 'themes/default/header.php';
    echo '<div class="alert alert-danger">خطای داخلی سرور</div>';
    include 'themes/default/footer.php';
}