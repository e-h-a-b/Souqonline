-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 27 نوفمبر 2025 الساعة 18:32
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

-- --------------------------------------------------------

--
-- بنية الجدول `product_media`
--

CREATE TABLE `product_media` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `media_type` enum('image','video','3d_model','gif') NOT NULL,
  `media_url` varchar(500) NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_main` tinyint(1) DEFAULT 0,
  `autoplay` tinyint(1) DEFAULT 0,
  `loop` tinyint(1) DEFAULT 1,
  `controls` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `product_media`
--

INSERT INTO `product_media` (`id`, `product_id`, `media_type`, `media_url`, `thumbnail_url`, `display_order`, `is_main`, `autoplay`, `loop`, `controls`, `created_at`) VALUES
(1, 1, '3d_model', 'assets/3d-models/Astronaut.glb', 'assets/images/3d-thumb-1.jpg', 1, 1, 0, 1, 1, '2025-11-25 05:26:43'),
(2, 2, 'video', 'assets/videos/dell-xps-demo.mp4', 'assets/images/video-thumb-2.jpg', 1, 1, 0, 1, 1, '2025-11-25 05:26:43'),
(3, 2, 'image', 'assets/images/products/dell-xps-1.jpg', 'assets/images/products/thumb-dell-1.jpg', 2, 0, 0, 0, 0, '2025-11-25 05:26:43'),
(4, 2, 'image', 'assets/images/products/dell-xps-2.jpg', 'assets/images/products/thumb-dell-2.jpg', 3, 0, 0, 0, 0, '2025-11-25 05:26:43'),
(5, 3, 'gif', 'assets/gifs/airpods-demo.gif', 'assets/images/gif-thumb-3.jpg', 1, 1, 1, 1, 0, '2025-11-25 05:26:43'),
(6, 4, 'image', 'assets/images/products/tshirt-1.jpg', 'assets/images/products/thumb-tshirt-1.jpg', 1, 1, 0, 0, 0, '2025-11-25 05:26:43'),
(7, 4, 'image', 'assets/images/products/tshirt-2.jpg', 'assets/images/products/thumb-tshirt-2.jpg', 2, 0, 0, 0, 0, '2025-11-25 05:26:43'),
(8, 4, 'image', 'assets/images/products/tshirt-3.jpg', 'assets/images/products/thumb-tshirt-3.jpg', 3, 0, 0, 0, 0, '2025-11-25 05:26:43'),
(9, 11, '3d_model', 'https://modelviewer.dev/shared-assets/models/Chair.glb', 'assets/images/3d-thumb-11.jpg', 1, 1, 0, 1, 1, '2025-11-25 05:26:43'),
(10, 11, 'image', 'assets/images/products/new-product-1.jpg', 'assets/images/products/thumb-new-1.jpg', 2, 0, 0, 0, 0, '2025-11-25 05:26:43'),
(11, 15, '3d_model', 'https://modelviewer.dev/shared-assets/models/shoe.glb', 'assets/images/3d-thumb-15.jpg', 1, 1, 0, 1, 1, '2025-11-25 05:26:43'),
(12, 15, 'video', 'assets/videos/product-15-demo.mp4', 'assets/images/video-thumb-15.jpg', 2, 0, 0, 1, 1, '2025-11-25 05:26:43'),
(13, 15, 'image', 'assets/images/products/product15-1.jpg', 'assets/images/products/thumb15-1.jpg', 3, 0, 0, 0, 0, '2025-11-25 05:26:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `product_media`
--
ALTER TABLE `product_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `product_media`
--
ALTER TABLE `product_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `product_media`
--
ALTER TABLE `product_media`
  ADD CONSTRAINT `product_media_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
