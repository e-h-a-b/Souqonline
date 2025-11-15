-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 28 أكتوبر 2025 الساعة 23:10
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

--
-- إرجاع أو استيراد بيانات الجدول `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 08:31:46'),
(2, 1, 'order_cancelled', 'تم إلغاء الطلب #ORD00000011', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 09:17:06'),
(3, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 15:17:15'),
(4, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 15:18:57'),
(5, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:12:35'),
(6, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:12:43'),
(7, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:13:10'),
(8, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:14:52'),
(9, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:15:23'),
(10, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:16:02'),
(11, NULL, 'order_cancelled', 'تم إلغاء الطلب #ORD00000013', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:18:17'),
(12, 1, 'coupon_updated', 'تم تحديث الكوبون بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:20:00'),
(13, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:20:56'),
(14, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:23:22'),
(15, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:28:26'),
(16, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:28:41'),
(17, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:31:58'),
(18, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:32:09'),
(19, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:49:17'),
(20, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:49:51'),
(21, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 16:57:52'),
(22, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 17:02:19'),
(23, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:08:04'),
(24, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:08:22'),
(25, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:11:40'),
(26, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:11:49'),
(27, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:12:53'),
(28, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:24:36'),
(29, 1, 'settings_updated', 'تم تحديث إعدادات المتجر والشحن', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:24:36'),
(30, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:24:38'),
(31, 1, 'settings_updated', 'تم تحديث إعدادات المتجر والشحن', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:24:38'),
(32, 1, 'settings_updated', 'تم تحديث إعدادات المتجر والشحن', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:25:09'),
(33, 1, 'settings_updated', 'تم تحديث إعدادات المتجر والشحن', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:25:28'),
(34, 1, 'settings_updated', 'تم تحديث إعدادات المتجر والشحن', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:25:31'),
(35, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:46'),
(36, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:48'),
(37, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:48'),
(38, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:49'),
(39, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:49'),
(40, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:50'),
(41, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:50'),
(42, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:27:51'),
(43, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 17:28:00'),
(44, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 18:21:03'),
(45, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 18:47:07'),
(46, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-04 04:29:26'),
(47, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-04 04:31:02'),
(48, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-04 04:31:35'),
(49, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-04 04:31:54'),
(50, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-04 04:32:27'),
(51, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:45:00'),
(52, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:36:55'),
(53, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:38:51'),
(54, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:40:30'),
(55, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:41:01'),
(56, 1, 'category_created', 'تم إضافة الفئة بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:41:54'),
(57, 1, 'category_deleted', 'تم حذف الفئة #6', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:42:01'),
(58, 1, 'coupon_created', 'تم إضافة الكوبون بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:44:06'),
(59, 1, 'coupon_updated', 'تم تحديث الكوبون بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:44:33'),
(60, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-08 15:57:08'),
(61, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-08 15:57:44'),
(62, 1, 'order_status_updated', 'تم تحديث حالة الطلب #27 إلى confirmed', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:46:25'),
(63, 1, 'order_status_updated', 'تم تحديث حالة الطلب #27 إلى shipped', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:46:52'),
(64, 1, 'order_status_updated', 'تم تحديث حالة الطلب #28 إلى confirmed', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:48:53'),
(65, 1, 'order_status_updated', 'تم تحديث حالة الطلب #28 إلى shipped', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:49:50'),
(66, 1, 'payment_status_updated', 'تم تحديث حالة الدفع للطلب #28 إلى paid', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:50:21'),
(67, 1, 'order_status_updated', 'تم تحديث حالة الطلب #28 إلى delivered', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:50:49'),
(68, 1, 'payment_status_updated', 'تم تحديث حالة الدفع للطلب #26 إلى failed', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:52:14'),
(69, 1, 'payment_status_updated', 'تم تحديث حالة الدفع للطلب #26 إلى refunded', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:52:50'),
(70, 1, 'order_status_updated', 'تم تحديث حالة الطلب #26 إلى cancelled', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:53:26'),
(71, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:57:03'),
(72, 1, 'order_status_updated', 'تم تحديث حالة الطلب #25 إلى confirmed', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:07:24'),
(73, 1, 'order_status_updated', 'تم تحديث حالة الطلب #29 إلى confirmed', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:08:55'),
(74, 1, 'order_status_updated', 'تم تحديث حالة الطلب #29 إلى shipped', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:09:27'),
(75, 1, 'admin_note_added', 'تم إضافة ملاحظة إدارية للطلب #29', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:09:59'),
(76, 1, 'order_status_updated', 'تم تحديث حالة الطلب #29 إلى delivered', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:11:13'),
(77, 1, 'order_status_updated', 'تم تحديث حالة الطلب #30 إلى cancelled', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:18:49'),
(78, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:21:11'),
(79, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:21:58'),
(80, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:22:36'),
(81, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:26:21'),
(82, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:26:25'),
(83, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:26:28'),
(84, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:26:47'),
(85, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:27:51'),
(86, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:28:31'),
(87, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:28:47'),
(88, 1, 'category_created', 'تم إضافة الفئة بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:29:09'),
(89, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:30:05'),
(90, 1, 'order_status_updated', 'تم تحديث حالة الطلب #31 إلى confirmed', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:32:02'),
(91, 1, 'order_status_updated', 'تم تحديث حالة الطلب #31 إلى processing', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:32:29'),
(92, 1, 'order_status_updated', 'تم تحديث حالة الطلب #31 إلى shipped', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:32:47'),
(93, 1, 'payment_status_updated', 'تم تحديث حالة الدفع للطلب #31 إلى paid', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:33:06'),
(94, 1, 'order_status_updated', 'تم تحديث حالة الطلب #31 إلى delivered', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:33:17'),
(95, 1, 'coupon_created', 'تم إضافة الكوبون بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:34:49'),
(96, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:36:08'),
(97, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:36:44'),
(98, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:37:00'),
(99, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:37:28'),
(100, 1, 'logout', 'تسجيل خروج', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:39:06'),
(101, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 19:50:08'),
(102, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:02:45'),
(103, 1, 'review_approved', 'تمت الموافقة على التقييم #1', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:02:55'),
(104, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:22:31'),
(105, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:23:27'),
(106, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:35:03'),
(107, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:36:12'),
(108, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:43:52'),
(109, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 02:45:53'),
(110, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-13 02:47:21'),
(111, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-13 02:48:27'),
(112, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:00:53'),
(113, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:04:40'),
(114, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:11:04'),
(115, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:19:36'),
(116, 1, 'product_created', 'تم إضافة المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:39:01'),
(117, 1, 'review_approved', 'تمت الموافقة على التقييم #2', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:40:29'),
(118, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:40:57'),
(119, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:41:05'),
(120, 1, 'product_deleted', 'تم تعطيل المنتج لأنه مرتبط بطلبات سابقة', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:41:12'),
(121, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:41:17'),
(122, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:41:22'),
(123, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:41:28'),
(124, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:41:34'),
(125, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 03:41:38'),
(126, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 19:05:02'),
(127, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 19:05:51'),
(128, 1, 'product_updated', 'تم تحديث المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 19:07:50'),
(129, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 18:19:50'),
(130, 1, 'product_deleted', 'تم حذف المنتج بنجاح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 18:19:57'),
(131, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 01:21:01'),
(132, 1, 'login', 'تسجيل دخول ناجح', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:44:52'),
(133, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:08:22'),
(134, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:09:24'),
(135, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:28:49'),
(136, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:31:54'),
(137, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:42:11'),
(138, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:47:42'),
(139, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:47:53'),
(140, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:50:52'),
(141, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:51:08'),
(142, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:52:02'),
(143, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:59:01'),
(144, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:59:18'),
(145, 1, 'shipping_rate_deleted', 'تم حذف منطقة شحن (ID: 5)', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:59:27'),
(146, 1, 'settings_updated', 'تم تحديث إعدادات المتجر', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 02:59:27'),
(147, 1, 'scratch_card_activity', 'تم إنشاء 1 كارت خربشة', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 19:14:32'),
(148, 1, 'scratch_card_activity', 'تم إنشاء 1 كارت خربشة', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:04:28');

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
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `role`, `last_login`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$12$FY5aE7RgklbhcxpsBGkYeexwC019FGErHOMurrQ3qhlld9SS5IGXe', 'eh.m.a@hotmail.com', 'super_admin', '2025-10-28 19:26:18', 1, '2025-10-02 19:15:24');

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
(5, 'جمال وعناية', 'beauty', 'منتجات التجميل والعناية الشخصية', NULL, NULL, 5, 1, '2025-10-02 19:15:24'),
(7, 'الطعام', 'الطعام', '', NULL, NULL, 0, 1, '2025-10-09 14:29:09');

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

--
-- إرجاع أو استيراد بيانات الجدول `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `usage_limit`, `usage_count`, `valid_from`, `valid_until`, `is_active`, `created_at`) VALUES
(1, 'WELCOME100', 'خصم 10% للعملاء الجدد', 'percentage', 10.00, 200.00, NULL, 100, 0, NULL, '2025-11-01 20:15:00', 1, '2025-10-02 19:15:24'),
(2, 'SAVE50', 'خصم 50 جنيه على طلبات أكثر من 500 جنيه', 'fixed', 50.00, 500.00, NULL, 50, 2, NULL, '2025-10-17 19:15:24', 1, '2025-10-02 19:15:24'),
(3, 'ٍSave30', '', 'percentage', 30.00, 0.00, NULL, 100, 2, '2025-10-03 18:43:00', '2025-10-08 18:43:00', 1, '2025-10-04 18:44:06'),
(5, 'ٍSav50', '', 'percentage', 50.00, 0.00, NULL, NULL, 0, NULL, NULL, 1, '2025-10-09 14:34:49'),
(6, 'SCR24826746', 'Scratch card discount for customer 1', 'percentage', 100.00, 0.00, NULL, 1, 0, NULL, '2025-11-27 20:27:16', 1, '2025-10-28 19:27:16');

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

