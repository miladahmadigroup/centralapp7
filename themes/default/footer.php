<!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo getSetting('site_title', 'اپ مرکزی'); ?></h5>
                    <p class="text-muted">سیستم مدیریت متمرکز اپلیکیشن‌های کلاینت</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        طراحی شده با ❤️ برای مدیریت آسان
                    </p>
                    <small class="text-muted">
                        نسخه 1.0.0 | <?php echo date('Y'); ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript اصلی -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/widgets.js"></script>
    
    <?php if (isset($additionalScripts)): ?>
        <?php echo $additionalScripts; ?>
    <?php endif; ?>

    <script>
        // تنظیم BASE_URL برای JavaScript
        window.BASE_URL = '<?php echo BASE_URL; ?>';
        
        // نمایش پیام‌های flash
        <?php $flashMessages = getFlashMessages(); ?>
        <?php if (!empty($flashMessages)): ?>
            <?php foreach ($flashMessages as $message): ?>
                showAlert('<?php echo $message['type']; ?>', '<?php echo addslashes($message['message']); ?>');
            <?php endforeach; ?>
        <?php endif; ?>
    </script>
</body>
</html>