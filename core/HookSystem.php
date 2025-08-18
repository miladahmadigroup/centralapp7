<?php
/**
 * سیستم Hook و Filter مانند WordPress
 */
class HookSystem {
    private static $instance = null;
    private $actions = [];
    private $filters = [];
    private $currentFilter = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new HookSystem();
        }
        return self::$instance;
    }
    
    /**
     * اضافه کردن Action
     */
    public function addAction($tag, $callback, $priority = 10, $acceptedArgs = 1) {
        return $this->addHook('actions', $tag, $callback, $priority, $acceptedArgs);
    }
    
    /**
     * اضافه کردن Filter
     */
    public function addFilter($tag, $callback, $priority = 10, $acceptedArgs = 1) {
        return $this->addHook('filters', $tag, $callback, $priority, $acceptedArgs);
    }
    
    /**
     * حذف Action
     */
    public function removeAction($tag, $callback, $priority = 10) {
        return $this->removeHook('actions', $tag, $callback, $priority);
    }
    
    /**
     * حذف Filter
     */
    public function removeFilter($tag, $callback, $priority = 10) {
        return $this->removeHook('filters', $tag, $callback, $priority);
    }
    
    /**
     * اجرای Action
     */
    public function doAction($tag, ...$args) {
        if (!isset($this->actions[$tag])) {
            return;
        }
        
        $this->doActionRef($tag, $args);
    }
    
    /**
     * اعمال Filter
     */
    public function applyFilters($tag, $value, ...$args) {
        if (!isset($this->filters[$tag])) {
            return $value;
        }
        
        $allArgs = func_get_args();
        array_shift($allArgs); // حذف $tag
        
        return $this->applyFiltersRef($tag, $allArgs);
    }
    
    /**
     * بررسی وجود Action
     */
    public function hasAction($tag, $callback = false) {
        return $this->hasHook('actions', $tag, $callback);
    }
    
    /**
     * بررسی وجود Filter
     */
    public function hasFilter($tag, $callback = false) {
        return $this->hasHook('filters', $tag, $callback);
    }
    
    /**
     * دریافت لیست Hook های ثبت شده
     */
    public function getRegisteredHooks($type = 'all') {
        switch ($type) {
            case 'actions':
                return $this->actions;
            case 'filters':
                return $this->filters;
            default:
                return [
                    'actions' => $this->actions,
                    'filters' => $this->filters
                ];
        }
    }
    
    /**
     * پاک کردن همه Hook های یک tag
     */
    public function removeAllActions($tag, $priority = false) {
        return $this->removeAllHooks('actions', $tag, $priority);
    }
    
    /**
     * پاک کردن همه Filter های یک tag
     */
    public function removeAllFilters($tag, $priority = false) {
        return $this->removeAllHooks('filters', $tag, $priority);
    }
    
    /**
     * اضافه کردن Hook (پایه)
     */
    private function addHook($type, $tag, $callback, $priority, $acceptedArgs) {
        $idx = $this->buildUniqueId($callback);
        
        $this->{$type}[$tag][$priority][$idx] = [
            'function' => $callback,
            'accepted_args' => $acceptedArgs
        ];
        
        return true;
    }
    
    /**
     * حذف Hook (پایه)
     */
    private function removeHook($type, $tag, $callback, $priority) {
        $idx = $this->buildUniqueId($callback);
        
        $exists = isset($this->{$type}[$tag][$priority][$idx]);
        
        if ($exists) {
            unset($this->{$type}[$tag][$priority][$idx]);
            
            if (empty($this->{$type}[$tag][$priority])) {
                unset($this->{$type}[$tag][$priority]);
            }
            
            if (empty($this->{$type}[$tag])) {
                unset($this->{$type}[$tag]);
            }
        }
        
        return $exists;
    }
    
    /**
     * بررسی وجود Hook (پایه)
     */
    private function hasHook($type, $tag, $callback) {
        if (!isset($this->{$type}[$tag])) {
            return false;
        }
        
        if ($callback === false) {
            return true;
        }
        
        $idx = $this->buildUniqueId($callback);
        
        foreach ($this->{$type}[$tag] as $priority => $hooks) {
            if (isset($hooks[$idx])) {
                return $priority;
            }
        }
        
        return false;
    }
    
    /**
     * حذف همه Hook های یک tag
     */
    private function removeAllHooks($type, $tag, $priority) {
        if (isset($this->{$type}[$tag])) {
            if ($priority !== false && isset($this->{$type}[$tag][$priority])) {
                unset($this->{$type}[$tag][$priority]);
            } else {
                unset($this->{$type}[$tag]);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * اجرای Action با reference
     */
    private function doActionRef($tag, $args) {
        if (!isset($this->actions[$tag])) {
            return;
        }
        
        $this->currentFilter[] = $tag;
        
        $allArgs = [];
        if (is_array($args) && 1 == count($args) && isset($args[0]) && is_object($args[0])) {
            $allArgs[] =& $args[0];
        } else {
            $allArgs = $args;
        }
        
        $hooks = $this->actions[$tag];
        ksort($hooks);
        
        foreach ($hooks as $priority => $priorityHooks) {
            if (!is_null($priorityHooks)) {
                foreach ($priorityHooks as $hook) {
                    call_user_func_array($hook['function'], array_slice($allArgs, 0, (int) $hook['accepted_args']));
                }
            }
        }
        
        array_pop($this->currentFilter);
    }
    
    /**
     * اعمال Filter با reference
     */
    private function applyFiltersRef($tag, $args) {
        if (!isset($this->filters[$tag])) {
            return $args[0];
        }
        
        $this->currentFilter[] = $tag;
        
        $hooks = $this->filters[$tag];
        ksort($hooks);
        
        foreach ($hooks as $priority => $priorityHooks) {
            if (!is_null($priorityHooks)) {
                foreach ($priorityHooks as $hook) {
                    $args[0] = call_user_func_array($hook['function'], array_slice($args, 0, (int) $hook['accepted_args']));
                }
            }
        }
        
        array_pop($this->currentFilter);
        
        return $args[0];
    }
    
    /**
     * تولید شناسه یکتا برای callback
     */
    private function buildUniqueId($callback) {
        if (is_string($callback)) {
            return $callback;
        }
        
        if (is_object($callback)) {
            $callback = [$callback, ''];
        } else {
            $callback = (array) $callback;
        }
        
        if (is_object($callback[0])) {
            return spl_object_hash($callback[0]) . $callback[1];
        } else if (is_string($callback[0])) {
            return $callback[0] . '::' . $callback[1];
        }
        
        return '';
    }
    
    /**
     * دریافت Hook فعلی
     */
    public function currentFilter() {
        return end($this->currentFilter);
    }
    
    /**
     * دریافت آمار Hook ها
     */
    public function getHookStats() {
        $stats = [
            'total_actions' => 0,
            'total_filters' => 0,
            'action_tags' => count($this->actions),
            'filter_tags' => count($this->filters),
            'top_actions' => [],
            'top_filters' => []
        ];
        
        // شمارش کل action ها
        foreach ($this->actions as $tag => $priorities) {
            $count = 0;
            foreach ($priorities as $priority => $hooks) {
                $count += count($hooks);
            }
            $stats['total_actions'] += $count;
            $stats['top_actions'][$tag] = $count;
        }
        
        // شمارش کل filter ها
        foreach ($this->filters as $tag => $priorities) {
            $count = 0;
            foreach ($priorities as $priority => $hooks) {
                $count += count($hooks);
            }
            $stats['total_filters'] += $count;
            $stats['top_filters'][$tag] = $count;
        }
        
        // مرتب‌سازی بر اساس تعداد
        arsort($stats['top_actions']);
        arsort($stats['top_filters']);
        
        // نگه‌داشتن فقط 10 تای اول
        $stats['top_actions'] = array_slice($stats['top_actions'], 0, 10, true);
        $stats['top_filters'] = array_slice($stats['top_filters'], 0, 10, true);
        
        return $stats;
    }
    
    /**
     * Debug Hook ها
     */
    public function debugHooks($tag = null, $type = 'all') {
        $output = [];
        
        if ($tag) {
            // Debug یک tag خاص
            if ($type === 'all' || $type === 'actions') {
                if (isset($this->actions[$tag])) {
                    $output['actions'][$tag] = $this->actions[$tag];
                }
            }
            
            if ($type === 'all' || $type === 'filters') {
                if (isset($this->filters[$tag])) {
                    $output['filters'][$tag] = $this->filters[$tag];
                }
            }
        } else {
            // Debug همه Hook ها
            if ($type === 'all' || $type === 'actions') {
                $output['actions'] = $this->actions;
            }
            
            if ($type === 'all' || $type === 'filters') {
                $output['filters'] = $this->filters;
            }
        }
        
        return $output;
    }
}

// توابع helper برای دسترسی آسان
function add_action($tag, $callback, $priority = 10, $acceptedArgs = 1) {
    return HookSystem::getInstance()->addAction($tag, $callback, $priority, $acceptedArgs);
}

function add_filter($tag, $callback, $priority = 10, $acceptedArgs = 1) {
    return HookSystem::getInstance()->addFilter($tag, $callback, $priority, $acceptedArgs);
}

function remove_action($tag, $callback, $priority = 10) {
    return HookSystem::getInstance()->removeAction($tag, $callback, $priority);
}

function remove_filter($tag, $callback, $priority = 10) {
    return HookSystem::getInstance()->removeFilter($tag, $callback, $priority);
}

function do_action($tag, ...$args) {
    HookSystem::getInstance()->doAction($tag, ...$args);
}

function apply_filters($tag, $value, ...$args) {
    return HookSystem::getInstance()->applyFilters($tag, $value, ...$args);
}

function has_action($tag, $callback = false) {
    return HookSystem::getInstance()->hasAction($tag, $callback);
}

function has_filter($tag, $callback = false) {
    return HookSystem::getInstance()->hasFilter($tag, $callback);
}

function remove_all_actions($tag, $priority = false) {
    return HookSystem::getInstance()->removeAllActions($tag, $priority);
}

function remove_all_filters($tag, $priority = false) {
    return HookSystem::getInstance()->removeAllFilters($tag, $priority);
}

function current_filter() {
    return HookSystem::getInstance()->currentFilter();
}

function get_hook_stats() {
    return HookSystem::getInstance()->getHookStats();
}

function debug_hooks($tag = null, $type = 'all') {
    return HookSystem::getInstance()->debugHooks($tag, $type);
}
?>