--
-- إرجاع أو استيراد بيانات الجدول `customers`
--

INSERT INTO `customers` (`id`, `email`, `phone`, `password`, `first_name`, `last_name`, `is_verified`, `verification_token`, `reset_token`, `reset_token_expire`, `orders_count`, `total_spent`, `last_order_date`, `created_at`, `updated_at`) VALUES
(1, 'eh.m.a@hotmail.com', '01116030797', '$2y$12$6gFXjWfq9vOSIRidgeNxCe8nKzUvIrSvD3TaAZGRSrawNyHvZx5iO', 'Ehab', 'Magdy', 0, NULL, NULL, NULL, 0, 0.00, NULL, '2025-10-03 06:21:41', '2025-10-03 06:21:41');

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

--
-- إرجاع أو استيراد بيانات الجدول `customer_points`
--

INSERT INTO `customer_points` (`id`, `customer_id`, `points`, `total_earned`, `total_spent`, `created_at`, `updated_at`) VALUES
(0, 1, 0, 0, 0, '2025-10-24 04:24:10', '2025-10-24 04:24:10');

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

--
-- إرجاع أو استيراد بيانات الجدول `delivery_agents`
--

INSERT INTO `delivery_agents` (`id`, `name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `salary_type`, `fixed_salary`, `commission_rate`, `area`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'أحمد محمد', '01116030798', 'ahmed@example.com', 'motorcycle', 'م أ 1234', 'mixed', 2000.00, 5.00, 'القاهرة', 1, '2025-10-20 03:42:47', '2025-10-20 03:42:47'),
(3, 'محمود علي', '01116030799', 'mahmoud@example.com', 'car', 'م ب 5678', 'commission', 0.00, 7.00, 'الجيزة', 1, '2025-10-20 03:42:47', '2025-10-20 03:42:47');

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
-- إرجاع أو استيراد بيانات الجدول `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_phone`, `customer_email`, `shipping_address`, `governorate`, `city`, `payment_method`, `payment_status`, `payment_transaction_id`, `subtotal`, `shipping_cost`, `discount_amount`, `tax_amount`, `total`, `status`, `notes`, `admin_notes`, `tracking_number`, `shipped_at`, `delivered_at`, `cancelled_at`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'ORD00000001', 1, 'tyhty', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6، مبنى 3، الطابق 3، شقة 3، علامة مميزة: 3', 'الإسماعيلية', 'Cairo', 'cod', 'pending', NULL, 3402.35, 70.00, 0.00, 0.00, 3472.35, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-02 19:19:38', '2025-10-03 10:12:32'),
(2, 'ORD00000002', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'vodafone_cash', 'pending', NULL, 21723.45, 30.00, 0.00, 0.00, 21753.45, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-02 19:22:12', '2025-10-03 10:12:32'),
(3, 'ORD00000003', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'pending', NULL, 1104.15, 30.00, 0.00, 0.00, 1134.15, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-02 19:23:44', '2025-10-03 09:25:05'),
(4, 'ORD00000004', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'pending', NULL, 8091.00, 30.00, 0.00, 0.00, 8121.00, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-02 19:25:44', '2025-10-03 09:25:05'),
(5, 'ORD00000005', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'البحيرة', 'Cairo', 'cod', 'pending', NULL, 3103.35, 70.00, 0.00, 0.00, 3173.35, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-02 19:27:47', '2025-10-03 09:25:05'),
(6, 'ORD00000006', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'السويس', 'Cairo', 'cod', 'pending', NULL, 8091.00, 70.00, 0.00, 0.00, 8161.00, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-02 19:29:27', '2025-10-03 09:25:05'),
(7, 'ORD00000007', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'الإسماعيلية', 'Cairo', 'cod', 'pending', NULL, 8415.00, 70.00, 0.00, 0.00, 8485.00, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 05:57:11', '2025-10-03 09:25:05'),
(8, 'ORD00000008', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'pending', NULL, 5401.55, 30.00, 0.00, 0.00, 5431.55, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 06:22:43', '2025-10-03 09:25:05'),
(9, 'ORD00000009', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'الشرقية', 'Cairo', 'cod', 'pending', NULL, 4530.50, 70.00, 0.00, 0.00, 4600.50, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 06:33:24', '2025-10-03 09:25:05'),
(10, 'ORD00000010', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'الدقهلية', 'Cairo', 'cod', 'pending', NULL, 4958.45, 70.00, 0.00, 0.00, 5028.45, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 06:41:03', '2025-10-03 09:25:05'),
(11, 'ORD00000011', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'pending', NULL, 4360.45, 30.00, 0.00, 0.00, 4390.45, 'cancelled', NULL, '\nسبب الإلغاء: 51', NULL, NULL, NULL, '2025-10-03 09:17:06', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 09:16:24', '2025-10-03 09:25:05'),
(12, 'ORD00000012', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'pending', NULL, 3252.35, 30.00, 0.00, 0.00, 3282.35, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 09:18:54', '2025-10-03 09:25:05'),
(13, 'ORD00000013', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'instapay', 'pending', NULL, 3912.45, 30.00, 0.00, 0.00, 3942.45, 'cancelled', NULL, NULL, NULL, NULL, NULL, '2025-10-03 16:18:17', '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 11:49:45', '2025-10-03 16:18:17'),
(14, 'ORD-MARY00000014', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'vodafone_cash', 'pending', NULL, 10202.35, 3.00, 50.00, 0.00, 10155.35, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 17:53:04', '2025-10-04 04:12:45'),
(15, 'ORD-MARY00000015', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'pending', NULL, 7904.15, 3.00, 0.00, 0.00, 7907.15, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 17:59:29', '2025-10-04 04:12:45'),
(16, 'ORD-MARY00000016', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'vodafone_cash', 'pending', NULL, 9903.35, 3.00, 0.00, 0.00, 9906.35, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:03:49', '2025-10-04 04:12:45'),
(17, 'ORD-MARY00000017', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'الشرقية', 'Cairo', 'vodafone_cash', 'paid', NULL, 7904.15, 7.00, 0.00, 0.00, 7911.15, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:15:15', '2025-10-04 04:12:45'),
(18, 'ORD-MARY00000018', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'pending', NULL, 11330.50, 3.00, 50.00, 0.00, 11283.50, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:18:52', '2025-10-04 04:12:45'),
(19, 'ORD-MARY00000019', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'visa', 'pending', NULL, 47424.90, 3.00, 0.00, 0.00, 47427.90, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:19:45', '2025-10-04 04:12:45'),
(20, 'ORD-MARY00000020', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'instapay', 'pending', NULL, 7904.15, 3.00, 0.00, 0.00, 7907.15, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:20:39', '2025-10-04 04:12:45'),
(21, 'ORD-MARY00000021', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'vodafone_cash', 'paid', NULL, 10202.35, 3.00, 0.00, 0.00, 10205.35, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:40:59', '2025-10-04 04:12:45'),
(22, 'ORD-MARY00000022', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'instapay', 'pending', NULL, 7904.15, 3.00, 0.00, 0.00, 7907.15, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:45:40', '2025-10-04 04:12:45'),
(23, 'ORD-MARY00000023', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'vodafone_cash', 'pending', NULL, 1999.20, 3.00, 0.00, 0.00, 2002.20, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-03 18:47:58', '2025-10-04 04:12:45'),
(24, 'ORD-MARY00000024', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'fawry', 'paid', NULL, 7904.15, 3.00, 0.00, 0.00, 7907.15, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-04 04:02:54', '2025-10-04 04:12:45'),
(25, 'ORD-MARY00000025', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'الجيزة', 'Cairo', 'cod', 'pending', NULL, 7904.15, 3.00, 0.00, 0.00, 7907.15, 'confirmed', NULL, '', NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 04:49:44', '2025-10-09 14:07:24'),
(26, 'ORD-MARY00000026', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'refunded', '', 11330.50, 3.00, 3399.15, 0.00, 7934.35, 'cancelled', NULL, '', NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 18:49:24', '2025-10-09 13:53:26'),
(27, 'ORD-MARY00000027', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'instapay', 'paid', NULL, 10202.35, 3.00, 3060.71, 0.00, 7144.65, 'shipped', NULL, '', '', '2025-10-09 13:46:52', NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-04 19:03:49', '2025-10-09 13:46:52'),
(28, 'ORD-MARY00000028', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'vodafone_cash', 'paid', '', 1104.15, 3.00, 0.00, 0.00, 1107.15, 'delivered', NULL, '\n2025-10-09 15:48: لا تقلقل الامول تسير على ما يرام', '', '2025-10-09 13:49:50', '2025-10-09 13:50:49', NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 13:47:39', '2025-10-12 22:27:48'),
(29, 'ORD-MARY00000029', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'instapay', 'paid', NULL, 1999.20, 3.00, 0.00, 0.00, 2002.20, 'delivered', NULL, '\n2025-10-09 16:09: 0000000000000000000000000000000000000000000000000...', '', '2025-10-09 14:09:27', '2025-10-09 14:11:13', NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:08:31', '2025-10-12 22:27:48'),
(30, 'ORD-MARY00000030', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'visa', 'paid', NULL, 1104.15, 3.00, 0.00, 0.00, 1107.15, 'cancelled', NULL, '', NULL, NULL, NULL, NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:12:48', '2025-10-12 22:27:48'),
(31, 'ORD-MARY00000031', 1, 'Ehab Magdy', '01116030797', 'eh.m.a@hotmail.com', 'Egypt,cairo, المعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', 'القاهرة', 'Cairo', 'cod', 'paid', '', 6300.00, 3.00, 0.00, 0.00, 6303.00, 'delivered', NULL, '', '', '2025-10-09 14:32:47', '2025-10-09 14:33:17', NULL, '192.168.0.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-09 14:30:53', '2025-10-12 22:27:48');

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

--
-- إرجاع أو استيراد بيانات الجدول `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_title`, `product_sku`, `qty`, `unit_price`, `discount_amount`, `total_price`, `created_at`) VALUES
(1, 1, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-02 19:19:38'),
(2, 1, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-02 19:19:38'),
(3, 1, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-02 19:19:38'),
(4, 2, 1, 'سماعات لاسلكية عالية الجودة', NULL, 7, 1104.15, 0.00, 7729.05, '2025-10-02 19:22:12'),
(5, 2, 2, 'ساعة ذكية رياضية', NULL, 7, 1999.20, 0.00, 13994.40, '2025-10-02 19:22:12'),
(6, 3, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-02 19:23:44'),
(7, 4, 4, 'طقم أواني طهي غير لاصقة', NULL, 10, 809.10, 0.00, 8091.00, '2025-10-02 19:25:44'),
(8, 5, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-02 19:27:47'),
(9, 5, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-02 19:27:47'),
(10, 6, 4, 'طقم أواني طهي غير لاصقة', NULL, 10, 809.10, 0.00, 8091.00, '2025-10-02 19:29:27'),
(11, 7, 1, 'سماعات لاسلكية عالية الجودة', NULL, 4, 1104.15, 0.00, 4416.60, '2025-10-03 05:57:11'),
(12, 7, 2, 'ساعة ذكية رياضية', NULL, 2, 1999.20, 0.00, 3998.40, '2025-10-03 05:57:11'),
(13, 8, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-03 06:22:43'),
(14, 8, 2, 'ساعة ذكية رياضية', NULL, 2, 1999.20, 0.00, 3998.40, '2025-10-03 06:22:43'),
(15, 8, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-03 06:22:43'),
(16, 9, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-03 06:33:24'),
(17, 9, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 06:33:24'),
(18, 9, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-03 06:33:24'),
(19, 9, 6, 'كريم واقي من الشمس SPF 50', NULL, 1, 170.05, 0.00, 170.05, '2025-10-03 06:33:24'),
(20, 9, 5, 'حبل قفز رياضي احترافي', NULL, 1, 149.00, 0.00, 149.00, '2025-10-03 06:33:24'),
(21, 9, 4, 'طقم أواني طهي غير لاصقة', NULL, 1, 809.10, 0.00, 809.10, '2025-10-03 06:33:24'),
(22, 10, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-03 06:41:03'),
(23, 10, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 06:41:03'),
(24, 10, 3, 'قميص قطني رجالي', NULL, 3, 299.00, 0.00, 897.00, '2025-10-03 06:41:03'),
(25, 10, 4, 'طقم أواني طهي غير لاصقة', NULL, 1, 809.10, 0.00, 809.10, '2025-10-03 06:41:03'),
(26, 10, 5, 'حبل قفز رياضي احترافي', NULL, 1, 149.00, 0.00, 149.00, '2025-10-03 06:41:03'),
(27, 11, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-03 09:16:24'),
(28, 11, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 09:16:24'),
(29, 11, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-03 09:16:24'),
(30, 11, 4, 'طقم أواني طهي غير لاصقة', NULL, 1, 809.10, 0.00, 809.10, '2025-10-03 09:16:24'),
(31, 11, 5, 'حبل قفز رياضي احترافي', NULL, 1, 149.00, 0.00, 149.00, '2025-10-03 09:16:24'),
(32, 12, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-03 09:18:54'),
(33, 12, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 09:18:54'),
(34, 12, 5, 'حبل قفز رياضي احترافي', NULL, 1, 149.00, 0.00, 149.00, '2025-10-03 09:18:54'),
(35, 13, 4, 'طقم أواني طهي غير لاصقة', NULL, 1, 809.10, 0.00, 809.10, '2025-10-03 11:49:46'),
(36, 13, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-03 11:49:46'),
(37, 13, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 11:49:46'),
(38, 14, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 17:53:04'),
(39, 14, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 17:53:04'),
(40, 14, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-03 17:53:04'),
(41, 15, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 17:59:29'),
(42, 16, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 18:03:49'),
(43, 16, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 18:03:49'),
(44, 17, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 18:15:15'),
(45, 18, 5, 'حبل قفز رياضي احترافي', NULL, 1, 149.00, 0.00, 149.00, '2025-10-03 18:18:52'),
(46, 18, 6, 'كريم واقي من الشمس SPF 50', NULL, 1, 170.05, 0.00, 170.05, '2025-10-03 18:18:52'),
(47, 18, 4, 'طقم أواني طهي غير لاصقة', NULL, 1, 809.10, 0.00, 809.10, '2025-10-03 18:18:52'),
(48, 18, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 18:18:52'),
(49, 18, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 18:18:52'),
(50, 18, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-03 18:18:52'),
(51, 19, 1, 'سماعات لاسلكية عالية الجودة', NULL, 6, 7904.15, 0.00, 47424.90, '2025-10-03 18:19:45'),
(52, 20, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 18:20:39'),
(53, 21, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 18:40:59'),
(54, 21, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 18:40:59'),
(55, 21, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-03 18:40:59'),
(56, 22, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-03 18:45:40'),
(57, 23, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-03 18:47:58'),
(58, 24, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-04 04:02:55'),
(59, 25, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-04 04:49:44'),
(60, 26, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-04 18:49:24'),
(61, 26, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-04 18:49:24'),
(62, 26, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-04 18:49:24'),
(63, 26, 4, 'طقم أواني طهي غير لاصقة', NULL, 1, 809.10, 0.00, 809.10, '2025-10-04 18:49:24'),
(64, 26, 5, 'حبل قفز رياضي احترافي', NULL, 1, 149.00, 0.00, 149.00, '2025-10-04 18:49:24'),
(65, 26, 6, 'كريم واقي من الشمس SPF 50', NULL, 1, 170.05, 0.00, 170.05, '2025-10-04 18:49:24'),
(66, 27, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 7904.15, 0.00, 7904.15, '2025-10-04 19:03:49'),
(67, 27, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-04 19:03:49'),
(68, 27, 3, 'قميص قطني رجالي', NULL, 1, 299.00, 0.00, 299.00, '2025-10-04 19:03:49'),
(69, 28, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-09 13:47:39'),
(70, 29, 2, 'ساعة ذكية رياضية', NULL, 1, 1999.20, 0.00, 1999.20, '2025-10-09 14:08:31'),
(71, 30, 1, 'سماعات لاسلكية عالية الجودة', NULL, 1, 1104.15, 0.00, 1104.15, '2025-10-09 14:12:48'),
(72, 31, 12, 'طعام فريد من نوعه', NULL, 1, 6300.00, 0.00, 6300.00, '2025-10-09 14:30:53');

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

--
-- إرجاع أو استيراد بيانات الجدول `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `comment`, `created_by`, `created_at`) VALUES
(1, 11, 'pending', 'cancelled', 'تم تحديث الحالة من pending إلى cancelled', NULL, '2025-10-03 09:17:06'),
(2, 11, 'pending', 'cancelled', 'تم إلغاء الطلب: 51', 1, '2025-10-03 09:17:06'),
(3, 13, 'pending', 'cancelled', 'تم تحديث الحالة من pending إلى cancelled', NULL, '2025-10-03 16:18:17'),
(4, 27, 'pending', 'confirmed', 'تم تحديث الحالة من pending إلى confirmed', NULL, '2025-10-09 13:46:25'),
(5, 27, 'pending', 'confirmed', '', 1, '2025-10-09 13:46:25'),
(6, 27, 'confirmed', 'shipped', 'تم تحديث الحالة من confirmed إلى shipped', NULL, '2025-10-09 13:46:52'),
(7, 27, 'confirmed', 'shipped', '', 1, '2025-10-09 13:46:52'),
(8, 28, 'pending', 'confirmed', 'تم تحديث الحالة من pending إلى confirmed', NULL, '2025-10-09 13:48:53'),
(9, 28, 'pending', 'confirmed', 'لا تقلقل الامول تسير على ما يرام', 1, '2025-10-09 13:48:53'),
(10, 28, 'confirmed', 'shipped', 'تم تحديث الحالة من confirmed إلى shipped', NULL, '2025-10-09 13:49:50'),
(11, 28, 'confirmed', 'shipped', '', 1, '2025-10-09 13:49:50'),
(12, 28, 'shipped', 'delivered', 'تم تحديث الحالة من shipped إلى delivered', NULL, '2025-10-09 13:50:49'),
(13, 28, 'shipped', 'delivered', '', 1, '2025-10-09 13:50:49'),
(14, 26, 'pending', 'cancelled', 'تم تحديث الحالة من pending إلى cancelled', NULL, '2025-10-09 13:53:26'),
(15, 26, 'pending', 'cancelled', '', 1, '2025-10-09 13:53:26'),
(16, 25, 'pending', 'confirmed', 'تم تحديث الحالة من pending إلى confirmed', NULL, '2025-10-09 14:07:24'),
(17, 25, 'pending', 'confirmed', '', 1, '2025-10-09 14:07:24'),
(18, 29, 'pending', 'confirmed', 'تم تحديث الحالة من pending إلى confirmed', NULL, '2025-10-09 14:08:55'),
(19, 29, 'pending', 'confirmed', '', 1, '2025-10-09 14:08:55'),
(20, 29, 'confirmed', 'shipped', 'تم تحديث الحالة من confirmed إلى shipped', NULL, '2025-10-09 14:09:27'),
(21, 29, 'confirmed', 'shipped', '', 1, '2025-10-09 14:09:27'),
(22, 29, 'shipped', 'delivered', 'تم تحديث الحالة من shipped إلى delivered', NULL, '2025-10-09 14:11:13'),
(23, 29, 'shipped', 'delivered', '', 1, '2025-10-09 14:11:13'),
(24, 30, 'pending', 'cancelled', 'تم تحديث الحالة من pending إلى cancelled', NULL, '2025-10-09 14:18:49'),
(25, 30, 'pending', 'cancelled', '', 1, '2025-10-09 14:18:49'),
(26, 31, 'pending', 'confirmed', 'تم تحديث الحالة من pending إلى confirmed', NULL, '2025-10-09 14:32:02'),
(27, 31, 'pending', 'confirmed', '', 1, '2025-10-09 14:32:02'),
(28, 31, 'confirmed', 'processing', 'تم تحديث الحالة من confirmed إلى processing', NULL, '2025-10-09 14:32:29'),
(29, 31, 'confirmed', 'processing', '', 1, '2025-10-09 14:32:29'),
(30, 31, 'processing', 'shipped', 'تم تحديث الحالة من processing إلى shipped', NULL, '2025-10-09 14:32:47'),
(31, 31, 'processing', 'shipped', '', 1, '2025-10-09 14:32:47'),
(32, 31, 'shipped', 'delivered', 'تم تحديث الحالة من shipped إلى delivered', NULL, '2025-10-09 14:33:17'),
(33, 31, 'shipped', 'delivered', '', 1, '2025-10-09 14:33:17');

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

