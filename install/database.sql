-- ایجاد دیتابیس
CREATE DATABASE IF NOT EXISTS `hesabino` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
USE `hesabino`;

-- غیرفعال کردن foreign key checks برای نصب
SET FOREIGN_KEY_CHECKS=0;

-- جدول کاربران
CREATE TABLE IF NOT EXISTS `hb_users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `full_name` varchar(100) NOT NULL,
    `mobile` varchar(11) DEFAULT NULL,
    `company` varchar(100) DEFAULT NULL,
    `avatar` varchar(255) DEFAULT NULL,
    `status` enum('pending','active','blocked','deleted') NOT NULL DEFAULT 'pending',
    `email_verified_at` datetime DEFAULT NULL,
    `mobile_verified_at` datetime DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول نقش‌ها
CREATE TABLE IF NOT EXISTS `hb_roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `display_name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول دسترسی‌ها
CREATE TABLE IF NOT EXISTS `hb_permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `display_name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول نقش‌های کاربر
CREATE TABLE IF NOT EXISTS `hb_user_roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `role_id` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_role` (`user_id`,`role_id`),
    KEY `role_id` (`role_id`),
    CONSTRAINT `user_roles_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_roles_role_id` FOREIGN KEY (`role_id`) REFERENCES `hb_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول دسترسی‌های نقش
CREATE TABLE IF NOT EXISTS `hb_role_permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `role_id` int(11) NOT NULL,
    `permission_id` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_permission` (`role_id`,`permission_id`),
    KEY `permission_id` (`permission_id`),
    CONSTRAINT `role_permissions_role_id` FOREIGN KEY (`role_id`) REFERENCES `hb_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `role_permissions_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `hb_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول توکن‌های کاربر
CREATE TABLE IF NOT EXISTS `hb_user_tokens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `token` varchar(64) NOT NULL,
    `created_at` datetime NOT NULL,
    `expires` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `user_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول دسته‌بندی محصولات (باید قبل از محصولات ایجاد شود)
CREATE TABLE IF NOT EXISTS `hb_product_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `parent_id` int(11) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_slug` (`user_id`,`slug`),
    KEY `parent_id` (`parent_id`),
    KEY `status` (`status`),
    CONSTRAINT `product_categories_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_categories_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `hb_product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول محصولات
CREATE TABLE IF NOT EXISTS `hb_products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `category_id` int(11) DEFAULT NULL,
    `code` varchar(50) NOT NULL,
    `name` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `price` decimal(15,2) NOT NULL DEFAULT 0.00,
    `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `unit` varchar(20) DEFAULT NULL,
    `stock` int(11) NOT NULL DEFAULT 0,
    `min_stock` int(11) NOT NULL DEFAULT 0,
    `image` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_code` (`user_id`,`code`),
    UNIQUE KEY `user_slug` (`user_id`,`slug`),
    KEY `category_id` (`category_id`),
    KEY `status` (`status`),
    CONSTRAINT `products_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `products_category_id` FOREIGN KEY (`category_id`) REFERENCES `hb_product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول مشتریان
CREATE TABLE IF NOT EXISTS `hb_customers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `company` varchar(100) DEFAULT NULL,
    `national_code` varchar(10) DEFAULT NULL,
    `economic_code` varchar(12) DEFAULT NULL,
    `registration_number` varchar(20) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `mobile` varchar(11) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `website` varchar(100) DEFAULT NULL,
    `province` varchar(50) DEFAULT NULL,
    `city` varchar(50) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `postal_code` varchar(10) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    CONSTRAINT `customers_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول فاکتورها
CREATE TABLE IF NOT EXISTS `hb_invoices` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `customer_id` int(11) NOT NULL,
    `number` varchar(20) NOT NULL,
    `date` date NOT NULL,
    `due_date` date DEFAULT NULL,
    `status` enum('draft','sent','viewed','paid','cancelled','deleted') NOT NULL DEFAULT 'draft',
    `payment_status` enum('unpaid','partial','paid','overpaid') NOT NULL DEFAULT 'unpaid',
    `payment_method` varchar(50) DEFAULT NULL,
    `currency` varchar(10) NOT NULL DEFAULT 'IRR',
    `exchange_rate` decimal(15,6) NOT NULL DEFAULT 1.000000,
    `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
    `tax_total` decimal(15,2) NOT NULL DEFAULT 0.00,
    `discount_total` decimal(15,2) NOT NULL DEFAULT 0.00,
    `shipping` decimal(15,2) NOT NULL DEFAULT 0.00,
    `total` decimal(15,2) NOT NULL DEFAULT 0.00,
    `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `notes` text DEFAULT NULL,
    `terms` text DEFAULT NULL,
    `footer` text DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_number` (`user_id`,`number`),
    KEY `customer_id` (`customer_id`),
    KEY `status` (`status`),
    KEY `payment_status` (`payment_status`),
    CONSTRAINT `invoices_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `invoices_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `hb_customers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول اقلام فاکتور
CREATE TABLE IF NOT EXISTS `hb_invoice_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_id` int(11) NOT NULL,
    `product_id` int(11) DEFAULT NULL,
    `description` text NOT NULL,
    `quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
    `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
    `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
    `total` decimal(15,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `invoice_id` (`invoice_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `invoice_items_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `hb_invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `invoice_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `hb_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول پرداخت‌ها
