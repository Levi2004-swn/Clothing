-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 05:07 AM
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
(7, 'admin', 'admin@clothingstore.com', 'admin123', 'super_admin', 1, '2025-10-28 09:41:30', '2025-10-27 09:04:20', '2025-10-28 03:11:30');

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
(40, 7, 'update_order_status', 'Updated order #12 to delivered', '::1', '2025-10-28 04:04:55');

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
(1, 1, 'ABC123', '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
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
(1, 1, 1, NULL, 1, 14.39, '2025-10-27 05:01:28'),
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
(4, 'Accessories', NULL, 'accessories', 'Fashion accessories', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(5, 'Shoes', NULL, 'shoes', 'Footwear for all', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(6, 'Outerwear', NULL, 'outerwear', 'Jackets and coats', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(7, 'Sportswear', NULL, 'sportswear', 'Active wear and gym clothing', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(8, 'Underwear', NULL, 'underwear', 'Inner garments', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(9, 'Bags', NULL, 'bags', 'Handbags, backpacks, etc.', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:51:10'),
(10, 'Sales', NULL, 'sale', 'Discounted products', NULL, 1, '2025-10-27 05:01:27', '2025-10-27 18:52:10'),
(12, 'Testing', NULL, '', '', NULL, 1, '2025-10-27 18:52:25', '2025-10-27 18:52:32');

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
(1, 'WELCOME10', 'percentage', 10.00, 0.00, 0.00, 0, 1, '2025-12-31 23:59:59', '2025-10-27 20:04:48', '2025-10-27 20:04:48'),
(2, 'SAVE20', 'percentage', 20.00, 50.00, 20.00, 100, 1, '2025-12-31 23:59:59', '2025-10-27 20:04:48', '2025-10-27 20:04:48'),
(4, 'NEWYEAR25', 'percentage', 25.00, 200.00, 50.00, 200, 1, '2025-12-31 23:59:59', '2025-10-27 20:04:48', '2025-10-27 20:04:48'),
(5, '20CCF7', 'fixed', 100.00, 12.00, 15.00, 12, 1, '2025-10-31 02:35:00', '2025-10-27 20:06:02', '2025-10-27 20:06:02');

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
(11, 'soegyi@gmail.com', '2025-10-27 05:14:15', 1);

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
(1, 1, 'ORD001', 50.00, 5.00, 5.99, 0.00, 60.99, 'ShopeePay', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(2, 2, 'ORD002', 25.00, 2.50, 5.99, 2.00, 31.49, 'Credit Card', 'paid', 'shipped', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(3, 3, 'ORD003', 100.00, 10.00, 0.00, 10.00, 100.00, 'ShopeePay', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(4, 4, 'ORD004', 35.00, 3.50, 5.99, 0.00, 44.49, 'COD', 'pending', 'processing', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(5, 5, 'ORD005', 80.00, 8.00, 5.99, 5.00, 88.99, 'Credit Card', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(6, 6, 'ORD006', 19.99, 1.99, 5.99, 0.00, 27.97, 'ShopeePay', 'paid', 'cancelled', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(7, 7, 'ORD007', 45.00, 4.50, 5.99, 0.00, 55.49, 'ShopeePay', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(8, 8, 'ORD008', 33.00, 3.30, 0.00, 0.00, 36.30, 'Credit Card', 'paid', 'processing', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(9, 9, 'ORD009', 99.99, 9.99, 0.00, 10.00, 99.98, 'ShopeePay', 'paid', 'returned', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(10, 10, 'ORD010', 120.00, 12.00, 0.00, 12.00, 120.00, 'ShopeePay', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-27 05:01:28', '2025-10-27 05:01:28'),
(11, 11, 'ORD-20251027-C90DA6', 30.59, 3.06, 5.99, 0.00, 39.64, 'cod', 'pending', 'delivered', 11, NULL, '', '2025-10-27 05:13:03', '2025-10-27 19:44:51'),
(12, 11, 'ORD-20251028-F7B6A1', 94.00, 9.40, 0.00, 0.00, 103.40, 'cod', 'pending', 'delivered', 11, NULL, '', '2025-10-28 03:44:47', '2025-10-28 04:04:55');

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
(1, 1, 1, NULL, NULL, 2, 14.39, 28.78),
(2, 2, 2, NULL, NULL, 1, 30.59, 30.59),
(3, 3, 5, NULL, NULL, 1, 47.99, 47.99),
(4, 4, 3, NULL, NULL, 1, 23.75, 23.75),
(5, 5, 6, NULL, NULL, 1, 44.99, 44.99),
(6, 6, 9, NULL, NULL, 1, 23.39, 23.39),
(7, 7, 4, NULL, NULL, 1, 19.99, 19.99),
(8, 8, 7, NULL, NULL, 1, 38.25, 38.25),
(9, 9, 8, NULL, NULL, 1, 67.49, 67.49),
(10, 10, 10, NULL, NULL, 2, 12.99, 25.98),
(11, 11, 2, 3, 'Women Floral Dress', 1, 30.59, 30.59),
(12, 12, 5, 6, 'Running Shoes', 2, 47.00, 94.00);

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
(1, 11, '08da0ea1d7240343f6d88fb138774ef219b0c258b1741b850bec4ed86d0cc89f', '2025-10-28 11:11:50', '2025-10-28 03:41:50', 1);

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
(1, 'Men Classic T-Shirt', 'men-classic-tshirt', 'Soft cotton tee for daily wear', 1, 'Uniqlo', 15.99, 10.00, 14.00, 'SKU-MEN001', 1, 1, 1, '2025-10-27 05:01:27', '2025-10-27 09:41:23'),
(2, 'Women Floral Dress', 'women-floral-dress', 'Elegant summer floral dress', 2, 'H&M', 35.99, 15.00, 30.00, 'SKU-WMN001', 1, 1, 1, '2025-10-27 05:01:27', '2025-10-28 03:30:11'),
(3, 'Kids Hoodie', 'kids-hoodie', 'Warm fleece hoodie for kids', 3, 'Zara Kids', 25.00, 5.00, 23.00, 'SKU-KID001', 0, 1, 1, '2025-10-27 05:01:27', '2025-10-28 03:30:56'),
(4, 'Leather Belt', 'leather-belt', 'Genuine brown leather belt', 4, 'Levi’s', 19.99, 0.00, 19.00, 'SKU-ACC001', 0, 0, 1, '2025-10-27 05:01:27', '2025-10-28 03:31:38'),
(5, 'Running Shoes', 'running-shoes', 'Lightweight running shoes', 5, 'Nike', 59.99, 20.00, 47.00, 'SKU-SHO001', 1, 1, 1, '2025-10-27 05:01:27', '2025-10-28 03:32:26'),
(6, 'Women Handbag', 'women-handbag', 'Stylish faux leather handbag', 9, 'Guess', 49.99, 10.00, 44.00, 'SKU-BAG001', 1, 1, 1, '2025-10-27 05:01:27', '2025-10-28 03:33:24'),
(7, 'Men Jeans', 'men-jeans', 'Slim-fit denim jeans', 1, 'Levi’s', 45.00, 15.00, 38.00, 'SKU-MEN002', 0, 1, 1, '2025-10-27 05:01:27', '2025-10-28 03:35:27'),
(8, 'Puffer Jacket', 'puffer-jacket', 'Insulated winter jacket', 6, 'Columbia', 89.99, 25.00, 67.00, 'SKU-OUT001', 1, 0, 1, '2025-10-27 05:01:27', '2025-10-28 03:35:51'),
(9, 'Sports Shorts', 'sports-shorts', 'Quick-dry shorts', 7, 'Adidas', 25.99, 10.00, 23.00, 'SKU-SPR001', 1, 1, 1, '2025-10-27 05:01:27', '2025-10-28 03:36:38'),
(10, 'Socks Pack', 'socks-pack', 'Pack of 5 cotton socks', 8, 'Uniqlo', 12.99, 0.00, 12.00, 'SKU-UND001', 0, 0, 1, '2025-10-27 05:01:27', '2025-10-28 03:37:14');

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
(11, 1, 'uploads/products/product_1_1761558084.jpg', 1, 0),
(12, 2, 'uploads/products/product_2_1761622211.jpg', 1, 0),
(13, 3, 'uploads/products/product_3_1761622256.jpg', 1, 0),
(14, 4, 'uploads/products/product_4_1761622298.jpg', 1, 0),
(15, 5, 'uploads/products/product_5_1761622346.jpg', 1, 0),
(16, 6, 'uploads/products/product_6_1761622404.jpg', 1, 0),
(17, 7, 'uploads/products/product_7_1761622527.jpg', 1, 0),
(18, 8, 'uploads/products/product_8_1761622551.jpg', 1, 0),
(19, 9, 'uploads/products/product_9_1761622598.jpg', 1, 0),
(20, 10, 'uploads/products/product_10_1761622634.jpg', 1, 0);

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
(1, 1, 1, 5, 'Excellent', 'Soft and comfortable', 1, '2025-10-27 05:01:28'),
(2, 2, 2, 4, 'Lovely dress', 'Perfect fit', 1, '2025-10-27 05:01:28'),
(3, 3, 3, 5, 'Great hoodie', 'Warm and comfy', 1, '2025-10-27 05:01:28'),
(4, 4, 4, 3, 'Okay', 'Decent for price', 1, '2025-10-27 05:01:28'),
(5, 5, 5, 5, 'Awesome shoes', 'Lightweight and comfy', 1, '2025-10-27 05:01:28'),
(6, 6, 6, 4, 'Nice bag', 'Good quality material', 1, '2025-10-27 05:01:28'),
(7, 7, 7, 4, 'Stylish jeans', 'Love the color', 1, '2025-10-27 05:01:28'),
(8, 8, 8, 5, 'Warm jacket', 'Perfect for winter', 1, '2025-10-27 05:01:28'),
(9, 9, 9, 4, 'Good shorts', 'Comfortable fabric', 1, '2025-10-27 05:01:28'),
(10, 10, 10, 3, 'Socks ok', 'Average quality', 1, '2025-10-27 05:01:28');

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
(1, 1, 'M', 'White', 100, 'SKU-MEN001-WH-M'),
(2, 1, 'L', 'Black', 80, 'SKU-MEN001-BK-L'),
(3, 2, 'S', 'Blue Floral', 49, 'SKU-WMN001-BL-S'),
(4, 3, 'M', 'Red', 70, 'SKU-KID001-RD-M'),
(5, 4, NULL, 'Brown', 120, 'SKU-ACC001-BR'),
(6, 5, '9', 'Gray', 58, 'SKU-SHO001-GR-9'),
(7, 6, NULL, 'Beige', 40, 'SKU-BAG001-BE'),
(8, 7, '32', 'Denim Blue', 90, 'SKU-MEN002-DB-32'),
(9, 8, 'L', 'Navy', 30, 'SKU-OUT001-NV-L'),
(10, 9, 'M', 'Black', 100, 'SKU-SPR001-BK-M');

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
(1, 'john.doe@example.com', 'hashed_pass', 'John', 'Doe', '+15551234567', 120, 35.50, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(2, 'jane.smith@example.com', 'hashed_pass', 'Jane', 'Smith', '+15559876543', 300, 50.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(3, 'michael.jones@example.com', 'hashed_pass', 'Michael', 'Jones', '+15552345678', 80, 10.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(4, 'emily.brown@example.com', 'hashed_pass', 'Emily', 'Brown', '+15553456789', 210, 25.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(5, 'kevin.taylor@example.com', 'hashed_pass', 'Kevin', 'Taylor', '+15554567890', 150, 15.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(6, 'lucas.wilson@example.com', 'hashed_pass', 'Lucas', 'Wilson', '+15555678901', 60, 0.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 0, NULL, NULL),
(7, 'maria.miller@example.com', 'hashed_pass', 'Maria', 'Miller', '+15556789012', 190, 80.75, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(8, 'ryan.moore@example.com', 'hashed_pass', 'Ryan', 'Moore', '+15557890123', 400, 99.99, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(9, 'ella.davis@example.com', 'hashed_pass', 'Ella', 'Davis', '+15558901234', 75, 5.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 0, NULL, NULL),
(10, 'sara.anderson@example.com', 'hashed_pass', 'Sara', 'Anderson', '+15559012345', 500, 120.00, '2025-10-27 05:01:27', '2025-10-27 05:01:27', 1, 1, NULL, NULL),
(11, 'soegyi@gmail.com', '$2y$10$vb7bR.wy98CFOPDW6SVJe.eFMezUuHQ/rZd4mO46nZMjkyEQ98.3i', 'Soe', 'Gyi', '2442422424', 142, 0.00, '2025-10-27 05:10:07', '2025-10-28 03:44:47', 1, 0, NULL, NULL);

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
(1, 1, 'home', 'John Doe', '+15551234567', '123 Main St', NULL, 'New York', 'NY', '10001', 'USA', 1, '2025-10-27 05:01:28'),
(2, 2, 'home', 'Jane Smith', '+15559876543', '456 Elm St', NULL, 'Los Angeles', 'CA', '90001', 'USA', 1, '2025-10-27 05:01:28'),
(3, 3, 'home', 'Michael Jones', '+15552345678', '789 Pine St', NULL, 'Chicago', 'IL', '60601', 'USA', 1, '2025-10-27 05:01:28'),
(4, 4, 'home', 'Emily Brown', '+15553456789', '22 Sunset Blvd', NULL, 'Miami', 'FL', '33101', 'USA', 1, '2025-10-27 05:01:28'),
(5, 5, 'home', 'Kevin Taylor', '+15554567890', '88 Park Ave', NULL, 'Seattle', 'WA', '98101', 'USA', 1, '2025-10-27 05:01:28'),
(6, 6, 'home', 'Lucas Wilson', '+15555678901', '10 Lake Rd', NULL, 'Austin', 'TX', '73301', 'USA', 1, '2025-10-27 05:01:28'),
(7, 7, 'home', 'Maria Miller', '+15556789012', '33 Broadway', NULL, 'Boston', 'MA', '02108', 'USA', 1, '2025-10-27 05:01:28'),
(8, 8, 'home', 'Ryan Moore', '+15557890123', '77 Ocean Dr', NULL, 'San Diego', 'CA', '92101', 'USA', 1, '2025-10-27 05:01:28'),
(9, 9, 'home', 'Ella Davis', '+15558901234', '50 Hill St', NULL, 'Denver', 'CO', '80201', 'USA', 1, '2025-10-27 05:01:28'),
(10, 10, 'home', 'Sara Anderson', '+15559012345', '66 Maple St', NULL, 'Portland', 'OR', '97201', 'USA', 1, '2025-10-27 05:01:28'),
(11, 11, 'home', 'Soe Gyi', '0988517371', 'Punnawithi 34/1, Sukhumvit 101 Rd', '', 'Bangkok', 'Yangon', '10260', 'United States', 1, '2025-10-27 05:11:26');

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
(1, 1, 6, '2025-10-27 05:01:28'),
(2, 2, 5, '2025-10-27 05:01:28'),
(3, 3, 7, '2025-10-27 05:01:28'),
(4, 4, 9, '2025-10-27 05:01:28'),
(5, 5, 2, '2025-10-27 05:01:28'),
(6, 6, 8, '2025-10-27 05:01:28'),
(7, 7, 1, '2025-10-27 05:01:28'),
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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `subscriber_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
