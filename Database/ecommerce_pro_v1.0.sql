-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 02 نوفمبر 2025 الساعة 21:07
-- إصدار الخادم: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_pro`
--

DELIMITER $$
--
-- الإجراءات
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_order_number` (OUT `order_num` VARCHAR(50))   BEGIN
  DECLARE prefix VARCHAR(10);
  DECLARE seq INT;
  
  SELECT setting_value INTO prefix FROM settings WHERE setting_key = 'order_prefix';
  SELECT COALESCE(MAX(CAST(SUBSTRING(order_number, LENGTH(prefix) + 1) AS UNSIGNED)), 0) + 1 
  INTO seq FROM orders;
  
  SET order_num = CONCAT(prefix, LPAD(seq, 8, '0'));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_product_rating` (IN `p_product_id` INT)   BEGIN
  UPDATE products p
  SET 
    rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = p_product_id AND is_approved = 1),
    rating_count = (SELECT COUNT(*) FROM reviews WHERE product_id = p_product_id AND is_approved = 1)
  WHERE id = p_product_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- بنية الجدول `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin','editor') DEFAULT 'admin',
  `role_id` int(11) DEFAULT 1,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `role`, `role_id`, `permissions`, `last_login`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$12$FY5aE7RgklbhcxpsBGkYeexwC019FGErHOMurrQ3qhlld9SS5IGXe', 'eh.m.a@hotmail.com', 'super_admin', 1, NULL, '2025-11-02 19:24:21', 1, '2025-10-02 19:15:24'),
(2, 'samer', '$2y$10$CVNc1QPODGoWkOPkj4XoneH3oglsRu52kgYDe3yLxaCcJFDUGqtli', 'eh.m.aa@hotmail.com', 'admin', 2, NULL, '2025-10-31 15:43:18', 1, '2025-10-31 15:42:33'),
(3, 'amir', '$2y$10$2U5vDMQoF8stkPV0.Iwvve6d17Dbsl7z3.y6LF/ydSh47NVzKFLOy', 'eh.mm.a@hotmail.com', 'admin', 3, NULL, '2025-10-31 15:44:52', 1, '2025-10-31 15:42:59');

-- --------------------------------------------------------

--
-- بنية الجدول `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admin_roles`
--

INSERT INTO `admin_roles` (`id`, `name`, `permissions`, `description`, `is_active`, `created_at`) VALUES
(1, 'المدير العام', '[\"all\"]', 'صلاحيات كاملة على النظام', 1, '2025-10-28 16:15:24'),
(2, 'مدير المنتجات', '[\"products.view\", \"products.create\", \"products.edit\", \"products.delete\", \"categories.view\", \"categories.create\", \"categories.edit\", \"categories.delete\"]', 'إدارة المنتجات والفئات فقط', 1, '2025-10-28 16:15:24'),
(3, 'مدير الطلبات', '[\"orders.view\", \"orders.edit\", \"orders.status\", \"customers.view\"]', 'إدارة الطلبات والعملاء', 1, '2025-10-28 16:15:24');

-- --------------------------------------------------------

--
-- بنية الجدول `agent_orders`
--

