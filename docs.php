<?php
/**
 * صفحه مستندات افزونه‌ها
 */

// بارگذاری فایل‌های مورد نیاز
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/functions.php';

// شروع session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// بارگذاری کلاس‌ها
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

$pluginName = $_GET['plugin'] ?? '';

if (empty($pluginName)) {
    header('Location: ' . BASE_URL);
    exit;
}

// بررسی وجود افزونه
$pluginPath = __DIR__ . '/plugins/' . $pluginName;
if (!is_dir($pluginPath)) {
    header('Location: ' . BASE_URL);
    exit;
}

// بارگذاری اطلاعات افزونه
$pluginManager = new PluginManager();
$allPlugins = $pluginManager->getAllPlugins();
$currentPlugin = null;

foreach ($allPlugins as $plugin) {
    if ($plugin['name'] === $pluginName) {
        $currentPlugin = $plugin;
        break;
    }
}

if (!$currentPlugin) {
    header('Location: ' . BASE_URL);
    exit;
}

// بارگذاری مستندات
$docsFile = $pluginPath . '/docs.json';
$docsData = null;

if (file_exists($docsFile)) {
    $docsContent = file_get_contents($docsFile);
    $docsData = json_decode($docsContent, true);
}

if (!$docsData) {
    // تولید مستندات پیش‌فرض
    $docsData = generateDefaultDocs($pluginName, $currentPlugin);
}

// اضافه کردن URL های پایه
if (isset($docsData['plugin'])) {
    $docsData['plugin']['base_api_url'] = BASE_URL . '/api/plugins/' . $pluginName;
    $docsData['plugin']['base_views_url'] = BASE_URL . '/api/views/' . $pluginName;
}

$pageTitle = 'مستندات افزونه ' . ($docsData['plugin']['display_name'] ?? $pluginName);

// تابع تولید مستندات پیش‌فرض
function generateDefaultDocs($pluginName, $pluginConfig) {
    return [
        'plugin' => [
            'name' => $pluginName,
            'display_name' => $pluginConfig['description'] ?? $pluginName,
            'version' => $pluginConfig['version'] ?? '1.0.0',
            'description' => $pluginConfig['description'] ?? 'افزونه سفارشی',
            'author' => $pluginConfig['author'] ?? 'نامشخص',
            'base_api_url' => BASE_URL . '/api/plugins/' . $pluginName,
            'base_views_url' => BASE_URL . '/api/views/' . $pluginName
        ],
        'api_endpoints' => generateDefaultApiEndpoints($pluginConfig['api_endpoints'] ?? []),
        'views' => generateDefaultViews($pluginConfig['views'] ?? []),
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
        ]
    ];
}

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
            ]
        ];
    }
    return $docs;
}

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
            ]
        ];
    }
    return $docs;
}

ob_start();
?>

<style>
.docs-container {
    max-width: 1200px;
    margin: 0 auto;
}

.docs-nav {
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}

.docs-nav .nav-link {
    color: #6c757d !important;
    border: none;
    border-radius: 0.375rem;
    margin-bottom: 0.25rem;
}

.docs-nav .nav-link.active {
    background-color: #0d6efd;
    color: white;
}

.docs-nav .nav-link:hover {
    background-color: #f8f9fa;
    color: #0d6efd;
}

.docs-content {
    padding-right: 2rem;
}

.docs-section {
    margin-bottom: 3rem;
    padding-top: 1rem;
}

