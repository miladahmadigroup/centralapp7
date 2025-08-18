<?php
/**
 * API اصلی سیستم
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = parse_url(BASE_URL, PHP_URL_PATH);
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . trim($uri, '/');

if (strpos($uri, '/api/') === 0) {
    $uri = substr($uri, 4);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($uri, '/'));

try {
    $auth = new Auth();
    $apiKey = $auth->requireApiAuth();
    
    if (count($path) >= 1) {
        switch ($path[0]) {
            case 'plugins':
                handlePluginApi($path, $method, $apiKey);
                break;
                
            case 'views':
                handleViewsApi($path, $method, $apiKey);
                break;
                
            case 'system':
                handleSystemApi($path, $method, $apiKey);
                break;
                
            default:
                jsonResponse([
                    'success' => false,
                    'message' => 'endpoint یافت نشد'
                ], 404);
        }
    } else {
        jsonResponse([
            'success' => true,
            'message' => 'Central App API v1.0',
            'timestamp' => date('c'),
            'api_key' => $apiKey['name']
        ]);
    }
    
} catch (Exception $e) {
    logError('API Error: ' . $e->getMessage(), [
        'uri' => $uri,
        'method' => $method,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    jsonResponse([
        'success' => false,
        'message' => 'خطای داخلی سرور'
    ], 500);
}

function handlePluginApi($path, $method, $apiKey) {
    global $pluginManager;
    
    if (count($path) < 3) {
        jsonResponse([
            'success' => false,
            'message' => 'نام افزونه و عملیات مورد نیاز است'
        ], 400);
    }
    
    $pluginName = $path[1];
    $action = $path[2];
    
    if (!$pluginManager->isPluginActive($pluginName)) {
        jsonResponse([
            'success' => false,
            'message' => 'افزونه فعال نیست'
        ], 404);
    }
    
    $inputData = [];
    if ($method === 'POST' || $method === 'PUT') {
        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true) ?? [];
        $inputData = array_merge($_POST, $inputData);
    } elseif ($method === 'GET') {
        $inputData = $_GET;
    }
    
    try {
        $result = $pluginManager->callPluginApi($pluginName, $action, $inputData);
        
        $decodedResult = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo $result;
        } else {
            jsonResponse([
                'success' => true,
                'data' => $result
            ]);
        }
        
        // لاگ API
        global $db;
        try {
            $stmt = $db->prepare("INSERT INTO api_logs (api_key_id, endpoint, method, ip_address, response_code) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$apiKey['id'], "/plugins/$pluginName/$action", $method, $_SERVER['REMOTE_ADDR'] ?? null, 200]);
        } catch (Exception $e) {
            // خطا در لاگ مهم نیست
        }
        
    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

function handleViewsApi($path, $method, $apiKey) {
    global $pluginManager;
    
    if (count($path) < 3) {
        jsonResponse([
            'success' => false,
            'message' => 'نام افزونه و ویو مورد نیاز است'
        ], 400);
    }
    
    $pluginName = $path[1];
    $viewName = $path[2];
    
    $cache = new Cache();
    $cachedView = $cache->getView($pluginName, $viewName);
    
    if ($cachedView) {
        header('X-Cache: HIT');
        echo $cachedView;
        return;
    }
    
    if (!$pluginManager->isPluginActive($pluginName)) {
        jsonResponse([
            'success' => false,
            'message' => 'افزونه فعال نیست'
        ], 404);
    }
    
    try {
        $params = $_GET;
        unset($params['api_key']);
        
        $html = $pluginManager->getPluginView($pluginName, $viewName, $params);
        
        $cache->setView($pluginName, $viewName, $html);
        
        header('X-Cache: MISS');
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        
    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 404);
    }
}

function handleSystemApi($path, $method, $apiKey) {
    if (count($path) < 2) {
        jsonResponse([
            'success' => false,
            'message' => 'عملیات مورد نیاز است'
        ], 400);
    }
    
    $action = $path[1];
    
    switch ($action) {
        case 'info':
            jsonResponse([
                'success' => true,
                'data' => [
                    'app_name' => getSetting('site_title', 'اپ مرکزی'),
                    'version' => '1.0.0',
                    'api_key' => $apiKey['name'],
                    'server_time' => date('c'),
                    'plugins_count' => count(glob(__DIR__ . '/../plugins/*', GLOB_ONLYDIR))
                ]
            ]);
            break;
            
        case 'ping':
            jsonResponse([
                'success' => true,
                'message' => 'pong',
                'timestamp' => time()
            ]);
            break;
            
        case 'cache-clear':
            if ($method === 'POST') {
                $cache = new Cache();
                $cleared = $cache->clear($_POST['type'] ?? 'all');
                
                jsonResponse([
                    'success' => true,
                    'message' => "تعداد {$cleared} فایل کش پاک شد"
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'متد POST مورد نیاز است'
                ], 405);
            }
            break;
            
        default:
            jsonResponse([
                'success' => false,
                'message' => 'عملیات نامعتبر'
            ], 404);
    }
}
?>