CREATE TABLE `agent_orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL,
  `status` enum('assigned','picked_up','on_way','delivered','cancelled') DEFAULT 'assigned',
  `delivery_notes` text DEFAULT NULL,
  `delivery_proof` varchar(255) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `agent_salaries`
--

CREATE TABLE `agent_salaries` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `month` date NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `fixed_salary` decimal(10,2) DEFAULT 0.00,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `total_salary` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `parent_id`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'إلكترونيات', 'electronics', 'أحدث الأجهزة الإلكترونية', NULL, NULL, 1, 1, '2025-10-02 19:15:24'),
(2, 'ملابس', 'clothing', 'أزياء عصرية للرجال والنساء', NULL, NULL, 2, 1, '2025-10-02 19:15:24'),
(3, 'منزل ومطبخ', 'home-kitchen', 'مستلزمات المنزل والمطبخ', NULL, NULL, 3, 1, '2025-10-02 19:15:24'),
(4, 'رياضة', 'sports', 'معدات ومستلزمات رياضية', NULL, NULL, 4, 1, '2025-10-02 19:15:24'),
(5, 'جمال وعناية', 'beauty', 'منتجات التجميل والعناية الشخصية', NULL, NULL, 5, 1, '2025-10-02 19:15:24');

-- --------------------------------------------------------

--
-- بنية الجدول `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` timestamp NULL DEFAULT NULL,
  `orders_count` int(11) DEFAULT 0,
  `total_spent` decimal(12,2) DEFAULT 0.00,
  `last_order_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `address_type` enum('home','work','other') DEFAULT 'home',
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `governorate` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `street_address` text DEFAULT NULL,
  `building_number` varchar(50) DEFAULT NULL,
  `floor_number` varchar(50) DEFAULT NULL,
  `apartment_number` varchar(50) DEFAULT NULL,
  `landmark` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `customer_points`
--

CREATE TABLE `customer_points` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `total_earned` int(11) DEFAULT 0,
  `total_spent` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `customer_wallets`
--

CREATE TABLE `customer_wallets` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deposited` decimal(12,2) DEFAULT 0.00,
  `total_withdrawn` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `customer_wallets`
--

INSERT INTO `customer_wallets` (`id`, `customer_id`, `balance`, `total_deposited`, `total_withdrawn`, `created_at`, `updated_at`) VALUES
(0, 1, 100.00, 100.00, 0.00, '2025-10-31 15:40:43', '2025-10-31 15:40:43'),
(0, 1, 1000.00, 1000.00, 0.00, '2025-10-31 17:49:25', '2025-10-31 17:49:25'),
(0, 1, 100.00, 100.00, 0.00, '2025-10-31 18:38:38', '2025-10-31 18:38:38'),
(0, 1, 100.00, 100.00, 0.00, '2025-10-31 18:39:27', '2025-10-31 18:39:27'),
(0, 1, 100.00, 100.00, 0.00, '2025-10-31 18:41:27', '2025-10-31 18:41:27'),
(0, 1, 1000.00, 1000.00, 0.00, '2025-11-01 07:25:12', '2025-11-01 07:25:12'),
(0, 1, 950.00, 950.00, 0.00, '2025-11-01 07:25:47', '2025-11-01 07:25:47'),
(0, 1, 950.00, 950.00, 0.00, '2025-11-01 07:27:08', '2025-11-01 07:27:08'),
(0, 1, 950.00, 950.00, 0.00, '2025-11-01 07:27:40', '2025-11-01 07:27:40'),
(0, 1, 900000.00, 900000.00, 0.00, '2025-11-01 07:50:27', '2025-11-01 07:50:27'),
(0, 1, 10000.00, 10000.00, 0.00, '2025-11-01 07:51:09', '2025-11-01 07:51:09');

-- --------------------------------------------------------

--
-- Stand-in structure for view `daily_sales_stats`
-- (See below for the actual view)
--
CREATE TABLE `daily_sales_stats` (
`sale_date` date
,`orders_count` bigint(21)
,`total_revenue` decimal(34,2)
,`avg_order_value` decimal(16,6)
,`paid_revenue` decimal(34,2)
);

-- --------------------------------------------------------

--
-- بنية الجدول `delivery_agents`
--

CREATE TABLE `delivery_agents` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `vehicle_type` enum('motorcycle','car','bicycle','truck') DEFAULT 'motorcycle',
  `vehicle_number` varchar(100) DEFAULT NULL,
  `salary_type` enum('fixed','commission','mixed') DEFAULT 'fixed',
  `fixed_salary` decimal(10,2) DEFAULT 0.00,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `area` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `offer_conditions`
--

CREATE TABLE `offer_conditions` (
  `id` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `condition_type` enum('min_order_amount','category','customer_group') NOT NULL,
  `condition_value` varchar(255) DEFAULT NULL,
  `operator` enum('=','>','<','>=','<=','IN') DEFAULT '=',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `governorate` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `payment_method` enum('cod','visa','instapay','vodafone_cash','fawry') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_transaction_id` varchar(255) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','returned') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- القوادح `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_insert` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
  IF NEW.customer_id IS NOT NULL AND NEW.payment_status = 'paid' THEN
    UPDATE customers 
    SET 
      orders_count = orders_count + 1,
      total_spent = total_spent + NEW.total,
      last_order_date = NEW.created_at
    WHERE id = NEW.customer_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_order_status_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
  IF OLD.status != NEW.status THEN
    INSERT INTO order_status_history (order_id, old_status, new_status, comment)
    VALUES (NEW.id, OLD.status, NEW.status, CONCAT('تم تحديث الحالة من ', OLD.status, ' إلى ', NEW.status));
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- بنية الجدول `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_title` varchar(255) NOT NULL,
  `product_sku` varchar(100) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `points` int(11) NOT NULL,
  `bonus_points` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `packages`
