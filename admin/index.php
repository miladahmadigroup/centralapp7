<?php
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
    die('خطا در اتصال به دیتابیس: ' . $e->getMessage());
}

$auth = new Auth();
$auth->requireAdminLogin();

$pageTitle = 'داشبورد مدیریت';

// تابع کمکی
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getTableCount($tableName) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM `$tableName`");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (Exception $e) {
        return 0;
    }
}

// آمار ساده
$stats = [
    'client_apps' => getTableCount('client_apps'),
    'active_apps' => 0,
    'plugins' => 0,
    'active_plugins' => 0,
    'api_calls_24h' => 0
];

// آمار اپ‌های فعال
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM client_apps WHERE status = 'active'");
    $stmt->execute();
    $stats['active_apps'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    // نادیده بگیر
}

// تعداد افزونه‌ها
$pluginsDir = __DIR__ . '/../plugins/';
if (is_dir($pluginsDir)) {
    $stats['plugins'] = count(glob($pluginsDir . '*', GLOB_ONLYDIR));
}

// افزونه‌های فعال
$stats['active_plugins'] = getTableCount('plugins WHERE status = "active"');

// آمار API
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM api_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $stats['api_calls_24h'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    // نادیده بگیر
}

// آخرین فعالیت‌ها
$recent_activities = [];
try {
    $stmt = $db->prepare("SELECT endpoint, method, created_at FROM api_logs ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();
} catch (Exception $e) {
    // نادیده بگیر
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2">داشبورد مدیریت</h1>
</div>

<!-- کارت‌های آمار -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="fas fa-mobile-alt text-primary fa-2x mb-2"></i>
                <h3><?php echo $stats['client_apps']; ?></h3>
                <p class="mb-0">اپ‌های کلاینت</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                <h3><?php echo $stats['active_apps']; ?></h3>
                <p class="mb-0">اپ‌های فعال</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="fas fa-puzzle-piece text-info fa-2x mb-2"></i>
                <h3><?php echo $stats['active_plugins']; ?> / <?php echo $stats['plugins']; ?></h3>
                <p class="mb-0">افزونه‌ها</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="fas fa-code text-warning fa-2x mb-2"></i>
                <h3><?php echo $stats['api_calls_24h']; ?></h3>
                <p class="mb-0">API (24 ساعت)</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- فعالیت‌ها -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0">آخرین فعالیت‌های API</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activities)): ?>
                    <p class="text-muted text-center">هیچ فعالیتی یافت نشد</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Endpoint</th>
                                <th>متد</th>
                                <th>زمان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($activity['endpoint']); ?></code></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $activity['method']; ?></span>
                                </td>
                                <td><?php echo date('H:i:s', strtotime($activity['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- اطلاعات سیستم -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0">اطلاعات سیستم</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">نسخه PHP</small>
                    <div><?php echo PHP_VERSION; ?></div>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">حافظه مصرفی</small>
                    <div><?php echo formatFileSize(memory_get_usage(true)); ?></div>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">زمان اجرا</small>
                    <div><?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- لینک‌های سریع -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0">لینک‌های سریع</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="<?php echo BASE_URL; ?>/admin/client-apps" class="btn btn-outline-primary w-100">
                            اپ‌های کلاینت
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?php echo BASE_URL; ?>/admin/plugins" class="btn btn-outline-success w-100">
                            افزونه‌ها
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?php echo BASE_URL; ?>/admin/api-keys" class="btn btn-outline-info w-100">
                            کلیدهای API
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?php echo BASE_URL; ?>/" class="btn btn-outline-secondary w-100">
                            صفحه اصلی
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>