CREATE TABLE IF NOT EXISTS `hb_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `invoice_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `method` varchar(50) NOT NULL,
    `reference` varchar(100) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `status` enum('pending','completed','failed','refunded','deleted') NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `invoice_id` (`invoice_id`),
    KEY `status` (`status`),
    CONSTRAINT `payments_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `payments_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `hb_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- جدول تنظیمات کاربر
CREATE TABLE IF NOT EXISTS `hb_user_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `key` varchar(50) NOT NULL,
    `value` text DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_key` (`user_id`,`key`),
    CONSTRAINT `user_settings_user_id` FOREIGN KEY (`user_id`) REFERENCES `hb_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- فعال کردن foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- درج داده‌های پیش‌فرض

-- نقش‌های پیش‌فرض
INSERT INTO `hb_roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
('admin', 'مدیر', 'دسترسی کامل به تمام بخش‌ها', NOW(), NOW()),
('user', 'کاربر', 'دسترسی محدود به بخش‌های عمومی', NOW(), NOW());

-- دسترسی‌های پیش‌فرض
INSERT INTO `hb_permissions` (`name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
-- مدیریت کاربران
('users.view', 'مشاهده کاربران', 'مشاهده لیست کاربران', NOW(), NOW()),
('users.create', 'ایجاد کاربر', 'ایجاد کاربر جدید', NOW(), NOW()),
('users.edit', 'ویرایش کاربر', 'ویرایش اطلاعات کاربر', NOW(), NOW()),
('users.delete', 'حذف کاربر', 'حذف کاربر', NOW(), NOW()),

-- مدیریت نقش‌ها
('roles.view', 'مشاهده نقش‌ها', 'مشاهده لیست نقش‌ها', NOW(), NOW()),
('roles.create', 'ایجاد نقش', 'ایجاد نقش جدید', NOW(), NOW()),
('roles.edit', 'ویرایش نقش', 'ویرایش نقش', NOW(), NOW()),
('roles.delete', 'حذف نقش', 'حذف نقش', NOW(), NOW()),

-- مدیریت دسترسی‌ها
('permissions.view', 'مشاهده دسترسی‌ها', 'مشاهده لیست دسترسی‌ها', NOW(), NOW()),
('permissions.create', 'ایجاد دسترسی', 'ایجاد دسترسی جدید', NOW(), NOW()),
('permissions.edit', 'ویرایش دسترسی', 'ویرایش دسترسی', NOW(), NOW()),
('permissions.delete', 'حذف دسترسی', 'حذف دسترسی', NOW(), NOW()),

-- مدیریت محصولات
('products.view', 'مشاهده محصولات', 'مشاهده لیست محصولات', NOW(), NOW()),
('products.create', 'ایجاد محصول', 'ایجاد محصول جدید', NOW(), NOW()),
('products.edit', 'ویرایش محصول', 'ویرایش محصول', NOW(), NOW()),
('products.delete', 'حذف محصول', 'حذف محصول', NOW(), NOW()),

-- مدیریت مشتریان
('customers.view', 'مشاهده مشتریان', 'مشاهده لیست مشتریان', NOW(), NOW()),
('customers.create', 'ایجاد مشتری', 'ایجاد مشتری جدید', NOW(), NOW()),
('customers.edit', 'ویرایش مشتری', 'ویرایش مشتری', NOW(), NOW()),
('customers.delete', 'حذف مشتری', 'حذف مشتری', NOW(), NOW()),

-- مدیریت فاکتورها
('invoices.view', 'مشاهده فاکتورها', 'مشاهده لیست فاکتورها', NOW(), NOW()),
('invoices.create', 'ایجاد فاکتور', 'ایجاد فاکتور جدید', NOW(), NOW()),
('invoices.edit', 'ویرایش فاکتور', 'ویرایش فاکتور', NOW(), NOW()),
('invoices.delete', 'حذف فاکتور', 'حذف فاکتور', NOW(), NOW()),

-- مدیریت پرداخت‌ها
('payments.view', 'مشاهده پرداخت‌ها', 'مشاهده لیست پرداخت‌ها', NOW(), NOW()),
('payments.create', 'ثبت پرداخت', 'ثبت پرداخت جدید', NOW(), NOW()),
('payments.edit', 'ویرایش پرداخت', 'ویرایش پرداخت', NOW(), NOW()),
('payments.delete', 'حذف پرداخت', 'حذف پرداخت', NOW(), NOW()),

-- گزارشات
('reports.view', 'مشاهده گزارش‌ها', 'مشاهده گزارش‌های سیستم', NOW(), NOW()),
('reports.export', 'خروجی گزارش‌ها', 'دریافت خروجی از گزارش‌ها', NOW(), NOW());

-- اختصاص دسترسی‌ها به نقش admin
INSERT INTO `hb_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 
    (SELECT `id` FROM `hb_roles` WHERE `name` = 'admin'),
    `id`,
    NOW()
FROM `hb_permissions`;

-- اختصاص دسترسی‌های محدود به نقش user
INSERT INTO `hb_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 
    (SELECT `id` FROM `hb_roles` WHERE `name` = 'user'),
    `id`,
    NOW()
FROM `hb_permissions`
WHERE `name` IN (
    'products.view',
    'customers.view',
    'customers.create',
    'customers.edit',
    'invoices.view',
    'invoices.create',
    'invoices.edit',
    'payments.view',
    'payments.create',
    'reports.view'
);