--

INSERT INTO `packages` (`id`, `name`, `description`, `price`, `points`, `bonus_points`, `image`, `is_featured`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'الباقة البرونزية', 'باقة مبتدئة للحصول على نقاط', 500.00, 5000, 500, NULL, 0, 1, 1, '2025-10-24 05:16:12', '2025-10-24 05:16:12'),
(2, 'الباقة الفضية', 'باقة متوسطة مع نقاط إضافية', 1000.00, 11000, 1000, NULL, 1, 2, 1, '2025-10-24 05:16:12', '2025-10-24 05:16:12'),
(3, 'الباقة الذهبية', 'باقة متقدمة مع مكافآت خاصة', 2000.00, 24000, 4000, NULL, 1, 3, 1, '2025-10-24 05:16:12', '2025-10-24 05:16:12'),
(4, 'الباقة البلاتينية', 'باقة احترافية لقوة شرائية أكبر', 5000.00, 60000, 10000, NULL, 1, 4, 1, '2025-10-24 05:16:12', '2025-10-24 05:16:12'),
(5, 'الباقة الماسية', 'باقة VIP مع أفضل العروض', 10000.00, 130000, 30000, NULL, 1, 5, 1, '2025-10-24 05:16:12', '2025-10-24 05:16:12');

-- --------------------------------------------------------

--
-- بنية الجدول `package_orders`
--

CREATE TABLE `package_orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `points_amount` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','visa','instapay','vodafone_cash','fawry') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `points_added` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `partners`
--

CREATE TABLE `partners` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `partnership_type` enum('supplier','investor','distributor','strategic') DEFAULT 'supplier',
  `investment_amount` decimal(12,2) DEFAULT 0.00,
  `profit_share` decimal(5,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `point_transactions`
--

CREATE TABLE `point_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type` enum('earn','spend','expire') NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference_type` enum('purchase','coupon','gift','reward','manual') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `price_countdowns`
--

CREATE TABLE `price_countdowns` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `countdown_end` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_price` decimal(10,2) GENERATED ALWAYS AS (`price` - greatest(`discount_amount`,`price` * `discount_percentage` / 100)) STORED,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `orders_count` int(11) DEFAULT 0,
  `rating_avg` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_condition` enum('new','used','refurbished','needs_repair') DEFAULT 'new',
  `special_offer_type` enum('none','points','coupon','gift','discount') DEFAULT 'none',
  `special_offer_value` varchar(255) DEFAULT NULL,
  `auction_enabled` tinyint(1) DEFAULT 0,
  `auction_end_time` timestamp NULL DEFAULT NULL,
  `starting_price` decimal(10,2) DEFAULT 0.00,
  `current_bid` decimal(10,2) DEFAULT 0.00,
  `bid_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `store_type` enum('main','customer') DEFAULT 'main'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `product_bids`
--

CREATE TABLE `product_bids` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `bid_amount` decimal(10,2) NOT NULL,
  `bid_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_winning` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_main` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `product_negotiations`
