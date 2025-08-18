<?php
/**
 * کلاس توابع کمکی
 */
class Helper {
    
    /**
     * فرمت کردن اندازه فایل
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * فرمت کردن تاریخ فارسی
     */
    public static function formatPersianDate($timestamp, $format = 'Y/m/d H:i') {
        $persianMonths = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خردادن', 4 => 'تیر',
            5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
            9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];
        
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        return jdate($format, $timestamp);
    }
    
    /**
     * تولید slug از متن فارسی
     */
    public static function generateSlug($text) {
        // تبدیل حروف فارسی به انگلیسی
        $persianToEnglish = [
            'ا' => 'a', 'ب' => 'b', 'پ' => 'p', 'ت' => 't', 'ث' => 's',
            'ج' => 'j', 'چ' => 'ch', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd',
            'ذ' => 'z', 'ر' => 'r', 'ز' => 'z', 'ژ' => 'zh', 'س' => 's',
            'ش' => 'sh', 'ص' => 's', 'ض' => 'z', 'ط' => 't', 'ظ' => 'z',
            'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'gh', 'ک' => 'k',
            'گ' => 'g', 'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'و' => 'v',
            'ه' => 'h', 'ی' => 'y', 'ء' => '', 'آ' => 'a', 'ة' => 'h',
            'ئ' => 'y', 'ؤ' => 'v'
        ];
        
        $text = str_replace(array_keys($persianToEnglish), array_values($persianToEnglish), $text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * اعتبارسنجی شماره موبایل ایرانی
     */
    public static function validateMobileNumber($mobile) {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        if (strlen($mobile) === 11 && substr($mobile, 0, 2) === '09') {
            return true;
        }
        
        if (strlen($mobile) === 10 && substr($mobile, 0, 1) === '9') {
            return true;
        }
        
        return false;
    }
    
    /**
     * فرمت کردن شماره موبایل
     */
    public static function formatMobileNumber($mobile) {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        if (strlen($mobile) === 10 && substr($mobile, 0, 1) === '9') {
            $mobile = '0' . $mobile;
        }
        
        if (strlen($mobile) === 11) {
            return substr($mobile, 0, 4) . '-' . substr($mobile, 4, 3) . '-' . substr($mobile, 7);
        }
        
        return $mobile;
    }
    
    /**
     * تولید رمز تصادفی
     */
    public static function generateRandomPassword($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $password;
    }
    
    /**
     * کوتاه کردن متن
     */
    public static function truncateText($text, $maxLength = 100, $suffix = '...') {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        
        return mb_substr($text, 0, $maxLength) . $suffix;
    }
    
    /**
     * اعتبارسنجی کد ملی ایرانی
     */
    public static function validateNationalCode($code) {
        $code = preg_replace('/[^0-9]/', '', $code);
        
        if (strlen($code) !== 10) {
            return false;
        }
        
        if (preg_match('/^(\d)\1{9}$/', $code)) {
            return false;
        }
        
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $code[$i] * (10 - $i);
        }
        
        $remainder = $sum % 11;
        $checkDigit = $remainder < 2 ? $remainder : 11 - $remainder;
        
        return $code[9] == $checkDigit;
    }
    
    /**
     * تبدیل اعداد انگلیسی به فارسی
     */
    public static function convertNumbersToPersian($text) {
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        
        return str_replace($englishNumbers, $persianNumbers, $text);
    }
    
    /**
     * تبدیل اعداد فارسی به انگلیسی
     */
    public static function convertNumbersToEnglish($text) {
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($persianNumbers, $englishNumbers, $text);
    }
    
    /**
     * فرمت کردن پول
     */
    public static function formatMoney($amount, $currency = 'تومان') {
        return number_format($amount) . ' ' . $currency;
    }
    
    /**
     * تولید کد تایید
     */
    public static function generateVerificationCode($length = 6) {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    /**
     * بررسی قوی بودن رمز عبور
     */
    public static function isStrongPassword($password) {
        $criteria = [
            'length' => strlen($password) >= 8,
            'lowercase' => preg_match('/[a-z]/', $password),
            'uppercase' => preg_match('/[A-Z]/', $password),
            'number' => preg_match('/[0-9]/', $password),
            'special' => preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
        ];
        
        $score = array_sum($criteria);
        return $score >= 4;
    }
    
    /**
     * تنظیم Timezone ایران
     */
    public static function setIranTimezone() {
        date_default_timezone_set('Asia/Tehran');
    }
    
    /**
     * دریافت IP واقعی کاربر
     */
    public static function getRealIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        return '0.0.0.0';
    }
    
    /**
     * فیلتر کردن آرایه
     */
    public static function arrayFilterRecursive($array, $callback = null) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::arrayFilterRecursive($value, $callback);
            } else {
                if ($callback && !$callback($value, $key)) {
                    unset($array[$key]);
                } elseif (!$callback && empty($value)) {
                    unset($array[$key]);
                }
            }
        }
        
        return $array;
    }
    
    /**
     * محاسبه زمان خواندن متن
     */
    public static function calculateReadingTime($text, $wordsPerMinute = 200) {
        $wordCount = str_word_count(strip_tags($text));
        $minutes = ceil($wordCount / $wordsPerMinute);
        
        return $minutes;
    }
}

// تابع jdate برای تاریخ فارسی (ساده شده)
if (!function_exists('jdate')) {
    function jdate($format, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // تبدیل ساده - در پروژه واقعی از کتابخانه مناسب استفاده کنید
        return date($format, $timestamp);
    }
}
?>