<?php
/**
 * راهنمای توسعه افزونه‌ها
 */

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

$pageTitle = 'راهنمای توسعه افزونه‌ها';

// پردازش درخواست دانلود
if (isset($_GET['download']) && $_GET['download'] === 'guide') {
    $guideContent = generateDownloadableGuide();
    
    header('Content-Type: text/markdown; charset=utf-8');
    header('Content-Disposition: attachment; filename="plugin-development-guide.md"');
    header('Content-Length: ' . strlen($guideContent));
    
    echo $guideContent;
    exit;
}

function generateDownloadableGuide() {
    $markdownFile = __DIR__ . '/../docs/plugin-development-guide.md';
    if (file_exists($markdownFile)) {
        return file_get_contents($markdownFile);
    }
    return "# راهنمای توسعه افزونه‌ها\n\nراهنمای کاملی برای توسعه افزونه‌ها در سیستم اپ مرکزی";
}

// خواندن محتوای فایل Markdown
$markdownFile = __DIR__ . '/../docs/plugin-development-guide.md';
$markdownContent = '';

if (file_exists($markdownFile)) {
    $markdownContent = file_get_contents($markdownFile);
} else {
    $markdownContent = "# راهنمای توسعه افزونه‌ها موجود نیست\n\nلطفاً فایل plugin-development-guide.md را در پوشه docs قرار دهید.";
}

// تولید فهرست مطالب
function extractToc($text) {
    preg_match_all('/^(#{1,3})\s+(.+)$/m', $text, $matches);
    $toc = [];
    
    foreach ($matches[0] as $index => $match) {
        $level = strlen($matches[1][$index]);
        $title = trim($matches[2][$index]);
        $id = 'heading-' . ($index + 1);
        
        $toc[] = [
            'level' => $level,
            'title' => $title,
            'id' => $id
        ];
    }
    
    return $toc;
}

// تابع تبدیل ساده Markdown به HTML
function parseMarkdown($text) {
    // Escape HTML
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Headers with IDs
    $headerCount = 0;
    $text = preg_replace_callback('/^(#{1,3})\s+(.+)$/m', function($matches) use (&$headerCount) {
        $headerCount++;
        $level = strlen($matches[1]);
        $title = trim($matches[2]);
        $id = 'heading-' . $headerCount;
        
        if ($level == 1) {
            return '<h1 id="' . $id . '" class="display-4 border-bottom pb-3 mb-4">' . $title . '</h1>';
        } elseif ($level == 2) {
            return '<h2 id="' . $id . '" class="h3 mt-5 mb-3 text-primary">' . $title . '</h2>';
        } else {
            return '<h3 id="' . $id . '" class="h5 mt-4 mb-2 text-secondary">' . $title . '</h3>';
        }
    }, $text);
    
    // Code blocks
    $text = preg_replace_callback('/```(\w+)?\n(.*?)\n```/s', function($matches) {
        $code = $matches[2];
        return '<div class="position-relative mb-3">
            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2" onclick="copyCode(this)">کپی</button>
            <pre class="bg-light border rounded p-3"><code>' . $code . '</code></pre>
        </div>';
    }, $text);
    
    // Inline code
    $text = preg_replace('/`([^`]+)`/', '<code class="bg-light px-2 py-1 rounded text-danger">$1</code>', $text);
    
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    
    // Italic
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    
    // Lists
    $text = preg_replace('/^- (.+)$/m', '<li class="mb-1">$1</li>', $text);
    
    // Wrap consecutive list items in ul
    $text = preg_replace('/(<li.*?<\/li>\s*)+/s', '<ul class="mb-3">$0</ul>', $text);
    
    // Clean up multiple ul tags
    $text = preg_replace('/<\/ul>\s*<ul[^>]*>/', '', $text);
    
    // Links
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="text-decoration-none">$1</a>', $text);
    
    // Blockquotes (for warnings and notes)
    $text = preg_replace('/^> (.+)$/m', '<blockquote class="alert alert-info border-start border-info border-3 ps-3">$1</blockquote>', $text);
    
    // Special boxes
    $text = preg_replace('/⚠️ \*\*(.+?)\*\*/', '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i><strong>$1</strong>', $text);
    $text = preg_replace('/✅ (.+)/', '<div class="alert alert-success"><i class="fas fa-check me-2"></i>$1</div>', $text);
    
    // Convert line breaks
    $text = preg_replace('/\n\n/', '</p><p class="mb-3">', $text);
    $text = '<p class="mb-3">' . $text . '</p>';
    
    // Clean up empty paragraphs
    $text = preg_replace('/<p[^>]*><\/p>/', '', $text);
    $text = preg_replace('/<p[^>]*>\s*<\/p>/', '', $text);
    
    return $text;
}

