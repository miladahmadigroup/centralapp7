<?php
/**
 * کلاس مدیریت افزونه‌ها - اصلاح شده
 */
class PluginManager {
    private $db;
    private $pluginsDir;
    private $activePlugins = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->pluginsDir = __DIR__ . '/../plugins/';
        $this->loadActivePlugins();
    }
    
    private function loadActivePlugins() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM plugins WHERE status = 'active'");
            $stmt->execute();
            $plugins = $stmt->fetchAll();
            
            foreach ($plugins as $plugin) {
                $this->activePlugins[$plugin['name']] = $plugin;
                $this->loadPlugin($plugin['name']);
            }
        } catch (Exception $e) {
            // جدول plugins موجود نیست - نادیده بگیر
        }
    }
    
    public function loadPlugin($pluginName) {
        $pluginPath = $this->pluginsDir . $pluginName . '/';
        $mainFile = $pluginPath . 'main.php';
        
        if (!file_exists($mainFile)) {
            return false;
        }
        
        try {
            require_once $mainFile;
            return true;
        } catch (Exception $e) {
            logError("Failed to load plugin {$pluginName}: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllPlugins() {
        $plugins = [];
        
        if (!is_dir($this->pluginsDir)) {
            return $plugins;
        }
        
        $dirs = glob($this->pluginsDir . '*', GLOB_ONLYDIR);
        
        foreach ($dirs as $dir) {
            $pluginName = basename($dir);
            $configFile = $dir . '/plugin.json';
            
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                
                if ($config) {
                    // استفاده از نام پوشه به عنوان شناسه اصلی
                    $config['folder_name'] = $pluginName;
                    $config['display_name'] = $config['name'] ?? $pluginName;
                    $config['name'] = $pluginName; // override کردن name با نام پوشه
                    
                    $config['path'] = $dir;
                    $config['status'] = $this->getPluginStatus($pluginName);
                    $config['installed'] = $this->isPluginInstalled($pluginName);
                    $config['has_docs'] = file_exists($dir . '/docs.json');
                    
                    $validationErrors = $this->validatePluginStructure($dir);
                    $config['validation_errors'] = $validationErrors;
                    $config['is_valid'] = empty($validationErrors);
                    
                    $plugins[] = $config;
                }
            }
        }
        
        return $plugins;
    }
    
    public function validatePluginStructure($pluginPath) {
        $requiredFiles = ['plugin.json', 'main.php', 'docs.json'];
        $errors = [];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($pluginPath . '/' . $file)) {
                $errors[] = "فایل $file یافت نشد";
            }
        }
        
        if (file_exists($pluginPath . '/plugin.json')) {
            $pluginConfig = json_decode(file_get_contents($pluginPath . '/plugin.json'), true);
            if (!$pluginConfig) {
                $errors[] = 'فایل plugin.json معتبر نیست';
            }
        }
        
        return $errors;
    }
    
    public function installPlugin($pluginName) {
        $pluginPath = $this->pluginsDir . $pluginName . '/';
        $configFile = $pluginPath . 'plugin.json';
        $installFile = $pluginPath . 'install.sql';
        
        if (!file_exists($configFile)) {
            throw new Exception('فایل تنظیمات افزونه یافت نشد');
        }
        
        $validationErrors = $this->validatePluginStructure($pluginPath);
        if (!empty($validationErrors)) {
            throw new Exception('ساختار افزونه معتبر نیست: ' . implode(', ', $validationErrors));
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        if (!$config) {
            throw new Exception('فایل تنظیمات افزونه معتبر نیست');
        }
        
        if ($this->isPluginInstalled($pluginName)) {
            throw new Exception('افزونه قبلاً نصب شده است');
        }
        
        try {
            if (file_exists($installFile)) {
                $sql = file_get_contents($installFile);
                if (!empty(trim($sql))) {
                    $this->db->exec($sql);
                }
            }
            
            $stmt = $this->db->prepare("INSERT INTO plugins (name, version, status) VALUES (?, ?, 'inactive')");
            $result = $stmt->execute([$pluginName, $config['version'] ?? '1.0.0']);
            
            if (!$result) {
                throw new Exception('خطا در ثبت افزونه در دیتابیس');
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('خطا در نصب افزونه: ' . $e->getMessage());
        }
    }
    
    public function activatePlugin($pluginName) {
        if (!$this->isPluginInstalled($pluginName)) {
            throw new Exception('افزونه نصب نشده است');
        }
        
        if ($this->isPluginActive($pluginName)) {
            throw new Exception('افزونه قبلاً فعال است');
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE plugins SET status = 'active' WHERE name = ?");
            $result = $stmt->execute([$pluginName]);
            
            if (!$result) {
                throw new Exception('خطا در بروزرسانی وضعیت افزونه');
            }
            
            $this->loadPlugin($pluginName);
            $this->activePlugins[$pluginName] = [
                'name' => $pluginName,
                'status' => 'active'
            ];
            
            return true;
        } catch (Exception $e) {
            throw new Exception('خطا در فعال‌سازی افزونه: ' . $e->getMessage());
        }
    }
    
    public function deactivatePlugin($pluginName) {
        if (!$this->isPluginActive($pluginName)) {
            return true;
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE plugins SET status = 'inactive' WHERE name = ?");
            $result = $stmt->execute([$pluginName]);
            
            if (!$result) {
                throw new Exception('خطا در بروزرسانی وضعیت افزونه');
            }
            
            unset($this->activePlugins[$pluginName]);
            return true;
        } catch (Exception $e) {
            throw new Exception('خطا در غیرفعال‌سازی افزونه: ' . $e->getMessage());
        }
    }
    
    public function uninstallPlugin($pluginName) {
        if ($this->isPluginActive($pluginName)) {
            $this->deactivatePlugin($pluginName);
        }
        
        try {
            if ($this->isPluginInstalled($pluginName)) {
                $stmt = $this->db->prepare("DELETE FROM plugins WHERE name = ?");
                $stmt->execute([$pluginName]);
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('خطا در حذف افزونه: ' . $e->getMessage());
        }
    }
    
    public function isPluginInstalled($pluginName) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM plugins WHERE name = ?");
            $stmt->execute([$pluginName]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function isPluginActive($pluginName) {
        return isset($this->activePlugins[$pluginName]);
    }
    
    public function getPluginStatus($pluginName) {
        try {
            $stmt = $this->db->prepare("SELECT status FROM plugins WHERE name = ?");
            $stmt->execute([$pluginName]);
            $result = $stmt->fetch();
            return $result ? $result['status'] : 'not_installed';
        } catch (Exception $e) {
            return 'not_installed';
        }
    }
    
    public function callPluginApi($pluginName, $action, $data = []) {
        if (!$this->isPluginActive($pluginName)) {
            throw new Exception('افزونه فعال نیست');
        }
        
        $pluginPath = $this->pluginsDir . $pluginName . '/';
        $apiFile = $pluginPath . 'api.php';
        
        if (!file_exists($apiFile)) {
            throw new Exception('API افزونه یافت نشد');
        }
        
        $GLOBALS['plugin_action'] = $action;
        $GLOBALS['plugin_data'] = $data;
        
        ob_start();
        include $apiFile;
        $output = ob_get_clean();
        
        return $output;
    }
    
    public function getPluginView($pluginName, $viewName, $params = []) {
        if (!$this->isPluginActive($pluginName)) {
            throw new Exception('افزونه فعال نیست');
        }
        
        $pluginPath = $this->pluginsDir . $pluginName . '/';
        $viewFile = $pluginPath . 'views/' . $viewName . '.php';
        
        if (!file_exists($viewFile)) {
            throw new Exception('ویو یافت نشد');
        }
        
        extract($params);
        
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
}