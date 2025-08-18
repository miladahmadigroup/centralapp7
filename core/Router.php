<?php
/**
 * کلاس مسیریابی درخواست‌ها
 */
class Router {
    private $uri;
    private $method;
    
    public function __construct() {
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // حذف base path از URI
        $basePath = parse_url(BASE_URL, PHP_URL_PATH);
        if ($basePath && strpos($this->uri, $basePath) === 0) {
            $this->uri = substr($this->uri, strlen($basePath));
        }
        
        $this->uri = '/' . trim($this->uri, '/');
    }
    
    public function handleRequest() {
        // API routes
        if (strpos($this->uri, '/api/') === 0) {
            return $this->handleApi();
        }
        
        // Admin routes
        if (strpos($this->uri, '/admin') === 0) {
            return $this->handleAdmin();
        }
        
        // Default home page
        if ($this->uri === '/' || $this->uri === '') {
            return $this->showHomePage();
        }
        
        // 404 Not Found
        $this->show404();
    }
    
    private function handleApi() {
        $startTime = microtime(true);
        
        try {
            require_once __DIR__ . '/../api/index.php';
        } catch (Exception $e) {
            logError('API Error: ' . $e->getMessage());
            jsonResponse([
                'success' => false,
                'message' => 'خطای داخلی سرور'
            ], 500);
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000, 3);
        logApi($this->uri, $this->method, null, http_response_code(), $responseTime);
    }
    
    private function handleAdmin() {
        $path = str_replace('/admin', '', $this->uri);
        $path = trim($path, '/');
        
        if (empty($path)) {
            $path = 'index';
        }
        
        $file = __DIR__ . '/../admin/' . $path . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        } else {
            $this->show404();
        }
    }
    
    private function showHomePage() {
        $pageTitle = getSetting('site_title', 'اپ مرکزی');
        
        ob_start();
        ?>
        <div class="jumbotron">
            <h1 class="display-4">خوش آمدید به اپ مرکزی</h1>
            <p class="lead">سیستم مدیریت متمرکز اپلیکیشن‌های کلاینت</p>
            <hr class="my-4">
            <p>برای مدیریت سیستم، وارد داشبورد شوید.</p>
            <a class="btn btn-primary btn-lg" href="<?= BASE_URL ?>/admin" role="button">ورود به داشبورد</a>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">مدیریت اپ‌های کلاینت</h5>
                        <p class="card-text">اضافه کردن، ویرایش و مدیریت اپ‌های کلاینت متصل به سیستم</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">سیستم افزونه‌ای</h5>
                        <p class="card-text">افزودن قابلیت‌های جدید از طریق سیستم افزونه‌های قدرتمند</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">API مرکزی</h5>
                        <p class="card-text">ارائه API یکپارچه برای تمام اپ‌های کلاینت</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        
        include __DIR__ . '/../themes/default/layout.php';
    }
    
    private function show404() {
        http_response_code(404);
        $pageTitle = 'صفحه یافت نشد';
        
        ob_start();
        ?>
        <div class="text-center">
            <h1 class="display-1">404</h1>
            <h2>صفحه یافت نشد</h2>
            <p>صفحه‌ای که دنبال آن هستید وجود ندارد.</p>
            <a href="<?= BASE_URL ?>" class="btn btn-primary">بازگشت به صفحه اصلی</a>
        </div>
        <?php
        $content = ob_get_clean();
        
        include __DIR__ . '/../themes/default/layout.php';
    }
}
?>