$toc = extractToc($markdownContent);
$parsedContent = parseMarkdown($markdownContent);

ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">
                                <i class="fas fa-book text-primary me-2"></i>
                                راهنمای توسعه افزونه‌ها
                            </h1>
                            <p class="text-muted mb-0">راهنمای کامل برای ایجاد افزونه‌های حرفه‌ای</p>
                        </div>
                        <div>
                            <a href="?download=guide" class="btn btn-outline-primary">
                                <i class="fas fa-download me-2"></i>دانلود راهنما
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Table of Contents -->
        <div class="col-lg-3 col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        فهرست مطالب
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($toc as $item): ?>
                            <a href="#<?php echo $item['id']; ?>" 
                               class="list-group-item list-group-item-action d-flex align-items-center toc-link <?php echo $item['level'] > 2 ? 'ps-4' : ''; ?>"
                               data-target="<?php echo $item['id']; ?>">
                                <?php if ($item['level'] == 1): ?>
                                    <i class="fas fa-bookmark text-primary me-2"></i>
                                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                <?php elseif ($item['level'] == 2): ?>
                                    <i class="fas fa-chevron-left text-secondary me-2"></i>
                                    <?php echo htmlspecialchars($item['title']); ?>
                                <?php else: ?>
                                    <i class="fas fa-circle text-muted me-2" style="font-size: 0.5rem;"></i>
                                    <small><?php echo htmlspecialchars($item['title']); ?></small>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="col-lg-9 col-md-8">
            <div class="card">
                <div class="card-body p-4">
                    <div class="markdown-content">
                        <?php echo $parsedContent; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.markdown-content {
    line-height: 1.6;
    color: #333;
}

.markdown-content h1 {
    color: #2c3e50;
}

.markdown-content h2 {
    border-right: 4px solid #007bff;
    padding-right: 1rem;
}

.markdown-content h3 {
    border-right: 2px solid #6c757d;
    padding-right: 0.5rem;
}

.markdown-content pre {
    direction: ltr;
    text-align: left;
    font-family: 'Courier New', Monaco, monospace;
    font-size: 0.9rem;
    line-height: 1.4;
    max-height: 400px;
    overflow-y: auto;
}

.markdown-content code {
    font-family: 'Courier New', Monaco, monospace;
    font-size: 0.9em;
}

.markdown-content ul {
    padding-right: 2rem;
}

.markdown-content li {
    margin-bottom: 0.5rem;
}

.markdown-content .alert {
    border-radius: 0.5rem;
}

.card {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.toc-link {
    border: none !important;
    padding: 0.75rem 1rem;
    transition: background-color 0.2s ease;
}

.toc-link:hover {
    background-color: #f8f9fa !important;
}

.toc-link.active {
    background-color: #e3f2fd !important;
    border-right: 3px solid #007bff !important;
}

.sticky-top {
    z-index: 1020;
}

@media (max-width: 768px) {
    .container-fluid {
        padding: 0.5rem;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .markdown-content h2 {
        font-size: 1.25rem;
    }
    
    .markdown-content h3 {
        font-size: 1.1rem;
    }
}
</style>

<script>
// Copy code functionality
function copyCode(button) {
    const codeBlock = button.parentElement.querySelector('code');
    const text = codeBlock.textContent;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            const originalText = button.textContent;
            button.textContent = 'کپی شد!';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        });
    } else {
        // Fallback
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            button.textContent = 'کپی شد!';
            setTimeout(() => {
                button.textContent = 'کپی';
            }, 2000);
        } catch (err) {
            console.error('کپی نشد:', err);
        }
        
        document.body.removeChild(textArea);
    }
}

// Table of Contents functionality
document.addEventListener('DOMContentLoaded', function() {
    const tocLinks = document.querySelectorAll('.toc-link');
    
    // Smooth scrolling for TOC links
    tocLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            
            if (target) {
                // Remove active class from all links
                tocLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Smooth scroll to target
                const targetPosition = target.offsetTop - 80; // Account for sticky header
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Update active TOC item on scroll
    function updateActiveToc() {
        const headings = document.querySelectorAll('h1[id], h2[id], h3[id]');
        const scrollPos = window.pageYOffset + 100;
        
        let activeHeading = null;
        
        headings.forEach(heading => {
            if (heading.offsetTop <= scrollPos) {
                activeHeading = heading;
            }
        });
        
        // Update active state
        tocLinks.forEach(link => link.classList.remove('active'));
        
        if (activeHeading) {
            const activeLink = document.querySelector(`a[href="#${activeHeading.id}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }
        }
    }
    
    // Throttled scroll event
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(updateActiveToc, 100);
    });
    
    // Set initial active state
    updateActiveToc();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>