-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Nov 09, 2025 at 07:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clothing`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','inventory_manager','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(7, 'admin', 'admin@clothingstore.com', 'admin123', 'super_admin', 1, '2025-11-10 00:28:49', '2025-10-27 09:04:20', '2025-11-09 17:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(3, 7, 'login', NULL, '::1', '2025-10-27 09:04:50'),
(4, 7, 'logout', NULL, '::1', '2025-10-27 09:05:14'),
(5, 7, 'login', NULL, '::1', '2025-10-27 09:05:27'),
(6, 7, 'update_product', 'Updated product: Men Classic T-Shirt', '::1', '2025-10-27 09:41:24'),
(7, 7, 'delete_image', 'Deleted product image ID: 1', '::1', '2025-10-27 09:41:32'),
(8, 7, 'login', NULL, '::1', '2025-10-27 18:21:42'),
(9, 7, 'add_category', 'Added new category: Testing', '::1', '2025-10-27 18:42:53'),
(10, 7, 'update_category', 'Updated category: Sales', '::1', '2025-10-27 18:52:10'),
(11, 7, 'delete_category', 'Deleted category ID: 11', '::1', '2025-10-27 18:52:16'),
(12, 7, 'delete_category', 'Deleted category ID: 11', '::1', '2025-10-27 18:52:25'),
(13, 7, 'add_category', 'Added new category: Tesi', '::1', '2025-10-27 18:52:25'),
(14, 7, 'delete_category', 'Deleted category ID: 11', '::1', '2025-10-27 18:52:32'),
(15, 7, 'update_category', 'Updated category: Testing', '::1', '2025-10-27 18:52:32'),
(16, 7, 'update_order_status', 'Updated order #11 to delivered', '::1', '2025-10-27 19:44:51'),
(17, 7, 'update_coupon', 'Updated coupon ID: 3', '::1', '2025-10-27 20:05:16'),
(18, 7, 'create_coupon', 'Created coupon: 20CCF7', '::1', '2025-10-27 20:06:02'),
(19, 7, 'update_coupon', 'Updated coupon ID: 3', '::1', '2025-10-27 20:06:15'),
(20, 7, 'delete_coupon', 'Deleted coupon: FLAT500', '::1', '2025-10-27 20:06:21'),
(21, 7, 'login', NULL, '::1', '2025-10-28 03:11:30'),
(22, 7, 'update_product', 'Updated product: Women Floral Dress', '::1', '2025-10-28 03:30:11'),
(23, 7, 'delete_image', 'Deleted product image ID: 2', '::1', '2025-10-28 03:30:15'),
(24, 7, 'update_product', 'Updated product: Kids Hoodie', '::1', '2025-10-28 03:30:56'),
(25, 7, 'delete_image', 'Deleted product image ID: 3', '::1', '2025-10-28 03:31:00'),
(26, 7, 'update_product', 'Updated product: Leather Belt', '::1', '2025-10-28 03:31:38'),
(27, 7, 'delete_image', 'Deleted product image ID: 4', '::1', '2025-10-28 03:31:41'),
(28, 7, 'update_product', 'Updated product: Running Shoes', '::1', '2025-10-28 03:32:26'),
(29, 7, 'delete_image', 'Deleted product image ID: 5', '::1', '2025-10-28 03:32:31'),
(30, 7, 'delete_image', 'Deleted product image ID: 6', '::1', '2025-10-28 03:33:11'),
(31, 7, 'update_product', 'Updated product: Women Handbag', '::1', '2025-10-28 03:33:24'),
(32, 7, 'update_product', 'Updated product: Men Jeans', '::1', '2025-10-28 03:35:27'),
(33, 7, 'delete_image', 'Deleted product image ID: 7', '::1', '2025-10-28 03:35:31'),
(34, 7, 'update_product', 'Updated product: Puffer Jacket', '::1', '2025-10-28 03:35:51'),
(35, 7, 'delete_image', 'Deleted product image ID: 8', '::1', '2025-10-28 03:35:54'),
(36, 7, 'delete_image', 'Deleted product image ID: 9', '::1', '2025-10-28 03:36:32'),
(37, 7, 'update_product', 'Updated product: Sports Shorts', '::1', '2025-10-28 03:36:38'),
(38, 7, 'update_product', 'Updated product: Socks Pack', '::1', '2025-10-28 03:37:14'),
(39, 7, 'delete_image', 'Deleted product image ID: 10', '::1', '2025-10-28 03:37:17'),
(40, 7, 'update_order_status', 'Updated order #12 to delivered', '::1', '2025-10-28 04:04:55'),
(41, 7, 'login', NULL, '::1', '2025-11-02 18:36:03'),
(42, 7, 'delete_user', 'Hard-deleted user: john.doe@example.com', '::1', '2025-11-02 18:43:13'),
(43, 7, 'delete_category', 'Deleted category ID: 12', '::1', '2025-11-03 07:35:09'),
(44, 7, 'delete_category', 'Deleted category ID: 12', '::1', '2025-11-03 07:35:12'),
(45, 7, 'add_product', 'Product: Ghost 2', '::1', '2025-11-03 08:13:25'),
(46, 7, 'update_product', 'Updated product: Ghost 2', '::1', '2025-11-03 08:16:47'),
(47, 7, 'reset_user_password', 'Reset password for user ID: 11', '::1', '2025-11-03 08:18:20'),
(48, 7, 'update_user', 'Updated user: soegyi@gmail.com', '::1', '2025-11-03 08:18:38'),
(49, 7, 'update_user', 'Updated user: soegyi@gmail.com', '::1', '2025-11-03 08:18:41'),
(50, 7, 'update_user', 'Updated user: soegyi@gmail.com', '::1', '2025-11-03 08:49:42'),
(51, 7, 'login', NULL, '::1', '2025-11-03 09:55:34'),
(52, 7, 'login', NULL, '::1', '2025-11-03 12:20:42'),
(53, 7, 'login', NULL, '::1', '2025-11-03 17:06:52'),
(54, 7, 'login', NULL, '::1', '2025-11-04 15:36:56'),
(55, 7, 'delete_variant', 'Deleted variant ID: 12', '::1', '2025-11-04 15:37:45'),
(56, 7, 'delete_variant', 'Deleted variant ID: 5', '::1', '2025-11-04 15:38:18'),
(57, 7, 'delete_variant', 'Deleted variant ID: 5', '::1', '2025-11-04 15:39:01'),
(58, 7, 'update_product', 'Updated product: Running Shoes', '::1', '2025-11-04 15:54:50'),
(59, 7, 'update_product', 'Updated product: Running Shoes', '::1', '2025-11-04 15:56:28'),
(60, 7, 'update_product', 'Updated product: Running Shoes', '::1', '2025-11-04 15:57:38'),
(61, 7, 'update_order_status', 'Updated order #13 to processing', '::1', '2025-11-04 15:58:37'),
(62, 7, 'update_order_status', 'Updated order #13 to processing', '::1', '2025-11-04 16:00:43'),
(63, 7, 'update_order_status', 'Updated order #13 to processing', '::1', '2025-11-04 16:01:21'),
(64, 7, 'update_order_status', 'Updated order #13 to delivered', '::1', '2025-11-04 16:01:27'),
(65, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-04 16:29:31'),
(66, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-04 16:29:37'),
(67, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-04 16:33:48'),
(68, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-04 16:33:51'),
(69, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-04 16:36:40'),
(70, 7, 'update_order_status', 'Updated order #14 to delivered and payment to pending', '::1', '2025-11-04 16:39:05'),
(71, 7, 'update_order_status', 'Updated order #14 to delivered and payment to pending', '::1', '2025-11-04 16:40:38'),
(72, 7, 'update_order_status', 'Updated order #14 to delivered and payment to paid', '::1', '2025-11-04 16:45:59'),
(73, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 16:46:16'),
(74, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 16:46:24'),
(75, 7, 'update_order_status', 'Updated order #15 to cancelled', '::1', '2025-11-04 16:47:14'),
(76, 7, 'update_order_status', 'Updated order #14 to delivered and payment to pending', '::1', '2025-11-04 16:48:10'),
(77, 7, 'update_order_status', 'Updated order #14 to delivered and payment to pending', '::1', '2025-11-04 16:48:23'),
(78, 7, 'update_order_status', 'Updated order #13 to delivered and payment to pending', '::1', '2025-11-04 16:48:45'),
(79, 7, 'update_order_status', 'Updated order #14 to cancelled', '::1', '2025-11-04 16:49:20'),
(80, 7, 'update_order_status', 'Updated order #14 to pending and payment to paid', '::1', '2025-11-04 16:52:57'),
(81, 7, 'update_order_status', 'Updated order #14 to pending and payment to pending', '::1', '2025-11-04 16:53:17'),
(82, 7, 'update_order_status', 'Updated order #14 to pending and payment to pending', '::1', '2025-11-04 16:53:20'),
(83, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 17:13:55'),
(84, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-04 17:14:08'),
(85, 7, 'update_order_status', 'Updated order #13 to delivered and payment to pending', '::1', '2025-11-04 17:14:25'),
(86, 7, 'update_order_status', 'Updated order #15 to processing', '::1', '2025-11-04 17:15:04'),
(87, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 17:15:19'),
(88, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 17:20:16'),
(89, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 17:20:27'),
(90, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 17:21:05'),
(91, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 17:23:38'),
(92, 7, 'update_order_status', 'Updated order #15 to pending', '::1', '2025-11-04 17:23:44'),
(93, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-04 17:31:53'),
(94, 7, 'update_order_status', 'Updated order #15 to cancelled and payment to pending', '::1', '2025-11-04 18:24:47'),
(95, 7, 'update_order_status', 'Updated order #15 to pending and payment to pending', '::1', '2025-11-04 18:25:03'),
(96, 7, 'update_order_status', 'Updated order #15 to cancelled and payment to pending', '::1', '2025-11-04 18:27:51'),
(97, 7, 'login', NULL, '::1', '2025-11-05 04:08:29'),
(98, 7, 'update_order_status', 'Updated order #14 to pending and payment to pending', '::1', '2025-11-05 05:13:15'),
(99, 7, 'update_order_status', 'Updated order #14 to processing and payment to pending', '::1', '2025-11-05 05:14:01'),
(100, 7, 'update_order_status', 'Updated order #14 to shipped and payment to pending', '::1', '2025-11-05 05:14:23'),
(101, 7, 'update_order_status', 'Updated order #14 to delivered and payment to pending', '::1', '2025-11-05 05:15:01'),
(102, 7, 'update_order_status', 'Updated order #14 to shipped and payment to pending', '::1', '2025-11-05 05:15:49'),
(103, 7, 'update_order_status', 'Updated order #14 to pending and payment to pending', '::1', '2025-11-05 05:16:33'),
(104, 7, 'login', NULL, '::1', '2025-11-05 07:40:13'),
(105, 7, 'update_coupon', 'Updated coupon ID: 5', '::1', '2025-11-05 07:43:59'),
(106, 7, 'update_coupon', 'Updated coupon ID: 5', '::1', '2025-11-05 07:44:05'),
(107, 7, 'update_coupon', 'Updated coupon ID: 5', '::1', '2025-11-05 07:44:18'),
(108, 7, 'update_coupon', 'Updated coupon ID: 1', '::1', '2025-11-05 07:44:26'),
(109, 7, 'update_coupon', 'Updated coupon ID: 1', '::1', '2025-11-05 07:44:29'),
(110, 7, 'logout', NULL, '::1', '2025-11-05 09:52:31'),
(111, 7, 'login', NULL, '::1', '2025-11-05 09:52:55'),
(112, 7, 'logout', NULL, '::1', '2025-11-05 16:07:08'),
(113, 7, 'login', NULL, '::1', '2025-11-05 16:07:22'),
(114, 7, 'update_order_status', 'Updated order #14 to delivered and payment to pending', '::1', '2025-11-05 16:13:16'),
(115, 7, 'update_order_status', 'Updated order #14 to delivered and payment to paid', '::1', '2025-11-05 16:13:51'),
(116, 7, 'update_order_status', 'Updated order #14 to delivered and payment to paid', '::1', '2025-11-05 16:14:15'),
(117, 7, 'update_order_status', 'Updated order #14 to returned', '::1', '2025-11-05 18:09:19'),
(118, 7, 'update_order_status', 'Updated order #14 to delivered', '::1', '2025-11-05 18:13:40'),
(119, 7, 'update_order_status', 'Updated order #13 to returned', '::1', '2025-11-05 18:15:29'),
(120, 7, 'update_order_status', 'Updated order #14 to returned', '::1', '2025-11-05 18:28:02'),
(121, 7, 'update_order_status', 'Updated order #13 to pending', '::1', '2025-11-05 18:32:14'),
(122, 7, 'update_order_status', 'Updated order #14 to pending', '::1', '2025-11-05 18:32:18'),
(123, 7, 'update_order_status', 'Updated order #14 to pending', '::1', '2025-11-05 18:32:22'),
(124, 7, 'update_order_status', 'Updated order #13 to pending', '::1', '2025-11-05 18:32:26'),
(125, 7, 'update_order_status', 'Updated order #12 to pending', '::1', '2025-11-05 18:35:19'),
(126, 7, 'update_order_status', 'Updated order #12 to delivered', '::1', '2025-11-05 18:35:39'),
(127, 7, 'update_order_status', 'Updated order #12 to pending', '::1', '2025-11-05 18:36:55'),
(128, 7, 'update_order_status', 'Updated order #12 to pending', '::1', '2025-11-05 18:37:10'),
(129, 7, 'update_order_status', 'Updated order #12 to pending', '::1', '2025-11-05 18:37:13'),
(130, 7, 'update_order_status', 'Updated order #14 to delivered and payment to paid', '::1', '2025-11-05 18:57:25'),
(131, 7, 'login', NULL, '::1', '2025-11-06 05:35:57'),
(132, 7, 'logout', NULL, '::1', '2025-11-06 07:11:29'),
(133, 7, 'login', NULL, '::1', '2025-11-06 07:16:43'),
(134, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-06 07:17:32'),
(135, 7, 'update_order_status', 'Updated order #13 to delivered and payment to paid', '::1', '2025-11-06 07:17:35'),
(136, 7, 'login', NULL, '::1', '2025-11-06 15:29:47'),
(137, 7, 'logout', NULL, '::1', '2025-11-06 15:36:14'),
(138, 7, 'login', NULL, '::1', '2025-11-06 15:47:03'),
(139, 7, 'login', NULL, '::1', '2025-11-06 16:08:04'),
(140, 7, 'delete_user', 'Hard-deleted user: nyinyi.kelvin98@gmail.com', '::1', '2025-11-06 16:08:24'),
(141, 7, 'update_order_status', 'Updated order #9 to returned and payment to paid', '::1', '2025-11-06 16:10:10'),
(142, 7, 'update_order_status', 'Updated order #9 to delivered', '::1', '2025-11-06 16:10:27'),
(143, 7, 'update_order_status', 'Updated order #9 to delivered', '::1', '2025-11-06 16:10:42'),
(144, 7, 'update_order_status', 'Updated order #11 to delivered and payment to paid', '::1', '2025-11-06 16:46:53'),
(145, 7, 'update_order_status', 'Updated order #12 to delivered and payment to paid', '::1', '2025-11-06 18:00:26'),
(146, 7, 'update_order_status', 'Updated order #12 to delivered and payment to pending', '::1', '2025-11-06 18:00:38'),
(147, 7, 'update_order_status', 'Updated order #12 to processing', '::1', '2025-11-06 18:03:35'),
(148, 7, 'update_order_status', 'Updated order #12 to processing and payment to paid', '::1', '2025-11-06 18:04:08'),
(149, 7, 'update_order_status', 'Updated order #12 to pending and payment to paid', '::1', '2025-11-06 18:04:33'),
(150, 7, 'update_order_status', 'Updated order #12 to shipped and payment to paid', '::1', '2025-11-06 18:04:53'),
(151, 7, 'update_order_status', 'Updated order #12 to delivered and payment to paid', '::1', '2025-11-06 18:05:09'),
(152, 7, 'update_order_status', 'Updated order #12 to delivered and payment to pending', '::1', '2025-11-06 18:05:20'),
(153, 7, 'logout', NULL, '::1', '2025-11-06 18:50:23'),
(154, 7, 'login', NULL, '::1', '2025-11-06 18:50:35'),
(155, 7, 'add_product', 'Product: Puma Men&#039;s Essentials Big Logo Hoodie', '::1', '2025-11-07 13:16:39'),
(156, 7, 'update_product', 'Updated product: Puma Men&#039;s Essentials Big Logo Hoodie', '::1', '2025-11-07 13:16:59'),
(157, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 13:18:04'),
(158, 7, 'delete_variant', 'Deleted variant ID: 11', '::1', '2025-11-07 14:32:03'),
(159, 7, 'delete_product', 'Product: Ghost 2 (ID 11)', '::1', '2025-11-07 14:40:05'),
(160, 7, 'delete_product', 'Product: Puma Men Essentials Big Logo Hoodie (ID 16)', '::1', '2025-11-07 14:51:34'),
(161, 7, 'add_category', 'Added new category: fsdfa', '::1', '2025-11-07 15:00:46'),
(162, 7, 'delete_category', 'Deleted category ID: 13', '::1', '2025-11-07 15:02:13'),
(163, 7, 'add_product', 'Product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 15:04:16'),
(164, 7, 'delete_product', 'Product: Puma Men Essentials Big Logo Hoodie (ID 17)', '::1', '2025-11-07 15:04:32'),
(165, 7, 'add_product', 'Product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 15:13:44'),
(166, 7, 'delete_product_soft', 'Product (kept for order refs): Men Classic T-Shirt (ID 1)', '::1', '2025-11-07 15:13:57'),
(167, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 15:25:29'),
(168, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 15:25:44'),
(169, 7, 'update_category', 'Updated category: Accessoriesy', '::1', '2025-11-07 16:16:58'),
(170, 7, 'update_category', 'Updated category: Accessoriesy', '::1', '2025-11-07 16:17:06'),
(171, 7, 'update_category', 'Updated category: Accessories', '::1', '2025-11-07 16:17:11'),
(172, 7, 'update_category', 'Updated category: Accessories', '::1', '2025-11-07 16:17:14'),
(173, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 16:28:15'),
(174, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 16:28:47'),
(175, 7, 'update_product', 'Updated product: Men Classic T-Shirt', '::1', '2025-11-07 16:29:26'),
(176, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-07 16:30:43'),
(177, 7, 'delete_product_blocked', 'Product (sales history; set inactive): Men Classic T-Shirt (ID 1)', '::1', '2025-11-07 16:31:43'),
(178, 7, 'update_product', 'Updated product: Men Classic T-Shirt', '::1', '2025-11-07 16:31:55'),
(179, 7, 'delete_product_blocked', 'Product (sales history; set inactive): Men Classic T-Shirt (ID 1)', '::1', '2025-11-07 16:32:22'),
(180, 7, 'logout', NULL, '::1', '2025-11-07 16:33:35'),
(181, 7, 'login', NULL, '::1', '2025-11-07 16:34:16'),
(182, 7, 'update_product', 'Updated product: Socks Pack', '::1', '2025-11-07 16:35:03'),
(183, 7, 'add_product', 'Product: New Balance Fresh Foam Running Shoes', '::1', '2025-11-07 17:28:56'),
(184, 7, 'delete_variant', 'Deleted variant ID: 32', '::1', '2025-11-07 17:31:38'),
(185, 7, 'delete_variant', 'Deleted variant ID: 33', '::1', '2025-11-07 17:35:09'),
(186, 7, 'delete_variant', 'Deleted variant ID: 35', '::1', '2025-11-07 17:43:08'),
(187, 7, 'delete_variant', 'Deleted variant ID: 36', '::1', '2025-11-07 17:53:26'),
(188, 7, 'update_product', 'Updated product: Men Classic T-Shirt', '::1', '2025-11-07 18:13:12'),
(189, 7, 'login', NULL, '::1', '2025-11-08 05:15:20'),
(190, 7, 'update_product', 'Updated product: New Balance Fresh Foam Running Shoes', '::1', '2025-11-08 05:29:16'),
(191, 7, 'delete_image', 'Deleted product image ID: 28', '::1', '2025-11-08 05:29:22'),
(192, 7, 'update_product', 'Updated product: Socks Pack', '::1', '2025-11-08 05:50:55'),
(193, 7, 'delete_variant', 'Deleted variant ID: 14', '::1', '2025-11-08 06:19:00'),
(194, 7, 'add_product', 'Product: hj', '::1', '2025-11-08 06:54:05'),
(195, 7, 'delete_product', 'Product: hj (ID 20)', '::1', '2025-11-08 06:54:40'),
(196, 7, 'add_product', 'Product: hj', '::1', '2025-11-08 06:55:10'),
(197, 7, 'delete_product', 'Product: hj (ID 21)', '::1', '2025-11-08 06:56:01'),
(198, 7, 'add_product', 'Product: hj', '::1', '2025-11-08 07:17:00'),
(199, 7, 'delete_product', 'Product: hj (ID 22)', '::1', '2025-11-08 07:17:43'),
(200, 7, 'update_category', 'Updated category: Demo', '::1', '2025-11-08 10:33:22'),
(201, 7, 'update_category', 'Updated category: Sales', '::1', '2025-11-08 10:33:45'),
(202, 7, 'update_category', 'Updated category: Sales', '::1', '2025-11-08 10:33:55'),
(203, 7, 'update_category', 'Updated category: Unisex', '::1', '2025-11-08 15:04:58'),
(204, 7, 'login', NULL, '::1', '2025-11-08 15:10:17'),
(205, 7, 'delete_image', 'Deleted product image ID: 27', '::1', '2025-11-08 15:15:21'),
(206, 7, 'update_product', 'Updated product: Uniqlo AIRism Cotton Oversized Crew Neck T-Shirt', '::1', '2025-11-08 15:17:15'),
(207, 7, 'update_product', 'Updated product: Uniqlo AIRism Cotton Oversized Crew Neck T-Shirt', '::1', '2025-11-08 15:17:28'),
(208, 7, 'delete_variant', 'Deleted variant ID: 2', '::1', '2025-11-08 15:22:28'),
(209, 7, 'update_product', 'Updated product: Uniqlo AIRism Cotton Oversized Crew Neck T-Shirt', '::1', '2025-11-08 15:25:27'),
(210, 7, 'delete_image', 'Deleted product image ID: 35', '::1', '2025-11-08 15:25:54'),
(211, 7, 'update_product', 'Updated product: Uniqlo AIRism Cotton Oversized Crew Neck T-Shirt', '::1', '2025-11-08 15:27:59'),
(212, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-08 15:41:07'),
(213, 7, 'delete_image', 'Deleted product image ID: 25', '::1', '2025-11-08 15:41:14'),
(214, 7, 'delete_image', 'Deleted product image ID: 26', '::1', '2025-11-08 15:41:32'),
(215, 7, 'update_product', 'Updated product: Puma Men Essentials Big Logo Hoodie', '::1', '2025-11-08 15:43:36'),
(216, 7, 'update_product', 'Updated product: Women Floral Dress', '::1', '2025-11-08 15:57:42'),
(217, 7, 'delete_image', 'Deleted product image ID: 12', '::1', '2025-11-08 15:57:47'),
(218, 7, 'update_product', 'Updated product: Men Jeans', '::1', '2025-11-08 15:59:56'),
(219, 7, 'update_product', 'Updated product: Levis 511 Slim Straight Fit Men Jeans', '::1', '2025-11-08 16:01:20'),
(220, 7, 'delete_image', 'Deleted product image ID: 17', '::1', '2025-11-08 16:03:00'),
(221, 7, 'update_product', 'Updated product: Levis 511 Slim Straight Fit Men Jeans', '::1', '2025-11-08 16:03:06'),
(222, 7, 'update_product', 'Updated product: Levis 511 Slim Straight Fit Men Jeans', '::1', '2025-11-08 16:06:16'),
(223, 7, 'update_product', 'Updated product: Puffer Jacket', '::1', '2025-11-08 16:06:28'),
(224, 7, 'update_product', 'Updated product: Puffer Jacket', '::1', '2025-11-08 16:07:39'),
(225, 7, 'delete_image', 'Deleted product image ID: 18', '::1', '2025-11-08 16:07:43'),
(226, 7, 'update_product', 'Updated product: Columbia Puffer Jacket', '::1', '2025-11-08 16:08:28'),
(227, 7, 'update_product', 'Updated product: Puffer Jacket', '::1', '2025-11-08 16:08:33'),
(228, 7, 'update_product', 'Updated product: Kids Hoodie', '::1', '2025-11-08 16:10:59'),
(229, 7, 'delete_image', 'Deleted product image ID: 13', '::1', '2025-11-08 16:11:04'),
(230, 7, 'update_product', 'Updated product: Zara Kids Looney Tunes Hoodie', '::1', '2025-11-08 16:11:18'),
(231, 7, 'update_product', 'Updated product: Running Shoes', '::1', '2025-11-08 16:13:19'),
(232, 7, 'delete_image', 'Deleted product image ID: 15', '::1', '2025-11-08 16:13:25'),
(233, 7, 'update_product', 'Updated product: Nike Pegasus Trail 2 GORE-TEX Trail Running Shoes', '::1', '2025-11-08 16:13:47'),
(234, 7, 'delete_variant', 'Deleted variant ID: 6', '::1', '2025-11-08 16:20:47'),
(235, 7, 'update_product', 'Updated product: H&amp;M Women Floral Dress', '::1', '2025-11-08 16:22:51'),
(236, 7, 'update_product', 'Updated product: H&amp;M Women Floral Dress', '::1', '2025-11-08 16:23:10'),
(237, 7, 'update_product', 'Updated product: Zara Women Floral Dress', '::1', '2025-11-08 16:23:59'),
(238, 7, 'update_product', 'Updated product: Leather Belt', '::1', '2025-11-08 16:25:57'),
(239, 7, 'delete_image', 'Deleted product image ID: 14', '::1', '2025-11-08 16:26:01'),
(240, 7, 'update_product', 'Updated product: Levi&#039;s New Ashland Leather Belt', '::1', '2025-11-08 16:26:12'),
(241, 7, 'update_product', 'Updated product: Levi&#039;s New Ashland Leather Belt', '::1', '2025-11-08 16:27:02'),
(242, 7, 'delete_image', 'Deleted product image ID: 42', '::1', '2025-11-08 16:27:27'),
(243, 7, 'update_product', 'Updated product: Levis New Ashland Leather Belt', '::1', '2025-11-08 16:28:26'),
(244, 7, 'update_product', 'Updated product: Women Handbag', '::1', '2025-11-08 16:30:34'),
(245, 7, 'delete_image', 'Deleted product image ID: 16', '::1', '2025-11-08 16:30:41'),
(246, 7, 'delete_variant', 'Deleted variant ID: 7', '::1', '2025-11-08 16:31:00'),
(247, 7, 'update_product', 'Updated product: Women Handbag', '::1', '2025-11-08 16:31:05'),
(248, 7, 'update_product', 'Updated product: Guess Assia Quilted Logo Handbag', '::1', '2025-11-08 16:31:22'),
(249, 7, 'update_product', 'Updated product: Women Handbag', '::1', '2025-11-08 16:31:32'),
(250, 7, 'update_product', 'Updated product: Columbia Puffer Jacket', '::1', '2025-11-08 16:31:43'),
(251, 7, 'update_product', 'Updated product: Nike Crew Socks 6 Pair', '::1', '2025-11-08 16:33:40'),
(252, 7, 'update_product', 'Updated product: Nike Crew Socks 6 Pair', '::1', '2025-11-08 16:33:45'),
(253, 7, 'delete_image', 'Deleted product image ID: 20', '::1', '2025-11-08 16:33:54'),
(254, 7, 'update_product', 'Updated product: Adidas Adizero Gel Running Shorts', '::1', '2025-11-08 16:36:12'),
(255, 7, 'update_product', 'Updated product: Adidas Adizero Gel Running Shorts', '::1', '2025-11-08 16:36:18'),
(256, 7, 'delete_image', 'Deleted product image ID: 19', '::1', '2025-11-08 16:36:23'),
(257, 7, 'delete_image', 'Deleted product image ID: 45', '::1', '2025-11-08 16:37:31'),
(258, 7, 'update_product', 'Updated product: Zara Kids Looney Tunes Hoodie', '::1', '2025-11-08 16:42:24'),
(259, 7, 'delete_variant', 'Deleted variant ID: 22', '::1', '2025-11-08 16:44:28'),
(260, 7, 'delete_variant', 'Deleted variant ID: 23', '::1', '2025-11-08 16:44:31'),
(261, 7, 'delete_variant', 'Deleted variant ID: 24', '::1', '2025-11-08 16:44:33'),
(262, 7, 'delete_variant', 'Deleted variant ID: 4', '::1', '2025-11-08 16:46:27'),
(263, 7, 'update_product', 'Updated product: Guess Assia Quilted Logo Handbag', '::1', '2025-11-08 16:49:52'),
(264, 7, 'delete_variant', 'Deleted variant ID: 8', '::1', '2025-11-08 16:50:07'),
(265, 7, 'delete_variant', 'Deleted variant ID: 59', '::1', '2025-11-08 17:49:48'),
(266, 7, 'delete_variant', 'Deleted variant ID: 60', '::1', '2025-11-08 17:49:50'),
(267, 7, 'delete_variant', 'Deleted variant ID: 61', '::1', '2025-11-08 17:49:53'),
(268, 7, 'delete_variant', 'Deleted variant ID: 62', '::1', '2025-11-08 17:49:55'),
(269, 7, 'delete_variant', 'Deleted variant ID: 63', '::1', '2025-11-08 17:49:58'),
(270, 7, 'delete_variant', 'Deleted variant ID: 64', '::1', '2025-11-08 17:50:00'),
(271, 7, 'delete_variant', 'Deleted variant ID: 69', '::1', '2025-11-08 17:51:34'),
(272, 7, 'delete_variant', 'Deleted variant ID: 70', '::1', '2025-11-08 17:51:36'),
(273, 7, 'delete_variant', 'Deleted variant ID: 71', '::1', '2025-11-08 18:06:50'),
(274, 7, 'delete_variant', 'Deleted variant ID: 72', '::1', '2025-11-08 18:06:53'),
(275, 7, 'delete_variant', 'Deleted variant ID: 73', '::1', '2025-11-08 18:06:55'),
(276, 7, 'delete_variant', 'Deleted variant ID: 74', '::1', '2025-11-08 18:06:57'),
(277, 7, 'delete_variant', 'Deleted variant ID: 75', '::1', '2025-11-08 18:06:59'),
(278, 7, 'delete_variant', 'Deleted variant ID: 76', '::1', '2025-11-08 18:07:01'),
(279, 7, 'logout', NULL, '::1', '2025-11-08 18:13:48'),
(280, 7, 'login', NULL, '::1', '2025-11-08 18:13:59'),
(281, 7, 'login', NULL, '::1', '2025-11-09 05:29:35'),
(282, 7, 'login', NULL, '::1', '2025-11-09 05:43:01'),
(283, 7, 'create_coupon', 'Created coupon: SUMMER2025', '::1', '2025-11-09 06:21:16'),
(284, 7, 'delete_coupon', 'Deleted coupon: SUMMER2025', '::1', '2025-11-09 06:22:31'),
(285, 7, 'create_coupon', 'Created coupon: WINTER2025', '::1', '2025-11-09 06:23:06'),
(286, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:23:06'),
(287, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:23:13'),
(288, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:23:23'),
(289, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:23:36'),
(290, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:23:40'),
(291, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:24:21'),
(292, 7, 'delete_coupon', 'Deleted coupon: WINTER2025', '::1', '2025-11-09 06:24:25'),
(293, 7, 'create_coupon', 'Created coupon: WINTER2025', '::1', '2025-11-09 06:25:17'),
(294, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:25:17'),
(295, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 06:25:26'),
(296, 7, 'logout', NULL, '::1', '2025-11-09 06:25:53'),
(297, 7, 'login', NULL, '::1', '2025-11-09 06:26:05'),
(298, 7, 'update_coupon', 'Updated coupon ID: 8', '::1', '2025-11-09 06:33:02'),
(299, 7, 'update_coupon', 'Updated coupon ID: 8', '::1', '2025-11-09 06:33:05'),
(300, 7, 'update_coupon', 'Updated coupon ID: 8', '::1', '2025-11-09 06:34:40'),
(301, 7, 'logout', NULL, '::1', '2025-11-09 06:48:13'),
(302, 7, 'login', NULL, '::1', '2025-11-09 06:48:23'),
(303, 7, 'login', NULL, '::1', '2025-11-09 06:50:15'),
(304, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 06:51:52'),
(305, 7, 'login', NULL, '::1', '2025-11-09 07:30:45'),
(306, 7, 'update_order_status', 'Updated order #16 to pending and payment to paid', '::1', '2025-11-09 07:34:47'),
(307, 7, 'update_order_status', 'Updated order #16 to processing and payment to paid', '::1', '2025-11-09 07:35:08'),
(308, 7, 'update_order_status', 'Updated order #16 to pending and payment to pending', '::1', '2025-11-09 07:35:25'),
(309, 7, 'update_order_status', 'Updated order #16 to pending and payment to paid', '::1', '2025-11-09 07:39:21'),
(310, 7, 'update_order_status', 'Updated order #16 to pending and payment to paid', '::1', '2025-11-09 07:39:57'),
(311, 7, 'update_order_status', 'Updated order #16 to processing', '::1', '2025-11-09 08:17:13'),
(312, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:17:27'),
(313, 7, 'update_order_status', 'Updated order #16 to cancelled', '::1', '2025-11-09 08:18:37'),
(314, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:19:10'),
(315, 7, 'update_order_status', 'Updated order #16 to cancelled and payment to paid', '::1', '2025-11-09 08:19:20'),
(316, 7, 'update_order_status', 'Updated order #16 to cancelled and payment to paid', '::1', '2025-11-09 08:19:26'),
(317, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:19:34'),
(318, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:20:13'),
(319, 7, 'update_order_status', 'Updated order #16 to cancelled and payment to paid', '::1', '2025-11-09 08:20:28'),
(320, 7, 'update_order_status', 'Updated order #16 to pending and payment to paid', '::1', '2025-11-09 08:20:34'),
(321, 7, 'update_order_status', 'Updated order #16 to pending and payment to pending', '::1', '2025-11-09 08:21:06'),
(322, 7, 'update_order_status', 'Updated order #16 to pending and payment to pending', '::1', '2025-11-09 08:21:19'),
(323, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:23:12'),
(324, 7, 'delete_coupon', 'Deleted coupon: WINTER2025', '::1', '2025-11-09 08:32:57'),
(325, 7, 'create_coupon', 'Created coupon: WINTER2025', '::1', '2025-11-09 08:33:22'),
(326, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 08:33:22'),
(327, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 08:33:31'),
(328, 7, 'delete_coupon', 'Deleted coupon: ', '::1', '2025-11-09 08:33:54'),
(329, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:42:50'),
(330, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:42:53'),
(331, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:45:13'),
(332, 7, 'update_order_status', 'Updated order #16 to pending', '::1', '2025-11-09 08:45:31'),
(333, 7, 'update_product', 'Updated product: Levis 511 Slim Straight Fit Men Jeans', '::1', '2025-11-09 08:47:04'),
(334, 7, 'update_product', 'Updated product: New Balance Fresh Foam Running Shoes', '::1', '2025-11-09 08:47:23'),
(335, 7, 'delete_coupon', 'Deleted coupon: WINTER2025', '::1', '2025-11-09 09:09:52'),
(336, 7, 'create_coupon', 'Created coupon: WINTER2025', '::1', '2025-11-09 09:18:50'),
(337, 7, 'login', NULL, '::1', '2025-11-09 09:38:58'),
(338, 7, 'login', NULL, '::1', '2025-11-09 10:07:20'),
(339, 7, 'login', NULL, '::1', '2025-11-09 10:23:07'),
(340, 7, 'login', NULL, '::1', '2025-11-09 15:05:52'),
(341, 7, 'login', NULL, '::1', '2025-11-09 16:27:39'),
(342, 7, 'update_order_status', 'Updated order #22 to pending', '::1', '2025-11-09 16:29:01'),
(343, 7, 'update_order_status', 'Updated order #22 to processing and payment to pending', '::1', '2025-11-09 16:29:45'),
(344, 7, 'update_order_status', 'Updated order #22 to shipped and payment to pending', '::1', '2025-11-09 16:30:31'),
(345, 7, 'update_order_status', 'Updated order #22 to delivered and payment to pending', '::1', '2025-11-09 16:35:31'),
(346, 7, 'update_order_status', 'Updated order #22 to delivered and payment to paid', '::1', '2025-11-09 16:36:23'),
(347, 7, 'update_order_status', 'Updated order #22 to delivered and payment to pending', '::1', '2025-11-09 16:53:32'),
(348, 7, 'update_order_status', 'Updated order #22 to delivered and payment to paid', '::1', '2025-11-09 16:54:12'),
(349, 7, 'restock_on_return', 'return_id: 4, order_id: 22', '::1', '2025-11-09 16:58:37'),
(350, 7, 'restock_on_return', 'return_id: 4, order_id: 22', '::1', '2025-11-09 16:59:20'),
(351, 7, 'login', NULL, '::1', '2025-11-09 17:07:48'),
(352, 7, 'restock_on_return', 'return_id: 4, order_id: 22', '::1', '2025-11-09 17:21:39'),
(353, 7, 'restock_on_return', 'return_id: 4, order_id: 22', '::1', '2025-11-09 17:24:56'),
(354, 7, 'restock_on_return', 'return_id: 4, order_id: 22', '::1', '2025-11-09 17:27:55'),
(355, 7, 'delete_user', 'Hard-deleted user: nyinyi.kelvin98@gmail.com', '::1', '2025-11-09 17:42:05'),
(356, 7, 'login', NULL, '::1', '2025-11-09 17:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `session_id`, `created_at`, `updated_at`) VALUES
(2, 2, 'DEF234', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(3, 3, 'GHI345', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(4, 4, 'JKL456', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(5, 5, 'MNO567', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(6, 6, 'PQR678', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(7, 7, 'STU789', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(8, 8, 'VWX890', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(9, 9, 'YZA901', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(10, 10, 'BCD012', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(12, NULL, 'dv30kqtl01naj1dhpotfn2cqnh', '2025-10-28 03:38:05', '2025-10-28 03:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `variant_id`, `quantity`, `price`, `added_at`) VALUES
(2, 2, 2, NULL, 1, 30.59, '2025-10-27 05:01:28'),
(3, 3, 3, NULL, 2, 23.75, '2025-10-27 05:01:28'),
(4, 4, 4, NULL, 1, 19.99, '2025-10-27 05:01:28'),
(5, 5, 5, NULL, 1, 47.99, '2025-10-27 05:01:28'),
(6, 6, 6, NULL, 1, 44.99, '2025-10-27 05:01:28'),
(7, 7, 7, NULL, 1, 38.25, '2025-10-27 05:01:28'),
(8, 8, 8, NULL, 1, 67.49, '2025-10-27 05:01:28'),
(9, 9, 9, NULL, 1, 23.39, '2025-10-27 05:01:28'),
(10, 10, 10, NULL, 1, 12.99, '2025-10-27 05:01:28'),
(12, 12, 8, 9, 1, 67.00, '2025-10-28 03:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `parent_id`, `slug`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Men', NULL, 'men', 'Men clothing and accessories', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(2, 'Women', NULL, 'women', 'Women clothing and accessories', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(3, 'Kids', NULL, 'kids', 'Kids clothing and accessories', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(4, 'Accessories', NULL, 'accessories', 'Fashion accessories', NULL, 1, '2025-10-27 05:01:27', '2025-11-07 16:17:11'),
(5, 'Shoes', NULL, 'shoes', 'Footwear for all', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(6, 'Outerwear', NULL, 'outerwear', 'Jackets and coats', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(7, 'Sportswear', NULL, 'sportswear', 'Active wear and gym clothing', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(8, 'Underwear', NULL, 'underwear', 'Inner garments', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(9, 'Bags', NULL, 'bags', 'Handbags, backpacks, etc.', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(10, 'Unisex', NULL, 'sale', 'Products everyone can wear', NULL, 1, '2025-10-27 05:01:27', '2025-11-08 15:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`coupon_id`, `code`, `discount_type`, `discount_value`, `min_purchase_amount`, `max_discount_amount`, `usage_limit`, `is_active`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 0.00, 0.00, 0, 1, '2025-12-31 23:59:59', '2025-10-27 20:04:48', '2025-11-05 07:44:29'),
(2, 'SAVE20', 'percentage', 20.00, 50.00, 20.00, 100, 1, '2025-12-31 23:59:59', '2025-10-27 20:04:48', '2025-10-27 20:04:48'),
(4, 'NEWYEAR25', 'percentage', 25.00, 200.00, 50.00, 200, 1, '2025-12-31 23:59:59', '2025-10-27 20:04:48', '2025-10-27 20:04:48'),
(5, '20CCF7', 'fixed', 100.00, 12.00, 15.00, 12, 1, '2025-10-31 02:35:00', '2025-10-27 20:06:02', '2025-11-05 07:44:18'),
(10, 'WINTER2025', 'fixed', 10.00, 100.00, 150.00, 10, 1, '2025-11-14 15:48:00', '2025-11-09 09:18:50', '2025-11-09 09:18:50');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `subscriber_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`subscriber_id`, `email`, `subscribed_at`, `is_active`) VALUES
(1, 'john.doe@example.com', '2025-10-27 05:01:28', 1),
(2, 'jane.smith@example.com', '2025-10-27 05:01:28', 1),
(3, 'michael.jones@example.com', '2025-10-27 05:01:28', 1),
(4, 'emily.brown@example.com', '2025-10-27 05:01:28', 1),
(5, 'kevin.taylor@example.com', '2025-10-27 05:01:28', 1),
(6, 'lucas.wilson@example.com', '2025-10-27 05:01:28', 1),
(7, 'maria.miller@example.com', '2025-10-27 05:01:28', 1),
(8, 'ryan.moore@example.com', '2025-10-27 05:01:28', 1),
(9, 'ella.davis@example.com', '2025-10-27 05:01:28', 1),
(10, 'sara.anderson@example.com', '2025-10-27 05:01:28', 1),
(11, 'soewana271103@gmail.com', '2025-10-27 05:14:15', 1),
(14, 'nyinyi.kelvin98@gmail.com', '2025-11-09 06:12:26', 1),
(15, 'phyumonthae313@gmail.com', '2025-11-09 17:10:30', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','processing','shipped','delivered','cancelled','returned') DEFAULT 'pending',
  `shipping_address_id` int(11) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_number`, `subtotal`, `tax_amount`, `shipping_amount`, `discount_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `shipping_address_id`, `tracking_number`, `notes`, `created_at`, `updated_at`) VALUES
