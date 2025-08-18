<?php
/**
 * API برای دریافت کلید API برای کپی کردن
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function ($className) {
    $file = __DIR__ . '/../core/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'خطا در اتصال به دیتابیس'
    ], 500);
}

global $db;
$auth = new Auth();

if (!isAdminLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => 'احراز هویت مورد نیاز است'
    ], 401);
}

try {
    // دریافت اولین کلید API فعال
    $stmt = $db->prepare("SELECT api_key FROM api_keys WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $key = $stmt->fetch();
    
    if ($key) {
        jsonResponse([
            'success' => true,
            'api_key' => $key['api_key']
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => 'هیچ کلید API فعالی یافت نشد'
        ]);
    }
    
} catch (Exception $e) {
    logError('Get API key error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'خطا در دریافت کلید API'
    ], 500);
}
?>