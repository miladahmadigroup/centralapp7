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

$pageTitle = 'مدیریت افزونه‌ها';

global $pluginManager;
if (!$pluginManager) {
    $pluginManager = new PluginManager();
}

// پردازش عملیات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pluginName = $_POST['plugin_name'] ?? '';
    
    try {
        switch ($action) {
            case 'install':
                $pluginManager->installPlugin($pluginName);
                setFlashMessage('success', 'افزونه با موفقیت نصب شد');
                break;
                
            case 'activate':
                $pluginManager->activatePlugin($pluginName);
                setFlashMessage('success', 'افزونه فعال شد');
                break;
                
            case 'deactivate':
                $pluginManager->deactivatePlugin($pluginName);
                setFlashMessage('success', 'افزونه غیرفعال شد');
                break;
                
            case 'uninstall':
                $pluginManager->uninstallPlugin($pluginName);
                setFlashMessage('success', 'افزونه حذف شد');
                break;
        }
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
    
    redirect(BASE_URL . '/admin/plugins');
}

$allPlugins = $pluginManager->getAllPlugins();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">مدیریت افزونه‌ها</h1>
</div>

<?php if (empty($allPlugins)): ?>
    <div class="text-center py-5">
        <h4 class="text-muted">هیچ افزونه‌ای یافت نشد</h4>
        <p class="text-muted">فایل‌های افزونه را در پوشه plugins قرار دهید</p>
    </div>
<?php else: ?>
    <!-- آمار افزونه‌ها -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo count($allPlugins); ?></h3>
                    <p>کل افزونه‌ها</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">
                        <?php echo count(array_filter($allPlugins, function($p) { return $p['status'] === 'active'; })); ?>
                    </h3>
                    <p>فعال</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning">
                        <?php echo count(array_filter($allPlugins, function($p) { return $p['status'] === 'inactive'; })); ?>
                    </h3>
                    <p>غیرفعال</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-secondary">
                        <?php echo count(array_filter($allPlugins, function($p) { return $p['status'] === 'not_installed'; })); ?>
                    </h3>
                    <p>نصب نشده</p>
                </div>
            </div>
        </div>
    </div>

    <!-- لیست افزونه‌ها -->
    <div class="row">
        <?php foreach ($allPlugins as $plugin): ?>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($plugin['display_name']); ?></h5>
                        <small class="text-muted">پوشه: <?php echo htmlspecialchars($plugin['name']); ?></small>
                    </div>
                    <span class="badge bg-<?php 
                        echo $plugin['status'] === 'active' ? 'success' : 
                             ($plugin['status'] === 'inactive' ? 'warning' : 'secondary'); 
                    ?>">
                        <?php 
                        echo $plugin['status'] === 'active' ? 'فعال' : 
                             ($plugin['status'] === 'inactive' ? 'غیرفعال' : 'نصب نشده'); 
                        ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <p><strong>نسخه:</strong> <?php echo htmlspecialchars($plugin['version'] ?? '1.0.0'); ?></p>
                    
                    <?php if (!empty($plugin['validation_errors'])): ?>
                    <div class="alert alert-warning p-2">
                        <small>مشکلات: <?php echo implode(', ', $plugin['validation_errors']); ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($plugin['api_endpoints'])): ?>
                    <p><strong>API:</strong> <?php echo count($plugin['api_endpoints']); ?> endpoint</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($plugin['views'])): ?>
                    <p><strong>Views:</strong> <?php echo count($plugin['views']); ?> ویو</p>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            <?php if ($plugin['status'] === 'not_installed'): ?>
                                <?php if ($plugin['is_valid']): ?>
                                <button class="btn btn-primary btn-sm" onclick="actionPlugin('install', '<?php echo $plugin['name']; ?>')">
                                    نصب
                                </button>
                                <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>غیرقابل نصب</button>
                                <?php endif; ?>
                            <?php elseif ($plugin['status'] === 'inactive'): ?>
                                <button class="btn btn-success btn-sm" onclick="actionPlugin('activate', '<?php echo $plugin['name']; ?>')">
                                    فعال‌سازی
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="actionPlugin('uninstall', '<?php echo $plugin['name']; ?>')">
                                    حذف
                                </button>
                            <?php else: ?>
                                <button class="btn btn-warning btn-sm" onclick="actionPlugin('deactivate', '<?php echo $plugin['name']; ?>')">
                                    غیرفعال‌سازی
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($plugin['has_docs']): ?>
                        <a href="<?php echo BASE_URL; ?>/docs.php?plugin=<?php echo urlencode($plugin['name']); ?>" 
                           class="btn btn-outline-info btn-sm" target="_blank">
                            مستندات
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form id="actionForm" method="post" style="display:none;">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="plugin_name" id="formPluginName">
</form>

<script>
function actionPlugin(action, pluginName) {
    const messages = {
        'install': 'آیا افزونه نصب شود؟',
        'activate': 'آیا افزونه فعال شود؟',
        'deactivate': 'آیا افزونه غیرفعال شود؟',
        'uninstall': 'آیا افزونه حذف شود؟ (قابل برگشت نیست)'
    };
    
    if (confirm(messages[action])) {
        document.getElementById('formAction').value = action;
        document.getElementById('formPluginName').value = pluginName;
        document.getElementById('actionForm').submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