(2, 2, 'ORD002', 25.00, 2.50, 5.99, 2.00, 31.49, 'Credit Card', 'paid', 'shipped', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(4, 4, 'ORD004', 35.00, 3.50, 5.99, 0.00, 44.49, 'COD', 'pending', 'processing', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(5, 5, 'ORD005', 80.00, 8.00, 5.99, 5.00, 88.99, 'Credit Card', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(6, 6, 'ORD006', 19.99, 1.99, 5.99, 0.00, 27.97, 'ShopeePay', 'paid', 'cancelled', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(7, 7, 'ORD007', 45.00, 4.50, 5.99, 0.00, 55.49, 'ShopeePay', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(8, 8, 'ORD008', 33.00, 3.30, 0.00, 0.00, 36.30, 'Credit Card', 'paid', 'processing', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(9, 9, 'ORD009', 99.99, 9.99, 0.00, 10.00, 99.98, 'ShopeePay', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-11-06 16:10:27'),
(10, 10, 'ORD010', 120.00, 12.00, 0.00, 12.00, 120.00, 'ShopeePay', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(11, 11, 'ORD-20251027-C90DA6', 30.59, 3.06, 5.99, 0.00, 39.64, 'cod', 'paid', 'delivered', NULL, NULL, '[2025-11-07 00:40:18] Return request by user #11: return - not legit', '2025-10-27 05:13:03', '2025-11-06 18:10:18'),
(13, 11, 'ORD-20251104-B9A9BC', 107.00, 10.70, 0.00, 0.00, 117.70, 'cod', 'paid', 'delivered', 12, NULL, '\\n[2025-11-05 12:07:05] Return request by user #11: return - fds', '2025-11-04 15:57:27', '2025-11-06 07:17:32'),
(14, 11, 'ORD-20251104-E8ACFC', 14.00, 1.40, 5.99, 0.00, 21.39, 'paypal', 'paid', 'delivered', 12, NULL, 'fasdf[2025-11-05 22:44:02] Return request by user #11: exchange - wdd', '2025-11-04 16:37:55', '2025-11-05 18:57:25'),
(15, 11, 'ORD-20251104-38B159', 14.00, 1.40, 5.99, 0.00, 21.39, 'credit_card', 'pending', 'cancelled', 12, NULL, '', '2025-11-04 16:40:20', '2025-11-04 18:27:37'),
(16, 11, 'ORD-20251109-4AD086', 70.17, 7.02, 0.00, 0.00, 77.19, 'cod', 'pending', 'pending', 12, NULL, '', '2025-11-09 06:50:00', '2025-11-09 08:42:50'),
(22, 14, 'ORD-20251109-E10CEE', 61.18, 6.12, 0.00, 0.00, 67.30, 'cod', 'paid', 'delivered', 14, NULL, '[2025-11-09 23:24:43] Return request by user #14: return - not good[2025-11-09 23:27:30] Return request by user #14: return - not good', '2025-11-09 16:25:34', '2025-11-09 16:57:30'),
(23, 14, 'ORD-20251109-971362', 73.95, 7.40, 0.00, 0.00, 66.55, 'wallet', 'paid', 'pending', 14, NULL, '', '2025-11-09 16:51:22', '2025-11-09 16:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `variant_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES
(2, 2, 2, NULL, NULL, 1, 30.59, 30.59),
(4, 4, 3, NULL, NULL, 1, 23.75, 23.75),
(5, 5, 6, NULL, NULL, 1, 44.99, 44.99),
(6, 6, 9, NULL, NULL, 1, 23.39, 23.39),
(7, 7, 4, NULL, NULL, 1, 19.99, 19.99),
(8, 8, 7, NULL, NULL, 1, 38.25, 38.25),
(9, 9, 8, NULL, NULL, 1, 67.49, 67.49),
(10, 10, 10, NULL, NULL, 2, 12.99, 25.98),
(11, 11, 2, 3, 'Women Floral Dress', 1, 30.59, 30.59),
(13, 13, 5, 15, 'Running Shoes', 2, 47.00, 94.00),
(14, 14, 1, 1, 'Men Classic T-Shirt', 1, 14.00, 14.00),
(15, 15, 1, 1, 'Men Classic T-Shirt', 1, 14.00, 14.00),
(16, 16, 9, 10, 'Adidas Adizero Gel Running Shorts', 3, 23.39, 70.17),
(23, 22, 2, 47, 'Zara Women Floral Dress', 2, 30.59, 61.18),
(24, 23, 18, 25, 'Puma Men Essentials Big Logo Hoodie', 1, 73.95, 73.95);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `token`, `expires_at`, `created_at`, `used`) VALUES
(1, 11, '08da0ea1d7240343f6d88fb138774ef219b0c258b1741b850bec4ed86d0cc89f', '2025-10-28 11:11:50', '2025-10-28 03:41:50', 1),
(3, 14, 'feca5384a305525f4dce336d8a822f924e52828e3f29135187848812deba8cd8', '2025-11-10 01:22:04', '2025-11-09 17:52:04', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_trending` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `slug`, `description`, `category_id`, `brand`, `base_price`, `discount_percentage`, `final_price`, `sku`, `is_featured`, `is_trending`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Uniqlo AIRism Cotton Oversized Crew Neck T-Shirt', 'men-classic-tshirt', 'Smooth &amp;amp;amp;#039;AIRism&amp;amp;amp;#039; fabric tee for daily wear', 10, 'Uniqlo', 15.99, 10.00, 14.39, 'SKU-MEN001', 1, 1, 1, '2025-10-27 05:01:27', '2025-11-08 15:27:59'),
(2, 'Zara Women Floral Dress', 'women-floral-dress', 'Elegant summer floral dress', 2, 'Zara', 35.99, 15.00, 30.59, 'SKU-WMN001', 1, 1, 1, '2025-10-27 05:01:27', '2025-11-08 16:23:59'),
(3, 'Zara Kids Looney Tunes Hoodie', 'kids-hoodie', 'Warm fleece hoodie for kids', 3, 'Zara Kids', 25.00, 5.00, 23.75, 'SKU-KID001', 0, 1, 1, '2025-10-27 05:01:27', '2025-11-08 16:42:24'),
(4, 'Levis New Ashland Leather Belt', 'leather-belt', 'Genuine black leather belt', 4, 'Levi’s', 19.99, 0.00, 19.99, 'SKU-ACC001', 0, 0, 1, '2025-10-27 05:01:27', '2025-11-08 16:28:26'),
(5, 'Nike Pegasus Trail 2 GORE-TEX Trail Running Shoes', 'running-shoes', 'Lightweight running shoes', 5, 'Nike', 59.99, 20.00, 47.99, 'SKU-SHO001', 1, 1, 1, '2025-10-27 05:01:27', '2025-11-08 16:13:47'),
(6, 'Guess Assia Quilted Logo Handbag', 'women-handbag', 'Stylish faux leather handbag', 9, 'Guess', 49.99, 10.00, 44.99, 'SKU-BAG001', 1, 1, 1, '2025-10-27 05:01:27', '2025-11-08 16:49:52'),
(7, 'Levis 511 Slim Straight Fit Men Jeans', 'men-jeans', 'Slim-fit denim jeans', 1, 'Levi’s', 45.00, 15.00, 38.25, 'SKU-MEN002', 1, 1, 1, '2025-10-27 05:01:27', '2025-11-09 08:47:04'),
(8, 'Columbia Puffer Jacket', 'puffer-jacket', 'Insulated winter jacket', 6, 'Columbia', 89.99, 25.00, 67.49, 'SKU-OUT001', 1, 0, 1, '2025-10-27 05:01:27', '2025-11-08 16:31:43'),
(9, 'Adidas Adizero Gel Running Shorts', 'sports-shorts', 'Running shorts', 7, 'Adidas', 25.99, 10.00, 23.39, 'SKU-SPR001', 1, 1, 1, '2025-10-27 05:01:27', '2025-11-08 16:36:18'),
(10, 'Nike Crew Socks 6 Pair', 'socks-pack', 'Pack of 5 cotton socks', 4, 'Nike', 12.99, 0.00, 12.99, 'SKU-UND001', 0, 0, 1, '2025-10-27 05:01:27', '2025-11-08 16:33:45'),
(18, 'Puma Men Essentials Big Logo Hoodie', 'puma-men-essentials-big-logo-hoodie', 'Sweatshirt fabric: soft and warm fabric with a cosy raised texture inside\r\n\r\nMain: 100% Cotton.', 6, 'Puma', 87.00, 15.00, 73.95, 'OUT001', 1, 1, 1, '2025-11-07 15:13:44', '2025-11-08 15:43:36'),
(19, 'New Balance Fresh Foam Running Shoes', 'new-balance-fresh-foam-running-shoes', 'Engineered mesh upper\r\nMoisture-wicking designed to keep feet dry from sweat and moisture\r\nApprox. weight: 312 g\r\n\r\nSole: 100% Rubber, Upper: 100% Textile.', 5, 'New Balance', 237.00, 20.00, 189.60, 'SHO002', 0, 1, 1, '2025-11-07 17:28:56', '2025-11-09 08:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_url`, `is_primary`, `display_order`) VALUES
(29, 19, 'uploads/products/product_19_1762579756.webp', 1, 0),
(33, 1, 'uploads/products/product_1_1762615035.webp', 0, 0),
(34, 1, 'uploads/products/product_1_1762615048.webp', 1, 0),
(36, 1, 'uploads/products/product_1_1762615679.webp', 0, 0),
(37, 18, 'uploads/products/product_18_1762616467.jpg', 1, 0),
(38, 18, 'uploads/products/product_18_1762616616.jpg', 0, 0),
(39, 2, 'uploads/products/product_2_1762617462.jpg', 1, 0),
(40, 7, 'uploads/products/product_7_1762617596.webp', 0, 0),
(41, 7, 'uploads/products/product_7_1762617786.jpg', 1, 0),
(43, 8, 'uploads/products/product_8_1762617988.webp', 1, 0),
(44, 8, 'uploads/products/product_8_1762618059.webp', 0, 0),
(46, 3, 'uploads/products/product_3_1762618259.webp', 1, 0),
(47, 5, 'uploads/products/product_5_1762618399.jpg', 1, 0),
(48, 4, 'uploads/products/product_4_1762619156.jpg', 1, 0),
(49, 6, 'uploads/products/product_6_1762619434.jpg', 1, 0),
(50, 10, 'uploads/products/product_10_1762619625.webp', 1, 0),
(51, 9, 'uploads/products/product_9_1762619778.webp', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_title` varchar(255) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `product_id`, `user_id`, `rating`, `review_title`, `review_text`, `is_verified_purchase`, `created_at`) VALUES
(2, 2, 2, 4, 'Lovely dress', 'Perfect fit', 1, '2025-10-27 05:01:28'),
(3, 3, 3, 5, 'Great hoodie', 'Warm and comfy', 1, '2025-10-27 05:01:28'),
(4, 4, 4, 3, 'Okay', 'Decent for price', 1, '2025-10-27 05:01:28'),
(5, 5, 5, 5, 'Awesome shoes', 'Lightweight and comfy', 1, '2025-10-27 05:01:28'),
(6, 6, 6, 4, 'Nice bag', 'Good quality material', 1, '2025-10-27 05:01:28'),
(7, 7, 7, 4, 'Stylish jeans', 'Love the color', 1, '2025-10-27 05:01:28'),
(8, 8, 8, 5, 'Warm jacket', 'Perfect for winter', 1, '2025-10-27 05:01:28'),
(9, 9, 9, 4, 'Good shorts', 'Comfortable fabric', 1, '2025-10-27 05:01:28'),
(10, 10, 10, 3, 'Socks ok', 'Average quality', 1, '2025-10-27 05:01:28'),
(12, 18, 11, 5, 'Nice pullover', 'Athletic and cozy', 0, '2025-11-09 11:23:25'),
(13, 19, 11, 4, 'King of comfort', 'Standout for everyday workout', 0, '2025-11-09 11:24:36'),
(14, 2, 14, 4, 'Beautiful dress', 'Exactly matches my personality.', 1, '2025-11-09 17:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `variant_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`variant_id`, `product_id`, `size`, `color`, `stock_quantity`, `sku`) VALUES
(1, 1, 'M', 'White', 0, 'SKU-MEN001-WH-M'),
(3, 2, 'S', 'Blue Floral', 49, 'SKU-WMN001-BL-S'),
(9, 8, 'L', 'Navy', 29, 'SKU-OUT001-NV-L'),
(10, 9, 'M', 'Black', 112, 'SKU-SPR001-BK-M'),
(15, 5, 'US 9', 'Black', 88, 'SKU-SHO001-GR-10'),
(25, 18, 'M', 'Grey', 9, NULL),
(26, 18, 'L', 'Grey', 10, NULL),
(27, 19, 'UK 6.5', 'Cream', 15, NULL),
(28, 19, 'UK 7', 'Cream', 12, NULL),
(29, 19, 'UK 7.5', 'Cream', 13, NULL),
(30, 19, 'UK 8', 'Cream', 10, NULL),
(37, 10, '', 'White', 10, 'VAR10--WHITE'),
(38, 10, '', 'Black', 5, 'VAR10--BLACK'),
(39, 10, '', 'Grey', 5, 'VAR10--GREY'),
(41, 1, 'S', 'Dark Green', 15, 'VAR1-S-DARKG'),
(42, 1, 'M', 'Dark Green', 15, 'VAR1-M-DARKG'),
(43, 1, 'L', 'Dark Green', 15, 'VAR1-L-DARKG'),
(44, 1, 'M', 'Black', 14, 'VAR1-M-BLACK'),
(45, 1, 'L', 'Black', 16, 'VAR1-L-BLACK'),
(46, 1, 'XL', 'Black', 18, 'VAR1-XL-BLACK'),
(47, 2, 'M', 'Blue Floral', 29, 'VAR2-M-BLUEF'),
(48, 2, 'L', 'Blue Floral', 18, 'VAR2-L-BLUEF'),
(49, 18, 'L', 'Black', 10, 'VAR18-L-BLACK'),
(50, 18, 'XL', 'Black', 9, 'VAR18-XL-BLACK'),
(51, 18, 'XXL', 'Black', 10, 'VAR18-XXL-BLACK'),
(52, 3, 'S', 'White', 60, 'VAR3-S-WHITE'),
(53, 3, 'M', 'White', 50, 'VAR3-M-WHITE'),
(54, 4, '', 'Black', 50, 'VAR4--BLACK'),
(55, 5, 'US 10', 'Black', 60, 'VAR5-US10-BLACK'),
(56, 5, 'US 10.5', 'Black', 50, 'VAR5-US10-BLACK-1'),
(57, 5, 'US 11.5', 'Black', 55, 'VAR5-US11-BLACK'),
(58, 6, '', 'Cream', 45, 'VAR6--CREAM'),
(65, 8, 'L', 'Black', 35, 'VAR8-L-BLACK'),
(66, 8, 'XL', 'Navy', 33, 'VAR8-XL-NAVY'),
(67, 8, 'XXL', 'Black', 33, 'VAR8-XXL-BLACK'),
(68, 9, 'L', 'Black', 100, 'VAR9-L-BLACK'),
(77, 7, 'M', 'Original Blue', 50, 'VAR7-M-ORIGIN'),
(78, 7, 'M', 'Light Blue', 50, 'VAR7-M-LIGHT'),
(79, 7, 'L', 'Original Blue', 50, 'VAR7-L-ORIGIN'),
(80, 7, 'L', 'Light Blue', 49, 'VAR7-L-LIGHT'),
(81, 7, 'XL', 'Original Blue', 50, 'VAR7-XL-ORIGIN'),
(82, 7, 'XXL', 'Light Blue', 50, 'VAR7-XXL-LIGHT');

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `promo_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`promo_id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount`, `usage_limit`, `used_count`, `valid_from`, `valid_until`, `is_active`) VALUES
(1, 'WELCOME10', '10% off for new users', 'percentage', 10.00, 0.00, 50.00, 1000, 100, NULL, NULL, 1),
(2, 'FREESHIP', 'Free shipping over $50', 'fixed', 5.99, 50.00, 5.99, 500, 120, NULL, NULL, 1),
(3, 'SUMMER20', '20% off summer collection', 'percentage', 20.00, 30.00, 40.00, 300, 70, NULL, NULL, 1),
(4, 'BAG15', '15% off all bags', 'percentage', 15.00, 0.00, 30.00, 200, 40, NULL, NULL, 1),
(5, 'WINTER25', '25% off outerwear', 'percentage', 25.00, 50.00, 60.00, 150, 20, NULL, NULL, 1),
(6, 'KIDS10', '10% off kids items', 'percentage', 10.00, 0.00, 20.00, 400, 80, NULL, NULL, 1),
(7, 'SPORT5', '5% off sportswear', 'percentage', 5.00, 20.00, 10.00, 250, 60, NULL, NULL, 1),
(8, 'FLASH50', 'Flash sale 50% off', 'percentage', 50.00, 0.00, 100.00, 50, 10, NULL, NULL, 1),
(9, 'LOYALTY15', '15% off for loyal members', 'percentage', 15.00, 0.00, 30.00, 100, 20, NULL, NULL, 1),
(10, 'HOLIDAY25', '25% off sitewide', 'percentage', 25.00, 0.00, 100.00, 200, 0, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_requests`
--

CREATE TABLE `return_requests` (
  `return_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('return','exchange') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_requests`
--

INSERT INTO `return_requests` (`return_id`, `order_id`, `user_id`, `request_type`, `reason`, `status`, `refund_amount`, `created_at`, `updated_at`) VALUES
(1, 13, 11, 'return', 'fds', 'approved', NULL, '2025-11-05 05:37:05', '2025-11-09 17:32:48'),
(2, 14, 11, 'exchange', 'wdd', 'rejected', NULL, '2025-11-05 16:14:02', '2025-11-09 17:32:44'),
(3, 11, 11, 'return', 'not legit', 'pending', NULL, '2025-11-06 18:10:18', '2025-11-06 18:10:18'),
(4, 22, 14, 'return', 'not good', 'completed', NULL, '2025-11-09 16:54:43', '2025-11-09 17:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'Clothing Store', '2025-10-27 04:34:05'),
(2, 'tax_rate', '10', '2025-10-27 04:34:05'),
(3, 'shipping_fee', '5.99', '2025-10-27 04:34:05'),
(4, 'free_shipping_threshold', '50.00', '2025-10-27 04:34:05'),
(5, 'currency', 'USD', '2025-10-27 04:34:05'),
(6, 'currency_symbol', '$', '2025-10-27 04:34:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `social_provider` varchar(50) DEFAULT NULL,
  `social_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `first_name`, `last_name`, `phone`, `loyalty_points`, `wallet_balance`, `created_at`, `updated_at`, `is_active`, `email_verified`, `social_provider`, `social_id`) VALUES
(2, 'jane.smith@example.com', 'hashed_pass', 'Jane', 'Smith', '+15559876543', 300, 50.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(3, 'michael.jones@example.com', 'hashed_pass', 'Michael', 'Jones', '+15552345678', 80, 10.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(4, 'emily.brown@example.com', 'hashed_pass', 'Emily', 'Brown', '+15553456789', 210, 25.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(5, 'kevin.taylor@example.com', 'hashed_pass', 'Kevin', 'Taylor', '+15554567890', 150, 15.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(6, 'lucas.wilson@example.com', 'hashed_pass', 'Lucas', 'Wilson', '+15555678901', 60, 0.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 0, NULL, NULL),
(7, 'maria.miller@example.com', 'hashed_pass', 'Maria', 'Miller', '+15556789012', 190, 80.75, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(8, 'ryan.moore@example.com', 'hashed_pass', 'Ryan', 'Moore', '+15557890123', 400, 99.99, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(9, 'ella.davis@example.com', 'hashed_pass', 'Ella', 'Davis', '+15558901234', 75, 5.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 0, NULL, NULL),
(10, 'sara.anderson@example.com', 'hashed_pass', 'Sara', 'Anderson', '+15559012345', 500, 120.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(11, 'soegyi@gmail.com', '$2y$10$e8ms7M0BI2SVd33Rj1bQ7O3GtsSIzJ8f4FP9rLhIbZM6UTni/af9.', 'Soe', 'Gyi', '2442422424', 883, 22.12, '2025-10-27 05:10:07', '2025-11-09 15:26:54', 1, 0, NULL, NULL),
(14, 'phyumonthae313@gmail.com', '$2y$10$vldgKEP17Tp8jJ3Hkdzm1uptd1EAiz4kb01UG1BwxyTmNd9mpBNam', 'Phyu', 'Mon', '65498489', 143, 183.45, '2025-11-09 16:19:26', '2025-11-09 17:14:05', 1, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('home','work','other') DEFAULT 'home',
  `full_name` varchar(200) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`address_id`, `user_id`, `address_type`, `full_name`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `is_default`, `created_at`) VALUES
(2, 2, 'home', 'Jane Smith', '+15559876543', '456 Elm St', NULL, 'Los Angeles', 'CA', '90001', 'USA', 1, '2025-10-27 05:01:28'),
(3, 3, 'home', 'Michael Jones', '+15552345678', '789 Pine St', NULL, 'Chicago', 'IL', '60601', 'USA', 1, '2025-10-27 05:01:28'),
(4, 4, 'home', 'Emily Brown', '+15553456789', '22 Sunset Blvd', NULL, 'Miami', 'FL', '33101', 'USA', 1, '2025-10-27 05:01:28'),
(5, 5, 'home', 'Kevin Taylor', '+15554567890', '88 Park Ave', NULL, 'Seattle', 'WA', '98101', 'USA', 1, '2025-10-27 05:01:28'),
(6, 6, 'home', 'Lucas Wilson', '+15555678901', '10 Lake Rd', NULL, 'Austin', 'TX', '73301', 'USA', 1, '2025-10-27 05:01:28'),
(7, 7, 'home', 'Maria Miller', '+15556789012', '33 Broadway', NULL, 'Boston', 'MA', '02108', 'USA', 1, '2025-10-27 05:01:28'),
(8, 8, 'home', 'Ryan Moore', '+15557890123', '77 Ocean Dr', NULL, 'San Diego', 'CA', '92101', 'USA', 1, '2025-10-27 05:01:28'),
(9, 9, 'home', 'Ella Davis', '+15558901234', '50 Hill St', NULL, 'Denver', 'CO', '80201', 'USA', 1, '2025-10-27 05:01:28'),
(10, 10, 'home', 'Sara Anderson', '+15559012345', '66 Maple St', NULL, 'Portland', 'OR', '97201', 'USA', 1, '2025-10-27 05:01:28'),
(12, 11, 'home', 'Soe Gyi', '256481984', 'Punnawithi 34/1, Sukhumvit 101 Rd', '', 'Bangkok', 'Yangon', '10260', 'United States', 1, '2025-11-03 08:20:33'),
(14, 14, 'home', 'Phyu Mon', '56484168', '155, 47th Street', '', 'Yangon', 'Yangon', '11101', 'Myanmar', 1, '2025-11-09 16:21:11');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `product_id`, `added_at`) VALUES
(2, 2, 5, '2025-10-27 05:01:28'),
(3, 3, 7, '2025-10-27 05:01:28'),
(4, 4, 9, '2025-10-27 05:01:28'),
(5, 5, 2, '2025-10-27 05:01:28'),
(6, 6, 8, '2025-10-27 05:01:28'),
(8, 8, 10, '2025-10-27 05:01:28'),
(9, 9, 3, '2025-10-27 05:01:28'),
(10, 10, 4, '2025-10-27 05:01:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`subscriber_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shipping_address_id` (`shipping_address_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`variant_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`refund_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=357;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `subscriber_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_requests`
--
ALTER TABLE `return_requests`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_address_id`) REFERENCES `user_addresses` (`address_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD CONSTRAINT `return_requests_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `return_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
