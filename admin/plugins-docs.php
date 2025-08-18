<?php
/**
 * API برای دریافت مستندات افزونه‌ها
 */

// بارگذاری فایل‌های مورد نیاز
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/functions.php';

// تنظیم header
header('Content-Type: application/json; charset=utf-8');

// دریافت نام افزونه
$pluginName = $_GET['plugin'] ?? '';

if (empty($pluginName)) {
    jsonResponse([
        'success' => false,
        'message' => 'نام افزونه مشخص نشده'
    ], 400);
}

// مسیر فایل مستندات
$docsFile = __DIR__ . '/../plugins/' . $pluginName . '/docs.json';

if (!file_exists($docsFile)) {
    // اگر فایل docs.json وجود نداشت، مستندات پیش‌فرض تولید کن
    $defaultDocs = generateDefaultDocs($pluginName);
    jsonResponse([
        'success' => true,
        'plugin' => $defaultDocs
    ]);
}

try {
    $docsContent = file_get_contents($docsFile);
    $docs = json_decode($docsContent, true);
    
    if (!$docs) {
        throw new Exception('فایل مستندات معتبر نیست');
    }
    
    // اضافه کردن URL های پایه
    if (isset($docs['plugin'])) {
        $docs['plugin']['base_api_url'] = BASE_URL . '/api/plugins/' . $pluginName;
        $docs['plugin']['base_views_url'] = BASE_URL . '/api/views/' . $pluginName;
    }
    
    jsonResponse([
        'success' => true,
        'plugin' => $docs
    ]);
    
} catch (Exception $e) {
    logError('Plugin docs error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'خطا در بارگذاری مستندات'
    ], 500);
}

/**
 * تولید مستندات پیش‌فرض برای افزونه‌ای که docs.json ندارد
 */
function generateDefaultDocs($pluginName) {
    // خواندن فایل plugin.json اگر وجود داشته باشد
    $configFile = __DIR__ . '/../plugins/' . $pluginName . '/plugin.json';
    $config = [];
    
    if (file_exists($configFile)) {
        $configContent = file_get_contents($configFile);
        $config = json_decode($configContent, true) ?: [];
    }
    
    return [
        'plugin' => [
            'name' => $pluginName,
            'display_name' => $config['description'] ?? $pluginName,
            'version' => $config['version'] ?? '1.0.0',
            'description' => $config['description'] ?? 'افزونه سفارشی',
            'author' => $config['author'] ?? 'نامشخص',
            'base_api_url' => BASE_URL . '/api/plugins/' . $pluginName,
            'base_views_url' => BASE_URL . '/api/views/' . $pluginName
        ],
        'api_endpoints' => generateDefaultApiEndpoints($config['api_endpoints'] ?? []),
        'views' => generateDefaultViews($config['views'] ?? []),
        'setup' => [
            'installation' => [
                'فایل‌های افزونه را در پوشه plugins/' . $pluginName . '/ قرار دهید',
                'از طریق داشبورد مدیریت، افزونه را نصب کنید',
                'افزونه را فعال کنید',
                'API Key اپ کلاینت خود را دریافت کنید'
            ],
            'client_configuration' => [
                'step1' => [
                    'description' => 'تنظیم اولیه در اپ کلاینت',
                    'code' => "window.CENTRAL_CONFIG = {\n  apiKey: 'YOUR_API_KEY_HERE',\n  baseUrl: '" . BASE_URL . "'\n};"
                ]
            ]
        ],
        'examples' => [
            'api_usage' => [
                'description' => 'استفاده از API افزونه',
                'javascript' => "callCentralAPI('" . $pluginName . "', 'action_name', data)\n  .then(response => {\n    console.log(response);\n  });"
            ],
            'view_usage' => [
                'description' => 'استفاده از ویوهای افزونه',
                'javascript' => "getViewFromCentral('" . $pluginName . "', 'view_name')\n  .then(html => {\n    document.getElementById('container').innerHTML = html;\n  });"
            ]
        ],
        'troubleshooting' => [
            'common_issues' => [
                [
                    'issue' => 'خطای کلید API معتبر نیست',
                    'solution' => 'API Key را از بخش اپ‌های کلاینت بررسی کنید'
                ],
                [
                    'issue' => 'ویو نمایش داده نمی‌شود',
                    'solution' => 'مطمئن شوید که افزونه فعال است و فایل widgets.js بارگذاری شده'
                ]
            ]
        ]
    ];
}

/**
 * تولید مستندات پیش‌فرض برای API endpoints
 */
function generateDefaultApiEndpoints($endpoints) {
    $docs = [];
    
    foreach ($endpoints as $endpoint) {
        $docs[] = [
            'name' => $endpoint,
            'method' => 'POST',
            'path' => '/' . $endpoint,
            'description' => 'عملیات ' . $endpoint,
            'parameters' => [
                'required' => [],
                'optional' => []
            ],
            'response_success' => [
                'success' => true,
                'message' => 'عملیات با موفقیت انجام شد'
            ],
            'response_error' => [
                'success' => false,
                'message' => 'خطا در انجام عملیات'
            ],
            'example_request' => "callCentralAPI('plugin-name', '" . $endpoint . "', data)"
        ];
    }
    
    return $docs;
}

/**
 * تولید مستندات پیش‌فرض برای Views
 */
function generateDefaultViews($views) {
    $docs = [];
    
    foreach ($views as $view) {
        $docs[] = [
            'name' => $view,
            'description' => 'ویو ' . $view,
            'parameters' => [
                'optional' => []
            ],
            'features' => [
                'ویو قابل تنظیم',
                'سازگار با Bootstrap',
                'پشتیبانی از RTL'
            ],
            'usage_example' => "getViewFromCentral('plugin-name', '" . $view . "')\n  .then(html => {\n    document.getElementById('container').innerHTML = html;\n  });"
        ];
    }
    
    return $docs;
}
?>