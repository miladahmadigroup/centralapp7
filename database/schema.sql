-- جداول اصلی سیستم اپ مرکزی

-- تنظیمات سیستم
CREATE TABLE `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `option_name` varchar(255) NOT NULL,
    `option_value` longtext,
    `autoload` enum('yes','no') DEFAULT 'yes',
    PRIMARY KEY (`id`),
    UNIQUE KEY `option_name` (`option_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کاربران مدیریت سیستم
CREATE TABLE `admin_users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` varchar(50) DEFAULT 'admin',
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `last_login` timestamp NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اپ‌های کلاینت (بدون API Key)
CREATE TABLE `client_apps` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `domain` varchar(255) NOT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `last_access` timestamp NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کلیدهای API (مستقل)
CREATE TABLE `api_keys` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `api_key` varchar(255) NOT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `last_used` timestamp NULL,
    `usage_count` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `api_key` (`api_key`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- افزونه‌های نصب شده
CREATE TABLE `plugins` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `version` varchar(20) NOT NULL,
    `status` enum('active','inactive') DEFAULT 'inactive',
    `install_date` timestamp DEFAULT CURRENT_TIMESTAMP,
    `update_date` timestamp NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- لاگ‌های API (با ارجاع به api_keys)
CREATE TABLE `api_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `api_key_id` int(11),
    `endpoint` varchar(255) NOT NULL,
    `method` varchar(10) NOT NULL,
    `ip_address` varchar(45),
    `user_agent` text,
    `response_code` int(3),
    `response_time` decimal(10,3),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `api_key_id` (`api_key_id`),
    FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;