--

CREATE TABLE `product_negotiations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `offered_price` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','rejected','counter_offer') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `counter_price` decimal(10,2) DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `product_offers`
--

CREATE TABLE `product_offers` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `offer_type` enum('buy2_get1','discount','free_shipping') DEFAULT 'buy2_get1',
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `min_quantity` int(11) DEFAULT 3,
  `max_quantity` int(11) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referred_id` int(11) NOT NULL,
  `referral_code` varchar(50) NOT NULL,
  `status` enum('pending','signed_up','completed_order','expired') DEFAULT 'pending',
  `points_earned` int(11) DEFAULT 0,
  `completed_order_id` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `referral_links`
--

CREATE TABLE `referral_links` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `referral_code` varchar(50) NOT NULL,
  `referral_url` varchar(255) NOT NULL,
  `clicks` int(11) DEFAULT 0,
  `signups` int(11) DEFAULT 0,
  `completed_orders` int(11) DEFAULT 0,
  `total_earned_points` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `admin_reply` text DEFAULT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `helpful_votes` int(11) DEFAULT 0,
  `total_votes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `scratch_cards`
--

CREATE TABLE `scratch_cards` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `card_code` varchar(50) NOT NULL,
  `reward_type` enum('points','discount','gift','cash') DEFAULT 'points',
  `reward_value` decimal(10,2) NOT NULL,
  `reward_description` varchar(255) DEFAULT NULL,
  `is_scratched` tinyint(1) DEFAULT 0,
  `scratched_at` timestamp NULL DEFAULT NULL,
  `is_claimed` tinyint(1) DEFAULT 0,
  `claimed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `created_at`, `updated_at`) VALUES