--
-- إرجاع أو استيراد بيانات الجدول `package_orders`
--

INSERT INTO `package_orders` (`id`, `order_number`, `customer_id`, `package_id`, `points_amount`, `price`, `payment_method`, `payment_status`, `status`, `points_added`, `created_at`, `updated_at`) VALUES
(1, 'PKG202510242391', 1, 4, 70000, 5000.00, 'cod', 'pending', 'pending', 0, '2025-10-24 05:20:17', '2025-10-24 05:20:17');

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

--
-- إرجاع أو استيراد بيانات الجدول `partners`
--

INSERT INTO `partners` (`id`, `name`, `email`, `phone`, `company`, `partnership_type`, `investment_amount`, `profit_share`, `start_date`, `end_date`, `responsibilities`, `contact_person`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ehab Magdy', 'eh.m.a@hotmail.com', '01116030797', 'None', 'investor', 1210.00, 10.00, '0000-00-00', '0000-00-00', '', 'Ehab Magdy', '', 'active', '2025-10-20 03:58:41', '2025-10-20 03:58:41');

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

--
-- إرجاع أو استيراد بيانات الجدول `price_countdowns`
--

INSERT INTO `price_countdowns` (`id`, `product_id`, `new_price`, `countdown_end`, `is_active`, `created_at`) VALUES
(3, 5, 50.00, '2025-10-27 19:32:00', 1, '2025-10-26 19:32:29'),
(4, 2, 600.00, '2025-10-28 19:32:00', 1, '2025-10-26 19:32:45'),
(5, 3, 30.00, '2025-10-26 19:36:00', 1, '2025-10-26 19:35:05'),
(6, 5, 100.00, '2025-10-27 20:00:00', 1, '2025-10-26 20:00:22'),
(7, 1, 300.00, '2025-10-30 18:00:00', 1, '2025-10-28 18:00:52');

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
  `bid_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `products`
--

INSERT INTO `products` (`id`, `category_id`, `title`, `slug`, `description`, `short_description`, `price`, `discount_percentage`, `discount_amount`, `stock`, `sku`, `weight`, `dimensions`, `main_image`, `views`, `orders_count`, `rating_avg`, `rating_count`, `is_featured`, `is_active`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_condition`, `special_offer_type`, `special_offer_value`, `auction_enabled`, `auction_end_time`, `starting_price`, `current_bid`, `bid_count`) VALUES
(1, 1, 'سماعات لاسلكية', 'سماعات-لاسلكية', 'سماعات بلوتوث مع خاصية إلغاء الضوضاء وبطارية تدوم 30 ساعة. صوت نقي وتصميم مريح للاستخدام الطويل.', 'سماعات بلوتوث احترافية مع إلغاء الضوضاء', 1299.00, 15.00, 0.00, 12, 'WH-PRO-001', NULL, '', 'assets\\images\\1.jpg', 73, 39, 3.00, 1, 1, 1, '', '', '2025-10-02 19:15:24', '2025-10-28 21:56:31', 'used', 'points', '100', 1, '2025-10-28 19:59:00', 1000.00, 1501.00, 1),
(2, 1, 'ساعة ذكية رياضية', 'smartwatch-sport', 'ساعة ذكية متطورة لتتبع اللياقة البدنية مع شاشة AMOLED ومقاومة للماء حتى 50 متر', 'ساعة ذكية للرياضة وتتبع الصحة', 2499.00, 20.00, 0.00, 6, 'SW-SPORT-001', NULL, NULL, 'assets\\images\\2.jpg', 26, 25, 0.00, 0, 1, 1, NULL, NULL, '2025-10-02 19:15:24', '2025-10-28 17:45:42', 'new', 'none', NULL, 0, NULL, 0.00, 0.00, 0),
(3, 2, 'قميص قطني رجالي', 'mens-cotton-shirt', 'قميص قطني 100% بألوان متعددة ومقاسات من S إلى XXL. مناسب للارتداء اليومي', 'قميص قطني مريح وعصري', 299.00, 0.00, 0.00, 89, 'SHIRT-M-001', NULL, NULL, '	\nassets\\images\\3.jpg', 11, 12, 0.00, 0, 0, 1, NULL, NULL, '2025-10-02 19:15:24', '2025-10-24 05:04:25', 'new', 'none', NULL, 0, NULL, 0.00, 0.00, 0),
(4, 3, 'طقم أواني طهي غير لاصقة', 'cookware-set-nonstick', 'طقم من 7 قطع بطبقة غير لاصقة ومقابض مقاومة للحرارة. آمن للاستخدام اليومي', 'طقم أواني طهي 7 قطع', 899.00, 10.00, 0.00, 1, 'COOK-SET-001', NULL, NULL, 'assets\\images\\4.jpg', 17, 25, 0.00, 0, 0, 1, NULL, NULL, '2025-10-02 19:15:24', '2025-10-28 20:07:40', 'new', 'coupon', '100', 0, NULL, 0.00, 0.00, 0),
(5, 4, 'حبل قفز رياضي احترافي', 'jump-rope-pro', 'حبل قفز قابل للتعديل مع عداد رقمي ومقابض مريحة. مثالي للياقة البدنية', 'حبل قفز مع عداد رقمي', 149.00, 0.00, 0.00, 70, 'ROPE-001', NULL, NULL, 'assets\\images\\5.jpg', 7, 6, 0.00, 0, 0, 1, NULL, NULL, '2025-10-02 19:15:24', '2025-10-28 19:26:43', 'used', 'points', '', 1, NULL, 0.00, 1000.00, 0),
(6, 5, 'كريم واقي من الشمس SPF 50', 'sunscreen-spf50', 'كريم حماية من أشعة الشمس بعامل حماية 50 ومقاوم للماء لمدة 80 دقيقة', 'واقي شمس للحماية الكاملة', 179.00, 5.00, 0.00, 57, 'SUN-001', NULL, NULL, 'assets\\images\\6.jpg', 3, 3, 0.00, 0, 0, 1, NULL, NULL, '2025-10-02 19:15:24', '2025-10-28 21:39:12', 'refurbished', 'none', '', 1, '2025-10-29 21:22:00', 100.00, 300000.00, 4),
(12, 7, 'طعام فريد من نوعه', 'طعام-فريد-من-نوعه', '', '', 9000.00, 30.00, 0.00, 4, '', 10.00, '3x2', NULL, 35, 1, 0.00, 0, 0, 0, '', '', '2025-10-09 14:30:05', '2025-10-13 03:41:12', 'new', 'none', NULL, 0, NULL, 0.00, 0.00, 0);

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