.docs-section h2 {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.api-endpoint {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background-color: #f8f9fa;
}

.method-badge {
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.method-get { background-color: #0d6efd; color: white; }
.method-post { background-color: #198754; color: white; }
.method-put { background-color: #ffc107; color: #000; }
.method-delete { background-color: #dc3545; color: white; }

.view-item {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background-color: #ffffff;
}

.code-block {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    margin: 1rem 0;
    overflow-x: auto;
}

.code-block code {
    background: none;
    color: #d63384;
    font-family: 'Courier New', monospace;
}

.installation-steps {
    counter-reset: step-counter;
}

.installation-steps li {
    counter-increment: step-counter;
    margin-bottom: 0.5rem;
    position: relative;
    padding-left: 2rem;
}

.installation-steps li::before {
    content: counter(step-counter);
    position: absolute;
    left: 0;
    top: 0;
    background-color: #0d6efd;
    color: white;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .docs-content {
        padding-right: 0;
    }
    
    .docs-nav {
        position: static;
        max-height: none;
        margin-bottom: 2rem;
    }
}
</style>

<div class="docs-container">
    <!-- Header -->
    <div class="docs-header mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1><?php echo htmlspecialchars($docsData['plugin']['display_name']); ?></h1>
                <p class="lead text-muted"><?php echo htmlspecialchars($docsData['plugin']['description']); ?></p>
                <div class="row">
                    <div class="col-md-6">
                        <strong>نسخه:</strong> <?php echo htmlspecialchars($docsData['plugin']['version']); ?><br>
                        <strong>توسعه‌دهنده:</strong> <?php echo htmlspecialchars($docsData['plugin']['author']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>API Base URL:</strong><br>
                        <code><?php echo htmlspecialchars($docsData['plugin']['base_api_url']); ?></code><br>
                        <strong>Views Base URL:</strong><br>
                        <code><?php echo htmlspecialchars($docsData['plugin']['base_views_url']); ?></code>
                    </div>
                </div>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/plugins" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-2"></i>بازگشت به افزونه‌ها
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Navigation -->
        <div class="col-lg-3">
            <div class="docs-nav">
                <nav class="nav nav-pills flex-column">
                    <a class="nav-link active" href="#overview">نمای کلی</a>
                    <a class="nav-link" href="#installation">نصب و راه‌اندازی</a>
                    <?php if (!empty($docsData['api_endpoints'])): ?>
                    <a class="nav-link" href="#api-endpoints">API Endpoints</a>
                    <?php endif; ?>
                    <?php if (!empty($docsData['views'])): ?>
                    <a class="nav-link" href="#views">Views</a>
                    <?php endif; ?>
                    <a class="nav-link" href="#examples">مثال‌ها</a>
                    <?php if (isset($docsData['troubleshooting'])): ?>
                    <a class="nav-link" href="#troubleshooting">عیب‌یابی</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="col-lg-9">
            <div class="docs-content">
                <!-- نمای کلی -->
                <section id="overview" class="docs-section">
                    <h2>نمای کلی</h2>
                    <p><?php echo htmlspecialchars($docsData['plugin']['description']); ?></p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>ویژگی‌ها</h5>
                            <ul>
                                <?php if (!empty($docsData['api_endpoints'])): ?>
                                <li><?php echo count($docsData['api_endpoints']); ?> API Endpoint</li>
                                <?php endif; ?>
                                <?php if (!empty($docsData['views'])): ?>
                                <li><?php echo count($docsData['views']); ?> View قابل استفاده</li>
                                <?php endif; ?>
                                <li>سازگار با سیستم اپ مرکزی</li>
                                <li>پشتیبانی از کش</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>وضعیت افزونه</h5>
                            <span class="badge bg-<?php 
                                echo $currentPlugin['status'] === 'active' ? 'success' : 
                                     ($currentPlugin['status'] === 'inactive' ? 'warning' : 'secondary'); 
                            ?> fs-6">
                                <?php 
                                echo $currentPlugin['status'] === 'active' ? 'فعال' : 
                                     ($currentPlugin['status'] === 'inactive' ? 'غیرفعال' : 'نصب نشده'); 
                                ?>
                            </span>
                        </div>
                    </div>
                </section>

                <!-- نصب و راه‌اندازی -->
                <section id="installation" class="docs-section">
                    <h2>نصب و راه‌اندازی</h2>
                    
                    <h4>مراحل نصب</h4>
                    <?php if (isset($docsData['setup']['installation'])): ?>
                    <ol class="installation-steps">
                        <?php foreach ($docsData['setup']['installation'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <?php endif; ?>

                    <h4>تنظیم در اپ کلاینت</h4>
                    <?php if (isset($docsData['setup']['client_configuration'])): ?>
                        <?php foreach ($docsData['setup']['client_configuration'] as $stepKey => $step): ?>
                        <div class="mb-3">
                            <h6><?php echo htmlspecialchars($step['description']); ?></h6>
                            <div class="code-block">
                                <code><?php echo htmlspecialchars($step['code']); ?></code>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <!-- API Endpoints -->
                <?php if (!empty($docsData['api_endpoints'])): ?>
                <section id="api-endpoints" class="docs-section">
                    <h2>API Endpoints</h2>
                    
                    <?php foreach ($docsData['api_endpoints'] as $endpoint): ?>
                    <div class="api-endpoint">
                        <h5>
                            <span class="method-badge method-<?php echo strtolower($endpoint['method'] ?? 'post'); ?>">
                                <?php echo strtoupper($endpoint['method'] ?? 'POST'); ?>
                            </span>
                            <?php echo htmlspecialchars($endpoint['path'] ?? $endpoint['name'] ?? $endpoint); ?>
                        </h5>
                        <p><?php echo htmlspecialchars($endpoint['description'] ?? 'عملیات ' . ($endpoint['name'] ?? $endpoint)); ?></p>
                        
                        <?php if (!empty($endpoint['parameters']['required'])): ?>
                        <h6>پارامترهای الزامی</h6>
                        <ul>
                            <?php foreach ($endpoint['parameters']['required'] as $param): ?>
                            <li>
                                <code><?php echo htmlspecialchars($param['name']); ?></code> 
                                (<?php echo htmlspecialchars($param['type']); ?>): 
                                <?php echo htmlspecialchars($param['description']); ?>
                                <?php if (isset($param['example'])): ?>
                                - مثال: <code><?php echo htmlspecialchars($param['example']); ?></code>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        
                        <?php if (!empty($endpoint['parameters']['optional'])): ?>
                        <h6>پارامترهای اختیاری</h6>
                        <ul>
                            <?php foreach ($endpoint['parameters']['optional'] as $param): ?>
                            <li>
                                <code><?php echo htmlspecialchars($param['name']); ?></code> 
                                (<?php echo htmlspecialchars($param['type']); ?>): 
                                <?php echo htmlspecialchars($param['description']); ?>
                                <?php if (isset($param['example'])): ?>
                                - مثال: <code><?php echo htmlspecialchars($param['example']); ?></code>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        
                        <?php if (isset($endpoint['response_success'])): ?>
                        <h6>پاسخ موفق</h6>
                        <div class="code-block">
                            <code><?php echo htmlspecialchars(json_encode($endpoint['response_success'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></code>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($endpoint['example_request'])): ?>
                        <h6>نمونه استفاده</h6>
                        <div class="code-block">
                            <code><?php echo htmlspecialchars($endpoint['example_request']); ?></code>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <!-- Views -->
                <?php if (!empty($docsData['views'])): ?>
                <section id="views" class="docs-section">
                    <h2>Views قابل استفاده</h2>
                    
                    <?php foreach ($docsData['views'] as $view): ?>
                    <div class="view-item">
                        <h5><?php echo htmlspecialchars($view['name'] ?? $view); ?></h5>
                        <p><?php echo htmlspecialchars($view['description'] ?? 'ویو ' . ($view['name'] ?? $view)); ?></p>
                        
                        <?php if (!empty($view['parameters']['optional'])): ?>
                        <h6>پارامترها</h6>
                        <ul>
                            <?php foreach ($view['parameters']['optional'] as $param): ?>
                            <li>
                                <code><?php echo htmlspecialchars($param['name']); ?></code> 
                                (<?php echo htmlspecialchars($param['type']); ?>): 
                                <?php echo htmlspecialchars($param['description']); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        
                        <?php if (!empty($view['features'])): ?>
                        <h6>ویژگی‌ها</h6>
                        <ul>
                            <?php foreach ($view['features'] as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        
                        <?php if (isset($view['usage_example'])): ?>
                        <h6>نمونه استفاده</h6>
                        <div class="code-block">
                            <code><?php echo htmlspecialchars($view['usage_example']); ?></code>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <!-- مثال‌ها -->
                <section id="examples" class="docs-section">
                    <h2>مثال‌های کاربردی</h2>
                    
                    <?php if (isset($docsData['examples'])): ?>
                        <?php foreach ($docsData['examples'] as $exampleKey => $example): ?>
                        <div class="mb-4">
                            <h5><?php echo htmlspecialchars($example['description']); ?></h5>
                            <?php if (isset($example['html'])): ?>
                            <h6>HTML</h6>
                            <div class="code-block">
                                <code><?php echo htmlspecialchars($example['html']); ?></code>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($example['javascript'])): ?>
                            <h6>JavaScript</h6>
                            <div class="code-block">
                                <code><?php echo htmlspecialchars($example['javascript']); ?></code>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <!-- عیب‌یابی -->
                <?php if (isset($docsData['troubleshooting'])): ?>
                <section id="troubleshooting" class="docs-section">
                    <h2>عیب‌یابی</h2>
                    
                    <?php if (isset($docsData['troubleshooting']['common_issues'])): ?>
                    <h5>مشکلات رایج</h5>
                    <?php foreach ($docsData['troubleshooting']['common_issues'] as $issue): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>مشکل:</strong> <?php echo htmlspecialchars($issue['issue']); ?>
                        </div>
                        <div class="card-body">
                            <strong>راه‌حل:</strong> <?php echo htmlspecialchars($issue['solution']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Smooth scrolling for navigation links
document.querySelectorAll('.docs-nav a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Update active navigation item on scroll
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('.docs-section');
    const navLinks = document.querySelectorAll('.docs-nav .nav-link');
    
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop - 100;
        if (window.pageYOffset >= sectionTop) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/themes/default/layout.php';
?>