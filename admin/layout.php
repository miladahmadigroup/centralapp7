<?php
/**
 * Layout مدیریت ادمین - اصلاح شده
 */

// بارگذاری menu items از افزونه‌های فعال
$adminMenuItems = [];

// دریافت منوهای اضافه شده توسط افزونه‌ها
global $pluginManager;
if (!$pluginManager) {
    require_once __DIR__ . '/../core/PluginManager.php';
    $pluginManager = new PluginManager();
}

// اجرای hook برای دریافت منوهای افزونه‌ها
if (function_exists('apply_filters')) {
    $adminMenuItems = apply_filters('admin_menu_items', $adminMenuItems);
}

// شامل کردن header اصلی
include __DIR__ . '/../themes/default/header.php';
?>

<style>
.admin-layout {
    min-height: 100vh;
    display: flex;
}

.admin-sidebar {
    background-color: #1b1b1b !important;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    width: 250px;
    position: fixed;
    height: 100vh;
    right: 0;
    top: 0;
    z-index: 10000;
    overflow-y: auto;
    padding-top: 20px;
}

.admin-content {
    margin-right: 250px;
    flex: 1;
    padding: 20px;
    min-height: 100vh;
    background-color: #f8f9fa;
    width: calc(100% - 250px);
}

.sidebar-brand {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 1rem;
    margin-top: 3rem;
}

.sidebar-brand h4 {
    color: #fff;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.sidebar-brand small {
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem;
}

.sidebar-nav {
    padding: 0;
    list-style: none;
}

.nav-item {
    margin-bottom: 2px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: rgba(151, 151, 151, 0.9);
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-left-color: rgba(255,255,255,0.3);
    color: #fff;
}

.nav-link.active {
    background-color: rgba(0,123,255,0.2);
    color: #fff;
    border-left-color: #007bff;
}

.nav-link i {
    width: 20px;
    margin-left: 10px;
    text-align: center;
    font-size: 0.9rem;
}

.admin-user-info {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 1rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    color: #fff;
    font-size: 2.5rem;
}

.user-details {
    flex: 1;
}

.user-name {
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.user-role {
    color: rgba(255,255,255,0.7);
    font-size: 0.8rem;
}

.breadcrumb-custom {
    background: transparent;
    padding: 0;
    margin-bottom: 1.5rem;
}

.breadcrumb-custom .breadcrumb-item {
    color: #6c757d;
}

.breadcrumb-custom .breadcrumb-item.active {
    color: #495057;
    font-weight: 500;
}

.plugin-menu-section {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.plugin-menu-title {
    color: rgba(255,255,255,0.6);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.5rem 1.5rem;
    margin-bottom: 0.5rem;
}

/* Mobile */
@media (max-width: 768px) {
    .admin-sidebar {
        width: 280px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .admin-content {
        margin-right: 0;
        width: 100%;
    }
    
    .mobile-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10001;
        background: #007bff;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .mobile-toggle:hover {
        background: #0056b3;
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: none;
    }
    
    .sidebar-overlay.show {
        display: block;
    }
}
</style>

<div class="admin-layout">
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="admin-sidebar" id="adminSidebar">
        <!-- User Info -->
        <div class="admin-user-info">
            <?php $admin = getCurrentAdmin(); ?>
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($admin['username']); ?></div>
                <div class="user-role">مدیر سیستم</div>
            </div>
        </div>
        
        <div class="sidebar-brand">
            <h4>
                <i class="fas fa-tachometer-alt me-2"></i>
                داشبورد مدیریت
            </h4>
            <small><?php echo getSetting('site_title', 'اپ مرکزی'); ?></small>
        </div>
        
        <!-- Navigation -->
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <!-- داشبورد اصلی -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == BASE_URL . '/admin' || $_SERVER['REQUEST_URI'] == BASE_URL . '/admin/') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin">
                        <i class="fas fa-tachometer-alt"></i>
                        داشبورد
                    </a>
                </li>
                
                <!-- نمایش سایت -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        نمایش سایت
                    </a>
                </li>
                
                <!-- اپ‌های کلاینت -->
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/client-apps') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/client-apps">
                        <i class="fas fa-mobile-alt"></i>
                        اپ‌های کلاینت
                    </a>
                </li>
                
                <!-- کلیدهای API -->
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/api-keys') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/api-keys">
                        <i class="fas fa-key"></i>
                        کلیدهای API
                    </a>
                </li>
                
                <!-- افزونه‌ها -->
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/plugins') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/plugins">
                        <i class="fas fa-puzzle-piece"></i>
                        افزونه‌ها
                    </a>
                </li>
                
                <!-- منوهای افزونه‌ها -->
                <?php if (!empty($adminMenuItems)): ?>
                <li class="plugin-menu-section">
                    <div class="plugin-menu-title">افزونه‌ها</div>
                    <?php foreach ($adminMenuItems as $menuItem): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], $menuItem['url']) !== false ? 'active' : ''; ?>" 
                           href="<?php echo htmlspecialchars($menuItem['url']); ?>">
                            <i class="<?php echo htmlspecialchars($menuItem['icon'] ?? 'fas fa-circle'); ?>"></i>
                            <?php echo htmlspecialchars($menuItem['title']); ?>
                            <?php if (isset($menuItem['badge'])): ?>
                            <span class="badge bg-primary ms-auto"><?php echo htmlspecialchars($menuItem['badge']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </li>
                <?php endif; ?>
                
                <!-- حساب کاربری -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/logout">
                        <i class="fas fa-sign-out-alt"></i>
                        خروج از سیستم
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- Mobile Toggle -->
    <button class="mobile-toggle d-md-none" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Main Content -->
    <div class="admin-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-custom">
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>/admin">
                        <i class="fas fa-home"></i> داشبورد
                    </a>
                </li>
                <?php 
                $currentPath = str_replace(BASE_URL . '/admin/', '', $_SERVER['REQUEST_URI']);
                if ($currentPath && $currentPath !== '' && $currentPath !== '/admin'):
                    $pathParts = explode('/', trim($currentPath, '/'));
                    foreach ($pathParts as $part):
                        if (!empty($part)):
                ?>
                <li class="breadcrumb-item active">
                    <?php 
                    echo match($part) {
                        'plugins' => 'افزونه‌ها',
                        'client-apps' => 'اپ‌های کلاینت',
                        'api-keys' => 'کلیدهای API',
                        default => ucfirst($part)
                    };
                    ?>
                </li>
                <?php 
                        endif;
                    endforeach;
                endif; 
                ?>
            </ol>
        </nav>
        
        <!-- Page Content -->
        <div class="page-content">
            <?php echo isset($content) ? $content : ''; ?>
        </div>
    </div>
</div>

<script>
// Toggle sidebar در موبایل
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Close sidebar
function closeSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
}

// بستن sidebar در موبایل وقتی روی لینک کلیک شد
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            closeSidebar();
        }
    });
});

// بستن sidebar وقتی ESC زده شد
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSidebar();
    }
});

// مدیریت resize صفحه
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) {
        closeSidebar();
    }
});
</script>

<?php include __DIR__ . '/../themes/default/footer.php'; ?>