--
-- إرجاع أو استيراد بيانات الجدول `product_bids`
--

INSERT INTO `product_bids` (`id`, `product_id`, `customer_id`, `bid_amount`, `bid_time`, `is_winning`) VALUES
(1, 5, 1, 1000.00, '2025-10-26 19:59:48', 0),
(2, 1, 1, 1500.00, '2025-10-26 20:01:47', 0),
(3, 1, 1, 1501.00, '2025-10-28 21:18:05', 0),
(4, 5, 1, 1000.00, '2025-10-28 21:21:10', 0),
(5, 6, 1, 20202.00, '2025-10-28 21:21:51', 0),
(6, 6, 1, 20203.00, '2025-10-28 21:34:26', 0),
(7, 6, 1, 20204.00, '2025-10-28 21:38:32', 0),
(8, 6, 1, 20205.00, '2025-10-28 21:38:45', 0),
(9, 6, 1, 300000.00, '2025-10-28 21:39:12', 0);

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

--
-- إرجاع أو استيراد بيانات الجدول `product_negotiations`
--

INSERT INTO `product_negotiations` (`id`, `product_id`, `customer_id`, `offered_price`, `status`, `admin_notes`, `counter_price`, `customer_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 780.00, 'accepted', '', 0.00, '5151', '2025-10-24 08:22:41', '2025-10-26 18:58:45'),
(2, 4, 1, 567.00, 'accepted', '', 0.00, '5151', '2025-10-24 08:23:57', '2025-10-26 18:27:49'),
(3, 2, 1, 1401.00, 'accepted', '', 0.00, '', '2025-10-24 08:52:33', '2025-10-26 17:52:29'),
(4, 1, 1, 780.00, 'accepted', '', 0.00, '', '2025-10-26 18:59:22', '2025-10-26 18:59:49'),
(5, 2, 1, 1509.00, 'counter_offer', '', 1500.00, '', '2025-10-26 19:31:11', '2025-10-26 19:31:32'),
(6, 1, 1, 790.00, 'accepted', '', 0.00, '', '2025-10-26 19:58:44', '2025-10-26 19:59:17');

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

--
-- إرجاع أو استيراد بيانات الجدول `referral_links`
--

INSERT INTO `referral_links` (`id`, `customer_id`, `referral_code`, `referral_url`, `clicks`, `signups`, `completed_orders`, `total_earned_points`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'MB1XENWM', 'https://192.168.0.107:8012/register.php?ref=MB1XENWM', 0, 0, 0, 0, 1, '2025-10-24 07:22:28', '2025-10-24 07:22:28');

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

--
-- إرجاع أو استيراد بيانات الجدول `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `customer_id`, `first_name`, `email`, `order_id`, `rating`, `title`, `comment`, `is_verified_purchase`, `is_approved`, `admin_reply`, `helpful_count`, `helpful_votes`, `total_votes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Ehab', 'eh.m.a@hotmail.com', NULL, 3, 'ممتاز', 'ممتاز  جدا', 0, 1, NULL, 0, 0, 0, '2025-10-12 22:38:58', '2025-10-13 02:02:55');

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

--
-- إرجاع أو استيراد بيانات الجدول `scratch_cards`
--

INSERT INTO `scratch_cards` (`id`, `customer_id`, `product_id`, `card_code`, `reward_type`, `reward_value`, `reward_description`, `is_scratched`, `scratched_at`, `is_claimed`, `claimed_at`, `expires_at`, `created_at`) VALUES
(1, 1, 5, 'SCR00ABDCEBF21761678872', 'discount', 100.00, '', 1, '2025-10-28 19:27:11', 1, '2025-10-28 19:27:16', '2025-10-29 19:14:00', '2025-10-28 19:14:32'),
(2, 1, 4, 'SCRDA694884751761681868', 'gift', 2000.00, '', 0, NULL, 0, NULL, '2025-10-30 21:04:00', '2025-10-28 20:04:28');

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

--
-- إرجاع أو استيراد بيانات الجدول `wholesalers`
--

INSERT INTO `wholesalers` (`id`, `name`, `email`, `phone`, `company`, `tax_number`, `address`, `specialty`, `discount_rate`, `credit_limit`, `current_balance`, `payment_terms`, `contact_person`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ehab Magdy', 'eh.m.a@hotmail.com', '01116030797', 'None', '212', 'Egypt,cairo\r\nالمعادي - اسكانات العرائس -عمارة 21 -مدخل 2 - شقة 6', '22', 10.00, 21.00, 0.00, '232', 'Ehab Magdy', '32', 'active', '2025-10-20 03:53:17', '2025-10-20 03:53:17');

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

--
-- إرجاع أو استيراد بيانات الجدول `wishlists`
--

INSERT INTO `wishlists` (`id`, `customer_id`, `product_id`, `created_at`) VALUES
(45, 1, 4, '2025-10-24 08:24:10'),
(50, 1, 2, '2025-10-28 18:53:48'),
(51, 1, 1, '2025-10-28 21:40:53');

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
  ADD KEY `idx_active_featured` (`is_active`,`is_featured`);
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_agents`
--
ALTER TABLE `delivery_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `price_countdowns`
--
ALTER TABLE `price_countdowns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_bids`
--
ALTER TABLE `product_bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wholesaler_products`
--
ALTER TABLE `wholesaler_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

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
