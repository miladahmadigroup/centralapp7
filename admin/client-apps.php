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

global $db;
$auth = new Auth();
$auth->requireAdminLogin();

$pageTitle = 'مدیریت اپ‌های کلاینت';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        
        if (empty($name) || empty($domain)) {
            setFlashMessage('error', 'نام و دامنه الزامی است');
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO client_apps (name, domain) VALUES (?, ?)");
                if ($stmt->execute([$name, $domain])) {
                    setFlashMessage('success', 'اپ کلاینت اضافه شد');
                }
            } catch (Exception $e) {
                setFlashMessage('error', 'خطا در افزودن اپ');
            }
        }
        redirect(BASE_URL . '/admin/client-apps');
    }
    
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
        
        try {
            $stmt = $db->prepare("UPDATE client_apps SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            setFlashMessage('success', 'وضعیت تغییر کرد');
        } catch (Exception $e) {
            setFlashMessage('error', 'خطا در تغییر وضعیت');
        }
        redirect(BASE_URL . '/admin/client-apps');
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        try {
            $stmt = $db->prepare("DELETE FROM client_apps WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'اپ حذف شد');
        } catch (Exception $e) {
            setFlashMessage('error', 'خطا در حذف اپ');
        }
        redirect(BASE_URL . '/admin/client-apps');
    }
}

try {
    $stmt = $db->prepare("SELECT * FROM client_apps ORDER BY created_at DESC");
    $stmt->execute();
    $clientApps = $stmt->fetchAll();
} catch (Exception $e) {
    $clientApps = [];
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">اپ‌های کلاینت</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        افزودن اپ
    </button>
</div>

<div class="alert alert-info">
    برای دسترسی API، از بخش <a href="<?php echo BASE_URL; ?>/admin/api-keys">کلیدهای API</a> استفاده کنید.
</div>

<?php if (empty($clientApps)): ?>
    <div class="text-center py-5">
        <h5 class="text-muted">هیچ اپ کلاینتی وجود ندارد</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            ایجاد اولین اپ
        </button>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>دامنه</th>
                    <th>وضعیت</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientApps as $app): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($app['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($app['domain']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $app['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo $app['status'] === 'active' ? 'فعال' : 'غیرفعال'; ?>
                        </span>
                    </td>
                    <td><?php echo date('Y/m/d', strtotime($app['created_at'])); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $app['id']; ?>, '<?php echo $app['status']; ?>')">
                                <?php echo $app['status'] === 'active' ? 'غیرفعال' : 'فعال'; ?>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteApp(<?php echo $app['id']; ?>)">
                                حذف
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Modal -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن اپ کلاینت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">نام اپ</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">دامنه</label>
                        <input type="url" class="form-control" name="domain" placeholder="https://example.com" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ایجاد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="actionForm" method="post" style="display:none;">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="id" id="formId">
    <input type="hidden" name="status" id="formStatus">
</form>

<script>
function toggleStatus(id, status) {
    document.getElementById('formAction').value = 'toggle';
    document.getElementById('formId').value = id;
    document.getElementById('formStatus').value = status;
    document.getElementById('actionForm').submit();
}

function deleteApp(id) {
    if (confirm('اپ حذف شود؟')) {
        document.getElementById('formAction').value = 'delete';
        document.getElementById('formId').value = id;
        document.getElementById('actionForm').submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>