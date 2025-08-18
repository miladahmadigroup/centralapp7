<?php
/**
 * کلاس مدیریت احراز هویت - تصحیح شده
 */
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * احراز هویت ادمین
     */
    public function loginAdmin($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                
                $stmt = $this->db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            logError('Admin login error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * خروج ادمین
     */
    public function logoutAdmin() {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        session_destroy();
    }
    
    /**
     * بررسی معتبر بودن API Key
     */
    public function validateApiKey($apiKey) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM api_keys WHERE api_key = ? AND status = 'active'");
            $stmt->execute([$apiKey]);
            $key = $stmt->fetch();
            
            if ($key) {
                // بروزرسانی آمار استفاده
                $stmt = $this->db->prepare("UPDATE api_keys SET last_used = NOW(), usage_count = usage_count + 1 WHERE id = ?");
                $stmt->execute([$key['id']]);
                
                return $key;
            }
            
            return false;
        } catch (Exception $e) {
            logError('API key validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تولید JWT Token
     */
    public function generateJWT($payload, $expirationTime = 3600) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expirationTime;
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->getJWTSecret(), true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * اعتبارسنجی JWT Token
     */
    public function validateJWT($token) {
        try {
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return false;
            }
            
            $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
            $signatureProvided = $tokenParts[2];
            
            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
            
            $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->getJWTSecret(), true);
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            if (!hash_equals($base64Signature, $signatureProvided)) {
                return false;
            }
            
            $payloadData = json_decode($payload, true);
            
            if (!$payloadData || $payloadData['exp'] < time()) {
                return false;
            }
            
            return $payloadData;
        } catch (Exception $e) {
            logError('JWT validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * دریافت کلید مخفی JWT
     */
    private function getJWTSecret() {
        static $secret = null;
        
        if ($secret === null) {
            $secret = getSetting('jwt_secret');
            if (!$secret) {
                $secret = bin2hex(random_bytes(32));
                setSetting('jwt_secret', $secret);
            }
        }
        
        return $secret;
    }
    
    /**
     * بررسی دسترسی ادمین
     */
    public function requireAdminLogin() {
        if (!isAdminLoggedIn()) {
            if (isAjaxRequest()) {
                jsonResponse([
                    'success' => false,
                    'message' => 'احراز هویت مورد نیاز است'
                ], 401);
            } else {
                redirect(BASE_URL . '/admin/login');
            }
        }
    }
    
    /**
     * بررسی دسترسی API - تصحیح شده
     */
    public function requireApiAuth() {
        $authHeader = '';
        
        // بررسی header های مختلف
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
            }
        }
        
        // Debug: لاگ کردن header
        logError('API Auth Debug - Header: ' . $authHeader);
        
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            
            // Debug: لاگ کردن token
            logError('API Auth Debug - Token: ' . substr($token, 0, 10) . '...');
            
            $key = $this->validateApiKey($token);
            
            if ($key) {
                return $key;
            }
        }
        
        // Debug: لاگ کردن تمام headers
        logError('API Auth Debug - All Headers: ' . json_encode($_SERVER));
        
        jsonResponse([
            'success' => false,
            'message' => 'کلید API معتبر نیست',
            'debug' => [
                'auth_header' => $authHeader,
                'has_bearer' => strpos($authHeader, 'Bearer ') === 0
            ]
        ], 401);
    }
}
?>