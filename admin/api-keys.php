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

$pageTitle = 'مدیریت کلیدهای API';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            setFlashMessage('error', 'نام کلید API الزامی است');
        } else {
            try {
                $apiKey = generateApiKey();
                $stmt = $db->prepare("INSERT INTO api_keys (name, description, api_key) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $description, $apiKey])) {
                    setFlashMessage('success', 'کلید API جدید ایجاد شد');
                }
            } catch (Exception $e) {
                setFlashMessage('error', 'خطا در ایجاد کلید API');
            }
        }
        redirect(BASE_URL . '/admin/api-keys');
    }
    
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
        
        try {
            $stmt = $db->prepare("UPDATE api_keys SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            setFlashMessage('success', 'وضعیت تغییر کرد');
        } catch (Exception $e) {
            setFlashMessage('error', 'خطا در تغییر وضعیت');
        }
        redirect(BASE_URL . '/admin/api-keys');
    }
    
    if ($action === 'regenerate') {
        $id = (int)($_POST['id'] ?? 0);
        
        try {
            $newApiKey = generateApiKey();
            $stmt = $db->prepare("UPDATE api_keys SET api_key = ?, usage_count = 0, last_used = NULL WHERE id = ?");
            $stmt->execute([$newApiKey, $id]);
            setFlashMessage('success', 'کلید جدید تولید شد');
        } catch (Exception $e) {
            setFlashMessage('error', 'خطا در تولید کلید');
        }
        redirect(BASE_URL . '/admin/api-keys');
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        try {
            $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'کلید حذف شد');
        } catch (Exception $e) {
            setFlashMessage('error', 'خطا در حذف کلید');
        }
        redirect(BASE_URL . '/admin/api-keys');
    }
}

try {
    $stmt = $db->prepare("SELECT * FROM api_keys ORDER BY created_at DESC");
    $stmt->execute();
    $apiKeys = $stmt->fetchAll();
} catch (Exception $e) {
    $apiKeys = [];
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">کلیدهای API</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        افزودن کلید
    </button>
</div>

<?php if (empty($apiKeys)): ?>
    <div class="text-center py-5">
        <h5 class="text-muted">هیچ کلید API وجود ندارد</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            ایجاد اولین کلید
        </button>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>کلید</th>
                    <th>وضعیت</th>
                    <th>استفاده</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apiKeys as $key): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($key['name']); ?></strong>
                        <?php if (!empty($key['description'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($key['description']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code><?php echo substr($key['api_key'], 0, 8); ?>...</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyToClipboard('<?php echo $key['api_key']; ?>')">
                            کپی
                        </button>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $key['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo $key['status'] === 'active' ? 'فعال' : 'غیرفعال'; ?>
                        </span>
                    </td>
                    <td>
                        <?php echo number_format($key['usage_count']); ?>
                        <?php if ($key['last_used']): ?>
                            <br><small class="text-muted"><?php echo date('Y/m/d', strtotime($key['last_used'])); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $key['id']; ?>, '<?php echo $key['status']; ?>')">
                                <?php echo $key['status'] === 'active' ? 'غیرفعال' : 'فعال'; ?>
                            </button>
                            <button class="btn btn-outline-info" onclick="regenerate(<?php echo $key['id']; ?>)">
                                تولید مجدد
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteKey(<?php echo $key['id']; ?>)">
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
                    <h5 class="modal-title">ایجاد کلید API</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">نام کلید</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">توضیحات</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
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

function regenerate(id) {
    if (confirm('کلید جدید تولید شود؟ کلید قبلی غیرفعال می‌شود.')) {
        document.getElementById('formAction').value = 'regenerate';
        document.getElementById('formId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function deleteKey(id) {
    if (confirm('کلید حذف شود؟')) {
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