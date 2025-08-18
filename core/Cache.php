<?php
/**
 * کلاس مدیریت کش
 */
class Cache {
    private $cacheDir;
    private $defaultTtl = 3600; // 1 hour
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        $this->ensureDirectoryExists();
    }
    
    /**
     * ذخیره در کش
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTtl;
        $filename = $this->getCacheFilename($key);
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($filename, serialize($cacheData), LOCK_EX) !== false;
    }
    
    /**
     * دریافت از کش
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }
        
        $cacheData = unserialize($content);
        if (!$cacheData || !is_array($cacheData)) {
            $this->delete($key);
            return null;
        }
        
        // بررسی انقضا
        if ($cacheData['expires'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $cacheData['data'];
    }
    
    /**
     * حذف از کش
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }
    
    /**
     * بررسی وجود در کش
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * پاک کردن کل کش
     */
    public function clear($type = 'all') {
        $cleared = 0;
        
        if ($type === 'all') {
            $dirs = ['views', 'api'];
        } else {
            $dirs = [$type];
        }
        
        foreach ($dirs as $dir) {
            $path = $this->cacheDir . $dir . '/';
            if (is_dir($path)) {
                $files = glob($path . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $cleared++;
                    }
                }
            }
        }
        
        return $cleared;
    }
    
    /**
     * پاک کردن کش منقضی شده
     */
    public function cleanup() {
        $cleaned = 0;
        $dirs = ['views', 'api'];
        
        foreach ($dirs as $dir) {
            $path = $this->cacheDir . $dir . '/';
            if (is_dir($path)) {
                $files = glob($path . '*.cache');
                foreach ($files as $file) {
                    $content = file_get_contents($file);
                    $cacheData = unserialize($content);
                    
                    if (!$cacheData || $cacheData['expires'] < time()) {
                        unlink($file);
                        $cleaned++;
                    }
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * ذخیره ویو در کش
     */
    public function setView($plugin, $view, $html, $ttl = null) {
        $key = "view_{$plugin}_{$view}";
        $filename = $this->cacheDir . 'views/' . md5($key) . '.cache';
        
        $cacheData = [
            'html' => $html,
            'expires' => time() + ($ttl ?? $this->defaultTtl),
            'created' => time(),
            'plugin' => $plugin,
            'view' => $view
        ];
        
        return file_put_contents($filename, serialize($cacheData), LOCK_EX) !== false;
    }
    
    /**
     * دریافت ویو از کش
     */
    public function getView($plugin, $view) {
        $key = "view_{$plugin}_{$view}";
        $filename = $this->cacheDir . 'views/' . md5($key) . '.cache';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        $cacheData = unserialize($content);
        
        if (!$cacheData || $cacheData['expires'] < time()) {
            if (file_exists($filename)) {
                unlink($filename);
            }
            return null;
        }
        
        return $cacheData['html'];
    }
    
    /**
     * ذخیره پاسخ API در کش
     */
    public function setApiResponse($endpoint, $params, $response, $ttl = null) {
        $key = "api_" . md5($endpoint . serialize($params));
        $filename = $this->cacheDir . 'api/' . $key . '.cache';
        
        $cacheData = [
            'response' => $response,
            'expires' => time() + ($ttl ?? 300), // 5 minutes default for API
            'created' => time(),
            'endpoint' => $endpoint,
            'params' => $params
        ];
        
        return file_put_contents($filename, serialize($cacheData), LOCK_EX) !== false;
    }
    
    /**
     * دریافت پاسخ API از کش
     */
    public function getApiResponse($endpoint, $params) {
        $key = "api_" . md5($endpoint . serialize($params));
        $filename = $this->cacheDir . 'api/' . $key . '.cache';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        $cacheData = unserialize($content);
        
        if (!$cacheData || $cacheData['expires'] < time()) {
            if (file_exists($filename)) {
                unlink($filename);
            }
            return null;
        }
        
        return $cacheData['response'];
    }
    
    /**
     * دریافت آمار کش
     */
    public function getStats() {
        $stats = [
            'views' => ['count' => 0, 'size' => 0],
            'api' => ['count' => 0, 'size' => 0],
            'total_size' => 0
        ];
        
        $dirs = ['views', 'api'];
        foreach ($dirs as $dir) {
            $path = $this->cacheDir . $dir . '/';
            if (is_dir($path)) {
                $files = glob($path . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $stats[$dir]['count']++;
                        $size = filesize($file);
                        $stats[$dir]['size'] += $size;
                        $stats['total_size'] += $size;
                    }
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * دریافت نام فایل کش
     */
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . 'api/' . $hash . '.cache';
    }
    
    /**
     * اطمینان از وجود دایرکتوری کش
     */
    private function ensureDirectoryExists() {
        $dirs = ['views', 'api'];
        foreach ($dirs as $dir) {
            $path = $this->cacheDir . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
}
?>