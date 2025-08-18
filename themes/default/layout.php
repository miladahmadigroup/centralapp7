<?php include 'header.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="لوگو" height="30" onerror="this.style.display='none'">
            <?php echo getSetting('site_title', 'اپ مرکزی'); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>">صفحه اصلی</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/admin">داشبورد</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isAdminLoggedIn()): ?>
                    <?php $admin = getCurrentAdmin(); ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($admin['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/settings">تنظیمات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/logout">خروج</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/login">ورود</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-4">
    <?php echo isset($content) ? $content : ''; ?>
</main>

<?php include 'footer.php'; ?>