(1, 'store_name', 'Mary', 'text', '2025-10-02 19:15:24', '2025-10-03 16:28:26'),
(2, 'store_description', 'أفضل الأسعار وأعلى جودة', 'text', '2025-10-02 19:15:24', '2025-10-02 19:15:24'),
(3, 'store_email', 'eh.m.a@hotmail.com', 'text', '2025-10-02 19:15:24', '2025-10-03 07:08:51'),
(4, 'store_phone', '01116030797', 'text', '2025-10-02 19:15:24', '2025-10-03 07:08:51'),
(5, 'currency', 'EGP', 'text', '2025-10-02 19:15:24', '2025-10-02 19:15:24'),
(6, 'currency_symbol', 'ج.م', 'text', '2025-10-02 19:15:24', '2025-10-02 19:15:24'),
(7, 'tax_rate', '0', 'number', '2025-10-02 19:15:24', '2025-10-02 19:15:24'),
(8, 'shipping_cost_cairo', '30', 'number', '2025-10-02 19:15:24', '2025-10-20 02:59:27'),
(9, 'shipping_cost_giza', '30', 'number', '2025-10-02 19:15:24', '2025-10-20 02:59:27'),
(10, 'shipping_cost_alex', '50', 'number', '2025-10-02 19:15:24', '2025-10-20 02:59:27'),
(11, 'shipping_cost_other', '60', 'number', '2025-10-02 19:15:24', '2025-10-20 02:59:27'),
(12, 'free_shipping_threshold', '50.0', 'number', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(13, 'order_prefix', 'ORD-MARY', 'text', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(14, 'items_per_page', '12', 'number', '2025-10-02 19:15:24', '2025-10-02 19:15:24'),
(15, 'maintenance_mode', '0', 'boolean', '2025-10-02 19:15:24', '2025-10-09 14:37:00'),
(16, 'google_analytics_id', 'https://www.facebook.com/civil.eng.ihab', 'text', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(17, 'facebook_pixel_id', 'https://www.facebook.com/civil.eng.ihab', 'text', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(18, 'whatsapp_number', '01116030797', 'text', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(19, 'facebook_url', 'https://www.facebook.com/civil.eng.ihab', 'text', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(20, 'instagram_url', 'https://www.facebook.com/civil.eng.ihab', 'text', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(21, 'twitter_url', 'https://www.facebook.com/civil.eng.ihab', 'text', '2025-10-02 19:15:24', '2025-10-03 16:49:17'),
(125, 'points_enabled', '1', 'boolean', '2025-10-24 04:16:19', '2025-10-24 04:16:19'),
(126, 'points_earn_rate', '10', 'number', '2025-10-24 04:16:19', '2025-10-24 04:16:19'),
(127, 'points_currency_rate', '100', 'number', '2025-10-24 04:16:19', '2025-10-24 04:16:19'),
(128, 'points_min_redeem', '1000', 'number', '2025-10-24 04:16:19', '2025-10-24 04:16:19'),
(129, 'points_expire_days', '365', 'number', '2025-10-24 04:16:19', '2025-10-24 04:16:19'),
(130, 'referral_system_enabled', '1', 'boolean', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(131, 'referral_points_referrer', '500', 'number', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(132, 'referral_points_referred', '300', 'number', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(133, 'referral_min_order_amount', '100', 'number', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(134, 'referral_expiry_days', '30', 'number', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(135, 'referral_commission_rate', '5', 'number', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(136, 'referral_coupon_enabled', '1', 'boolean', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(137, 'referral_coupon_code', 'WELCOME10', 'text', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(138, 'referral_coupon_discount', '10', 'number', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(139, 'referral_coupon_min_order', '200', 'number', '2025-10-24 07:07:22', '2025-10-24 07:07:22'),
(140, 'negotiation_enabled', '1', 'boolean', '2025-10-24 08:13:01', '2025-10-26 18:59:35'),
(141, 'negotiation_min_percentage', '70', 'number', '2025-10-24 08:13:01', '2025-10-26 18:59:35'),
(142, 'negotiation_auto_approve', '0', 'boolean', '2025-10-24 08:13:01', '2025-10-24 08:13:01'),
(143, 'auction_enabled', '1', 'text', '2025-10-26 17:51:01', '2025-10-26 18:59:35'),
(144, 'countdown_enabled', '1', 'text', '2025-10-26 17:51:01', '2025-10-26 18:59:35'),
(145, 'special_offers_enabled', '1', 'text', '2025-10-26 17:51:01', '2025-10-26 18:59:35');

-- --------------------------------------------------------

--
-- بنية الجدول `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` int(11) NOT NULL,
  `region` varchar(255) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `delivery_time` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `shipping_rates`
--

INSERT INTO `shipping_rates` (`id`, `region`, `cost`, `delivery_time`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'القاهرة', 30.00, '2-3 أيام عمل', 1, '2025-10-03 17:15:37', '2025-10-03 17:15:37'),
(2, 'الجيزة', 30.00, '2-3 أيام عمل', 1, '2025-10-03 17:15:37', '2025-10-03 17:15:37'),
(3, 'الإسكندرية', 50.00, '3-4 أيام عمل', 1, '2025-10-03 17:15:37', '2025-10-03 17:15:37'),
(4, 'القليوبية والشرقية والدقهلية', 60.00, '4-5 أيام عمل', 1, '2025-10-03 17:15:37', '2025-10-03 17:15:37'),
(5, 'باقي المحافظات', 70.00, '5-7 أيام عمل', 0, '2025-10-03 17:15:37', '2025-10-20 02:59:27');

-- --------------------------------------------------------

--
-- Stand-in structure for view `top_selling_products`
-- (See below for the actual view)
--
CREATE TABLE `top_selling_products` (
`id` int(11)
,`title` varchar(255)
,`price` decimal(10,2)
,`final_price` decimal(10,2)
,`stock` int(11)
,`orders_count` int(11)
,`total_sold` decimal(32,0)
,`total_revenue` decimal(34,2)
);

-- --------------------------------------------------------

--
-- بنية الجدول `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `type` enum('deposit','withdrawal','purchase','refund','bonus') NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference_type` enum('order','manual','transfer','refund') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'completed',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `customer_id`, `amount`, `type`, `description`, `reference_type`, `reference_id`, `status`, `transaction_date`, `created_at`) VALUES
(0, 1, 100.00, 'deposit', '3232', 'manual', NULL, 'completed', '2025-10-31 15:40:43', '2025-10-31 15:40:43'),
(0, 1, 1000.00, 'deposit', 'شحن محفظة عبر instapay', 'manual', NULL, 'pending', '2025-10-31 17:49:25', '2025-10-31 17:49:25'),
(0, 1, 100.00, 'deposit', '3232', 'manual', NULL, 'completed', '2025-10-31 18:38:38', '2025-10-31 18:38:38'),
(0, 1, 100.00, 'deposit', '3232', 'manual', NULL, 'completed', '2025-10-31 18:39:27', '2025-10-31 18:39:27'),
(0, 1, 100.00, 'deposit', '0', 'manual', NULL, 'completed', '2025-10-31 18:41:27', '2025-10-31 18:41:27'),
(0, 1, 1000.00, 'deposit', 'شحن محفظة عبر instapay', 'manual', NULL, 'pending', '2025-11-01 07:25:12', '2025-11-01 07:25:12'),
(0, 1, 950.00, 'deposit', '0', 'manual', NULL, 'completed', '2025-11-01 07:25:47', '2025-11-01 07:25:47'),
(0, 1, 950.00, 'deposit', '0', 'manual', NULL, 'completed', '2025-11-01 07:27:08', '2025-11-01 07:27:08'),
(0, 1, 950.00, 'deposit', '0', 'manual', NULL, 'completed', '2025-11-01 07:27:40', '2025-11-01 07:27:40'),
(0, 1, 900000.00, 'deposit', '0', 'manual', NULL, 'completed', '2025-11-01 07:50:27', '2025-11-01 07:50:27'),
(0, 1, 10000.00, 'deposit', 'شحن محفظة عبر vodafone_cash', 'manual', NULL, 'pending', '2025-11-01 07:51:09', '2025-11-01 07:51:09');

-- --------------------------------------------------------

--
-- بنية الجدول `wholesalers`
--

CREATE TABLE `wholesalers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `discount_rate` decimal(5,2) DEFAULT 0.00,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `current_balance` decimal(12,2) DEFAULT 0.00,
  `payment_terms` varchar(100) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `wholesaler_products`
--

CREATE TABLE `wholesaler_products` (
  `id` int(11) NOT NULL,
  `wholesaler_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `wholesale_price` decimal(10,2) NOT NULL,
  `min_order_qty` int(11) DEFAULT 1,
  `stock_quantity` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `daily_sales_stats`
--
DROP TABLE IF EXISTS `daily_sales_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `daily_sales_stats`  AS SELECT cast(`orders`.`created_at` as date) AS `sale_date`, count(0) AS `orders_count`, sum(`orders`.`total`) AS `total_revenue`, avg(`orders`.`total`) AS `avg_order_value`, sum(case when `orders`.`payment_status` = 'paid' then `orders`.`total` else 0 end) AS `paid_revenue` FROM `orders` WHERE `orders`.`status` not in ('cancelled','returned') GROUP BY cast(`orders`.`created_at` as date) ORDER BY cast(`orders`.`created_at` as date) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `top_selling_products`
--
DROP TABLE IF EXISTS `top_selling_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `top_selling_products`  AS SELECT `p`.`id` AS `id`, `p`.`title` AS `title`, `p`.`price` AS `price`, `p`.`final_price` AS `final_price`, `p`.`stock` AS `stock`, `p`.`orders_count` AS `orders_count`, sum(`oi`.`qty`) AS `total_sold`, sum(`oi`.`total_price`) AS `total_revenue` FROM ((`products` `p` left join `order_items` `oi` on(`p`.`id` = `oi`.`product_id`)) left join `orders` `o` on(`oi`.`order_id` = `o`.`id`)) WHERE `o`.`status` not in ('cancelled','returned') GROUP BY `p`.`id` ORDER BY sum(`oi`.`qty`) DESC LIMIT 0, 50 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `agent_orders`
--
ALTER TABLE `agent_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `agent_salaries`
--
ALTER TABLE `agent_salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- Indexes for table `customer_points`
--
ALTER TABLE `customer_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`);

--
-- Indexes for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `offer_conditions`
--
ALTER TABLE `offer_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offer_id` (`offer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_created_status` (`created_at`,`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_orders`
--
ALTER TABLE `package_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `price_countdowns`
--
ALTER TABLE `price_countdowns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_price` (`final_price`),
  ADD KEY `idx_active_featured` (`is_active`,`is_featured`),
  ADD KEY `created_by` (`created_by`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`title`,`description`);

--
-- Indexes for table `product_bids`
--
ALTER TABLE `product_bids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_negotiations`
--
ALTER TABLE `product_negotiations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `product_offers`
--
ALTER TABLE `product_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referred_id` (`referred_id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `referral_code` (`referral_code`),
  ADD KEY `completed_order_id` (`completed_order_id`);

--
-- Indexes for table `referral_links`
--
ALTER TABLE `referral_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD UNIQUE KEY `customer_id` (`customer_id`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_approved` (`is_approved`),
  ADD KEY `idx_approved_verified` (`is_approved`,`is_verified_purchase`);

--
-- Indexes for table `scratch_cards`
--
ALTER TABLE `scratch_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_code` (`card_code`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wholesalers`
--
ALTER TABLE `wholesalers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wholesaler_products`
--
ALTER TABLE `wholesaler_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wholesaler_id` (`wholesaler_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `agent_orders`
--
ALTER TABLE `agent_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_salaries`
--
ALTER TABLE `agent_salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offer_conditions`
--
ALTER TABLE `offer_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `package_orders`
--
ALTER TABLE `package_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_countdowns`
--
ALTER TABLE `price_countdowns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_bids`
--
ALTER TABLE `product_bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_negotiations`
--
ALTER TABLE `product_negotiations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_offers`
--
ALTER TABLE `product_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referral_links`
--
ALTER TABLE `referral_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scratch_cards`
--
ALTER TABLE `scratch_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wholesalers`
--
ALTER TABLE `wholesalers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wholesaler_products`
--
ALTER TABLE `wholesaler_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `agent_orders`
--
ALTER TABLE `agent_orders`
  ADD CONSTRAINT `agent_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_orders_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `delivery_agents` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `agent_salaries`
--
ALTER TABLE `agent_salaries`
  ADD CONSTRAINT `agent_salaries_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `delivery_agents` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `customer_points`
--
ALTER TABLE `customer_points`
  ADD CONSTRAINT `customer_points_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `offer_conditions`
--
ALTER TABLE `offer_conditions`
  ADD CONSTRAINT `offer_conditions_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `product_offers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- قيود الجداول `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `package_orders`
--
ALTER TABLE `package_orders`
  ADD CONSTRAINT `package_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_orders_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD CONSTRAINT `point_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `price_countdowns`
--
ALTER TABLE `price_countdowns`
  ADD CONSTRAINT `price_countdowns_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `product_bids`
--
ALTER TABLE `product_bids`
  ADD CONSTRAINT `product_bids_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_bids_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `product_negotiations`
--
ALTER TABLE `product_negotiations`
  ADD CONSTRAINT `product_negotiations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_negotiations_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `product_offers`
--
ALTER TABLE `product_offers`
  ADD CONSTRAINT `product_offers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_3` FOREIGN KEY (`completed_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `referral_links`
--
ALTER TABLE `referral_links`
  ADD CONSTRAINT `referral_links_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `scratch_cards`
--
ALTER TABLE `scratch_cards`
  ADD CONSTRAINT `scratch_cards_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scratch_cards_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `wholesaler_products`
--
ALTER TABLE `wholesaler_products`
  ADD CONSTRAINT `wholesaler_products_ibfk_1` FOREIGN KEY (`wholesaler_id`) REFERENCES `wholesalers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
