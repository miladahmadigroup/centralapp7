<?php
/**
 * توابع عمومی سیستم
 */

// بارگذاری سیستم Hook ها
require_once __DIR__ . '/HookSystem.php';

/**
 * دریافت آدرس پایه سایت
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    return $protocol . '://' . $host . ($path === '/' ? '' : $path);
}

/**
 * دریافت تنظیمات سیستم
 */
function getSetting($name, $default = null) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch();
        return $result ? $result['option_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * ذخیره تنظیمات سیستم
 */
function setSetting($name, $value) {
    global $db;
    try {
        $stmt = $db->prepare("INSERT INTO settings (option_name, option_value) VALUES (?, ?) 
                             ON DUPLICATE KEY UPDATE option_value = ?");
        return $stmt->execute([$name, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * تولید کلید API تصادفی
 */
function generateApiKey() {
    return bin2hex(random_bytes(32));
}

/**
 * hash کردن رمز عبور
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * بررسی رمز عبور
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * ارسال پاسخ JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * لاگ کردن خطا
 */
function logError($message, $context = []) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $log .= ' - Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    file_put_contents($logDir . 'error.log', $log . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * لاگ کردن API
 */
function logApi($endpoint, $method, $clientAppId = null, $responseCode = 200, $responseTime = 0) {
    global $db;
    try {
        $stmt = $db->prepare("INSERT INTO api_logs (client_app_id, endpoint, method, ip_address, user_agent, response_code, response_time) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $clientAppId,
            $endpoint,
            $method,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $responseCode,
            $responseTime
        ]);
    } catch (Exception $e) {
        logError('Failed to log API call: ' . $e->getMessage());
    }
}

/**
 * پاک کردن کش
 */
function clearCache($type = 'all') {
    $cacheDir = __DIR__ . '/../cache/';
    
    if ($type === 'all' || $type === 'views') {
        $files = glob($cacheDir . 'views/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }
    
    if ($type === 'all' || $type === 'api') {
        $files = glob($cacheDir . 'api/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }
}

/**
 * بررسی لاگین ادمین
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * دریافت اطلاعات ادمین لاگین شده
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * اعتبارسنجی ایمیل
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * اعتبارسنجی URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * ایمن کردن ورودی HTML
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * بررسی درخواست AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * هدایت به صفحه دیگر
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * نمایش پیام در session
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * دریافت پیام‌های flash
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}
?>