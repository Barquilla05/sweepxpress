-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2025 at 10:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sweepxpress_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `address` varchar(255) NOT NULL,
  `status` enum('pending','shipped','delivered','cancelled') DEFAULT 'pending',
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `order_id`, `customer_name`, `address`, `status`, `delivery_date`, `created_at`) VALUES
(1, 1, 'BRIAN', 'Unknown', 'delivered', '2025-09-13', '2025-09-08 16:25:19'),
(7, 9, 'beng', 'Unknown', 'cancelled', '2025-09-27', '2025-09-15 14:12:50'),
(8, 10, 'beng', 'llelele', 'pending', '2025-10-16', '2025-10-02 02:09:35'),
(9, 11, 'beng', 'sssssss', 'pending', '2025-11-20', '2025-10-02 02:09:44'),
(10, 12, 'beng', 'nnnn', 'pending', '2025-11-19', '2025-10-02 02:09:51'),
(11, 13, 'beng', 'kkk', 'pending', '2025-11-28', '2025-10-02 02:09:58'),
(12, 18, 'BIBI', '2528 Vision Street Sta cruz Manila', 'cancelled', NULL, '2025-10-14 10:51:09'),
(13, 17, 'BIBI', 'WWWW', 'delivered', NULL, '2025-10-14 11:35:57'),
(14, 15, 'BIBI', 'aaaa', 'pending', NULL, '2025-10-15 15:49:26'),
(15, 21, 'BIBI', 'MNL', 'pending', '2025-10-16', '2025-10-15 15:52:50'),
(16, 29, 'ror roxas', 'JJJJJJJJJJJJJ', 'pending', NULL, '2025-10-21 05:13:25'),
(17, 28, 'ror roxas', 'JJJJJJJJJJJJJJJJJJJ', 'pending', NULL, '2025-10-21 05:13:28'),
(18, 37, 'ror roxas', '', 'pending', NULL, '2025-10-21 06:20:15'),
(19, 36, 'ror roxas', '', 'pending', NULL, '2025-10-21 06:20:25'),
(20, 32, 'ror roxas', '', 'pending', NULL, '2025-10-21 06:20:30'),
(21, 31, 'ror roxas', '', 'pending', NULL, '2025-10-21 06:20:39'),
(22, 38, 'BIBI', '1233, 111, Manila, Manila', 'shipped', NULL, '2025-11-01 11:06:40'),
(23, 39, 'ror roxas', 'rrrrrrrrrrrr', 'pending', NULL, '2025-11-01 11:08:34'),
(24, 40, 'ror roxas', 'sssssssss', 'pending', NULL, '2025-11-01 12:57:38'),
(25, 44, 'IAN PAUL BARQUILLA', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', '', '2025-11-12', '2025-11-12 05:12:26'),
(26, 45, 'Ian paul Barquilla', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', 'delivered', '2025-11-14', '2025-11-12 05:44:08'),
(27, 46, 'IAN PAUL BARQUILLA', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', '', '2025-11-13', '2025-11-12 05:44:14'),
(28, 47, 'IAN PAUL BARQUILLA', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', '', NULL, '2025-11-12 05:55:59'),
(29, 48, 'IAN PAUL BARQUILLA', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', '', '2025-11-12', '2025-11-12 06:07:42'),
(30, 49, 'IAN PAUL BARQUILLA', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', '', '2025-11-14', '2025-11-12 07:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_id`, `location_id`, `quantity`, `updated_at`) VALUES
(1, 5, 1, 1500, '2025-11-03 13:04:34'),
(2, 11, 1, 1011, '2025-11-01 13:23:25'),
(3, 8, 1, 168, '2025-11-12 06:07:33'),
(4, 16, 1, 12, '2025-10-07 06:00:08'),
(5, 7, 1, 206, '2025-11-01 15:24:46'),
(6, 9, 1, 33, '2025-11-12 07:39:01'),
(7, 17, 1, 100, '2025-10-10 10:05:58'),
(8, 12, 1, 4, '2025-10-14 16:28:41'),
(9, 26, 1, 0, '2025-11-03 13:01:20');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`) VALUES
(1, 'Main Warehouse', 'Quezon');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('pending','preparing','delivered','completed','cancellation_requested','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) NOT NULL DEFAULT 'COD',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `customer_name` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `order_type` varchar(10) NOT NULL DEFAULT 'B2C'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `address`, `status`, `created_at`, `payment_method`, `payment_status`, `customer_name`, `notes`, `cancellation_reason`, `admin_note`, `po_number`, `payment_terms`, `order_type`) VALUES
(1, 2, 399.00, NULL, 'delivered', '2025-09-08 16:07:38', 'COD', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 'B2C'),
(9, 5, 800.00, NULL, 'cancelled', '2025-09-15 14:12:12', 'COD', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 'B2C'),
(10, 5, 1350.00, 'llelele', 'pending', '2025-09-22 14:59:18', 'COD', 'Pending', NULL, 'sssss', NULL, NULL, NULL, NULL, 'B2C'),
(11, 5, 3289.00, 'sssssss', 'pending', '2025-09-22 15:07:28', 'Bank', 'Awaiting Payment', NULL, 'eeeee', NULL, NULL, NULL, NULL, 'B2C'),
(12, 5, 2250.00, 'nnnn', 'pending', '2025-09-22 15:15:42', 'Bank', 'Awaiting Payment', NULL, 'nnnnn', NULL, NULL, NULL, NULL, 'B2C'),
(13, 5, 450.00, 'kkk', 'pending', '2025-09-22 15:16:54', 'COD', 'Pending', NULL, 'mmmm', NULL, NULL, NULL, NULL, 'B2C'),
(14, 5, 450.00, 'lk', 'pending', '2025-09-22 15:18:35', 'COD', 'Pending', NULL, 'jj', NULL, NULL, NULL, NULL, 'B2C'),
(15, 5, 600.00, 'aaaa', 'pending', '2025-10-07 08:55:32', 'COD', 'Pending', NULL, 'aaa', NULL, '', NULL, NULL, 'B2C'),
(16, 5, 450.00, 'zzzz', 'cancelled', '2025-10-07 09:01:41', 'COD', 'Pending', NULL, 'zzz', NULL, '', NULL, NULL, 'B2C'),
(17, 5, 498.00, 'WWWW', 'delivered', '2025-10-10 10:00:54', 'COD', 'Pending', NULL, 'WWW', NULL, '', NULL, NULL, 'B2C'),
(18, 5, 2750.00, '2528 Vision Street Sta cruz Manila', 'cancelled', '2025-10-12 12:09:46', 'COD', 'Pending', NULL, 'Pls see my attached order and PO', NULL, '', NULL, NULL, 'B2C'),
(19, 5, 32840.00, 'HJSDHDHDHDHD', 'pending', '2025-10-14 16:25:05', 'COD', 'Pending', NULL, 'RRRRRRRRRRRRRRRRRRRRRRRRR', NULL, NULL, NULL, NULL, 'B2C'),
(20, 5, 220.00, 'jjj', 'pending', '2025-10-15 14:43:35', 'COD', 'Pending', NULL, 'jjj', NULL, NULL, NULL, NULL, 'B2C'),
(21, 5, 600.00, 'MNL', 'pending', '2025-10-15 15:18:37', 'COD', 'Pending', 'Joanna', 'HELLO', NULL, '', NULL, NULL, 'B2C'),
(22, 5, 13860.00, '21111 Vision Street, 321, MANILA, MANILA', 'pending', '2025-10-19 09:42:24', 'Bank', 'Awaiting Payment', 'BIBI', 'iiiiiiiiiiiiiiiiiiii', NULL, NULL, NULL, 'Net 30', 'B2C'),
(23, 5, 1200.00, 'jjjjjjjjjjjjjjjjjjjjjjjjj, jjjjjjjjjjjjjjjjjjjjj, jjjjjjjjjjjjjjjjjjjj, kkkkkkkkkkkkkkkk', 'pending', '2025-10-19 09:43:07', 'COD', 'Pending', 'BIBI', 'jjjjjjjjjjjjjjjjjjjjjjjjjj', NULL, NULL, NULL, 'Net 30', 'B2C'),
(24, 5, 300.00, '21111 Vision Street, 321, MANILA, MANILA', 'pending', '2025-10-19 09:43:41', 'COD', 'Pending', 'BIBI', 'hhhhhhhhhhhhhhhhhhh', NULL, NULL, NULL, 'Net 30', 'B2C'),
(25, 5, 2730.00, '21111 Vision Street, 321, MANILA, MANILA', 'pending', '2025-10-19 14:59:19', 'COD', 'Pending', 'BIBI', 'HAHAHAAHAHA', NULL, NULL, NULL, 'Net 30', 'B2C'),
(26, 5, 1640.00, '21111 Vision Street, 321, MANILA, MANILA', 'pending', '2025-10-19 15:36:46', 'PO', 'Approved', 'BIBI', '11111111111111111', NULL, NULL, '0199288211', 'Net 60', 'B2B'),
(27, 5, 450.00, '21111 Vision Street, 555, MANILA, MANILA', 'pending', '2025-10-19 17:34:20', 'Cebuana', 'Awaiting Payment', 'BIBI MORGAN', 'yyyyyyyyyyyyyyyy', NULL, NULL, NULL, NULL, 'B2C'),
(28, 11, 600.00, 'JJJJJJJJJJJJJJJJJJJ', 'pending', '2025-10-21 05:08:58', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: MAR ROXAS\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: NWOSXNOWND\n------------------------------------', NULL, NULL, NULL, 'Net 60', 'B2B'),
(29, 11, 1200.00, 'JJJJJJJJJJJJJ', 'pending', '2025-10-21 05:10:27', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: JJJJJJJJJJJJJJJJJJJ\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: JJJJJJJJJJJJJJJJ\n------------------------------------', NULL, NULL, NULL, 'Net 60', 'B2B'),
(30, 5, 1200.00, '1233, 111, Manila, Manila', 'pending', '2025-10-21 05:15:19', 'GCash', 'Awaiting Payment', 'BIBI', 'HHHHHHHHHHHHHHHHHH', NULL, '', NULL, NULL, 'B2C'),
(31, 11, 1760.00, '', 'pending', '2025-10-21 05:21:15', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: hhhhhhhhhhhhhhhhhhhhhhh\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: sssssssss\n------------------------------------', NULL, NULL, NULL, 'Net 45', 'B2B'),
(32, 11, 450.00, '', 'pending', '2025-10-21 05:36:59', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: JJJJJJJJJJJJJJJJJJJ\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: SSSSSSSSSSSSSSSSSS\n------------------------------------', NULL, NULL, NULL, 'Net 45', 'B2B'),
(36, 11, 300.00, '', 'pending', '2025-10-21 05:47:48', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: SSSSSSSSSSS\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: SSSSSSSSSSSS\n------------------------------------', NULL, NULL, NULL, 'Net 30', 'B2C'),
(37, 11, 450.00, '', 'pending', '2025-10-21 05:48:29', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: MAR ROXAS\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: AAAAAAAAAA\n------------------------------------', NULL, NULL, NULL, 'Net 45', 'B2C'),
(38, 5, 900.00, '1233, 111, Manila, Manila', '', '2025-11-01 08:20:34', 'GCash', 'Awaiting Payment', 'BIBI', 'nnnnnnnnnnnnnn', NULL, '', NULL, NULL, 'B2C'),
(39, 11, 450.00, 'rrrrrrrrrrrr', 'pending', '2025-11-01 11:08:04', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: SSSSSSSSSSS\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: rrrrrrrrrr\n------------------------------------', NULL, NULL, NULL, 'Net 60', 'B2C'),
(40, 11, 1043.00, 'sssssssss', 'pending', '2025-11-01 12:56:47', 'COD', 'pending', 'ror roxas', '--- PURCHASE ORDER REQUEST DETAILS ---\nBuyer Company Name: MAR ROXAS\nBuyer Contact Name: ror roxas\nBuyer Email: ROXAS1@Gmail.com\nBilling Address: ssssssssssssss\n------------------------------------', NULL, NULL, NULL, 'Net 60', 'B2C'),
(41, 9, 15570.00, '21111 Vision Street, 321, MANILA, MANILA', 'pending', '2025-11-01 13:23:25', 'GCash', 'Awaiting Payment', 'Brian Francisco', 'jjjjjjjjjjjjjjjjjjjj', NULL, NULL, NULL, NULL, 'B2C'),
(42, 10, 600.00, '1233, 111, Manila, Manila', 'pending', '2025-11-01 13:26:56', 'PAYMAYA', 'Awaiting Payment', 'Brian Paul Royo Francisco', '11111', NULL, '', NULL, NULL, 'B2C'),
(43, 9, 1410.00, '21111 Vision Street, jjjjjjjjj, MANILA, MANILA', 'pending', '2025-11-01 15:24:46', 'COD', 'Pending', 'Brian Francisco', 'hhhhhhhhhhh', NULL, '', NULL, NULL, 'B2C'),
(44, 12, 1300.00, '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', 'completed', '2025-11-12 05:11:00', 'COD', 'Pending', 'IAN PAUL BARQUILLA', '', NULL, NULL, NULL, NULL, 'B2C'),
(45, 14, 1300.00, '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', 'delivered', '2025-11-12 05:23:27', 'COD', 'Pending', 'Ian paul Barquilla', '', NULL, NULL, NULL, NULL, 'B2C'),
(46, 12, 1100.00, '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', 'completed', '2025-11-12 05:43:59', 'COD', 'Pending', 'IAN PAUL BARQUILLA', '', NULL, NULL, NULL, NULL, 'B2C'),
(47, 12, 1300.00, '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', 'completed', '2025-11-12 05:55:47', 'COD', 'Pending', 'IAN PAUL BARQUILLA', '', NULL, NULL, NULL, NULL, 'B2C'),
(48, 12, 300.00, '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', 'completed', '2025-11-12 06:07:33', 'COD', 'Pending', 'IAN PAUL BARQUILLA', '', NULL, NULL, NULL, NULL, 'B2C'),
(49, 12, 150.00, '98 JASMIN ST ROXAS DISTRICT QUEZON CITY, Roxas, Quezon city, Metro Manila', 'completed', '2025-11-12 07:39:01', 'COD', 'Pending', 'IAN PAUL BARQUILLA', '', NULL, NULL, NULL, NULL, 'B2C');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 1, 399.00),
(26, 9, 7, 2, 100.00),
(27, 9, 8, 1, 300.00),
(28, 9, 9, 1, 150.00),
(29, 9, 10, 1, 150.00),
(30, 10, 9, 4, 150.00),
(31, 10, 10, 5, 150.00),
(32, 11, 4, 11, 299.00),
(33, 12, 9, 15, 150.00),
(34, 13, 9, 3, 150.00),
(35, 14, 9, 3, 150.00),
(36, 15, 8, 1, 300.00),
(37, 15, 9, 1, 150.00),
(38, 15, 10, 1, 150.00),
(39, 16, 9, 3, 150.00),
(40, 17, 12, 1, 149.00),
(41, 17, 13, 1, 149.00),
(42, 17, 14, 1, 200.00),
(43, 18, 7, 20, 100.00),
(44, 18, 15, 5, 90.00),
(45, 18, 18, 5, 60.00),
(46, 19, 15, 4, 90.00),
(47, 19, 16, 1, 180.00),
(48, 19, 17, 2, 300.00),
(49, 19, 21, 7, 1100.00),
(50, 19, 22, 16, 1500.00),
(51, 20, 7, 2, 110.00),
(52, 21, 9, 3, 150.00),
(53, 21, 10, 1, 150.00),
(54, 22, 7, 6, 110.00),
(55, 22, 26, 11, 1200.00),
(56, 23, 26, 1, 1200.00),
(57, 24, 8, 1, 300.00),
(58, 25, 7, 3, 110.00),
(59, 25, 26, 2, 1200.00),
(60, 26, 7, 4, 110.00),
(61, 26, 8, 4, 300.00),
(62, 27, 8, 1, 300.00),
(63, 27, 9, 1, 150.00),
(64, 28, 8, 2, 300.00),
(65, 29, 26, 1, 1200.00),
(66, 30, 26, 1, 1200.00),
(67, 31, 7, 1, 110.00),
(68, 31, 8, 1, 300.00),
(69, 31, 9, 1, 150.00),
(70, 31, 26, 1, 1200.00),
(71, 32, 8, 1, 300.00),
(72, 32, 9, 1, 150.00),
(74, 36, 8, 1, 300.00),
(75, 37, 8, 1, 300.00),
(76, 37, 9, 1, 150.00),
(77, 38, 8, 1, 300.00),
(78, 38, 9, 4, 150.00),
(79, 39, 8, 1, 300.00),
(80, 39, 9, 1, 150.00),
(81, 40, 11, 6, 149.00),
(82, 40, 12, 1, 149.00),
(83, 41, 7, 2, 110.00),
(84, 41, 8, 1, 300.00),
(85, 41, 9, 1, 150.00),
(86, 41, 11, 100, 149.00),
(87, 42, 9, 4, 150.00),
(88, 43, 7, 1, 110.00),
(89, 43, 26, 1, 1300.00),
(90, 44, 26, 1, 1300.00),
(91, 45, 26, 1, 1300.00),
(92, 46, 21, 1, 1100.00),
(93, 47, 26, 1, 1300.00),
(94, 48, 8, 1, 300.00),
(95, 49, 9, 1, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `sku`, `description`, `price`, `image`, `image_path`, `created_at`, `stock`, `updated_at`) VALUES
(1, 'All-Purpose Cleaner', 'Cleaning Supplies', 'APC-001', 'Powerful degreaser for floors and counters.', 149.00, NULL, '/sweepxpress/assets/sample_cleaner.jpg', '2025-09-06 14:52:25', 0, NULL),
(2, 'Microfiber Mop', 'Equipment', 'MOP-002', 'Washable head, lightweight aluminum handle.', 399.00, NULL, '/sweepxpress/assets/sample_mop.jpg', '2025-09-06 14:52:25', 0, NULL),
(3, 'Packaging Tape', 'Tapes & Adhesives', 'PACK-003', 'Heavy-duty 2-inch clear tape for boxes.', 89.00, NULL, '/sweepxpress/assets/sample_tape.jpg', '2025-09-06 14:52:25', 0, NULL),
(4, 'Latex Gloves (100 pcs)', 'Cleaning Supplies', 'GLV100-004', 'Powder-free gloves for household cleaning.', 299.00, NULL, '/sweepxpress/assets/sample_gloves.jpg', '2025-09-06 14:52:25', 0, NULL),
(5, '3M Duct Tape', 'Tapes & Adhesives', '3MDT-005', 'Just a tape', 99.00, NULL, NULL, '2025-09-15 05:01:44', 0, NULL),
(7, '3M Scotch Brite', 'Cleaning Supplies', 'SB-007', 'a brand of cleaning and surface conditioning products, known for its non-woven abrasive pads and sponges made from synthetic fibers infused with abrasives like aluminum oxide or silicon carbide.', 110.00, NULL, '/sweepxpress/assets/3M Scotch Brite.jpg', '2025-09-15 09:21:09', 1, NULL),
(8, '3M General Purpose Adhesive Cleaner', 'Cleaning Supplies', '3MAC-008', 'a solvent-based product designed to dissolve and remove sticky adhesive residue, grease, oil, wax, tar, and light paint overspray from various surfaces, including cured automotive paint, vinyl, and fabrics.', 300.00, NULL, '/sweepxpress/assets/3M General Purpose Adhesive Cleaner.jpg', '2025-09-15 09:21:09', 0, NULL),
(9, '3M Gloves', NULL, '3MGLV-009', 'washable work gloves offering good dexterity, breathability, and grip for light to medium-duty tasks like material handling, small parts assembly, and general construction, featuring a nitrile or polyurethane foam-coated palm and a knit wrist cuff for comfort and a secure fit.', 150.00, NULL, '/sweepxpress/assets/3M Gloves.jpg', '2025-09-15 09:21:09', 0, NULL),
(10, '3M Sharpshoote No Rinse Mark Remover', 'Cleaning Supplies', '3MSM-010', 'a ready-to-use, extra-strength cleaner designed to remove difficult stains, spots, and grease from a wide variety of hard, washable surfaces without requiring rinsing.', 150.00, NULL, '/sweepxpress/assets/3M Sharpshoote No Rinse Mark Remover.jpg', '2025-09-15 09:21:09', 0, NULL),
(11, '3M Duct Tapes', 'Tapes & Adhesives', '3MDT2-011', 'a durable, cloth-backed tape with a strong adhesive designed for general-purpose applications like bundling, sealing, and repairs, and it comes in different strengths and features, such as water resistance and hand-tearability.', 149.00, NULL, '/sweepxpress/assets/3M Duct Tapes.jpg', '2025-09-15 09:21:09', 0, NULL),
(12, '3M Sanitizer Concentrate', 'Cleaning Supplies', '3MSAN-012', 'an EPA-registered, concentrated liquid sanitizer for hard, non-porous surfaces, including food-contact surfaces in commercial settings.', 149.00, NULL, '/sweepxpress/assets/3M Sanitizer Concentrate.jpg', '2025-09-15 09:21:09', 0, NULL),
(13, 'Scotch-Brite Quick Clean Griddle Starting Kit', 'Cleaning Supplies', 'SBQK-013', 'a complete set designed for fast, safe, and efficient cleaning of hot commercial griddles, using a powerful, Green Seal™-certified liquid cleaner to remove burnt-on food soil without strong odors or caustic soda.', 149.00, NULL, '/sweepxpress/assets/Scotch-Brite Quick Clean Griddle Starting Kit.jpg', '2025-09-15 09:21:09', 0, NULL),
(14, '3M Solution Tablets', 'Cleaning Supplies', '3MTBL-014', 'EPA-registered, concentrated disinfectant tablets that, when dissolved in water, create a solution to kill a broad spectrum of microbes, including Clostridioides difficile (C. diff) spores, Norovirus, SARS-CoV-2, and various bacteria, on hard, non-porous surfaces.', 200.00, NULL, '/sweepxpress/assets/3M Solution Tablets.jpg', '2025-09-15 09:21:09', 0, NULL),
(15, '3M Spray Buff', 'Cleaning Supplies', '3MSB-015', 'a milky-white, ready-to-use emulsion designed for spray buffing operations to quickly clean, polish, and restore the luster of floor finishes, especially 3M and high-quality synthetic finishes.', 90.00, NULL, '/sweepxpress/assets/3M Spray Buff.jpg', '2025-09-15 09:21:09', 0, NULL),
(16, '3M Stainless Steel Cleaner and Polish', 'Cleaning Supplies', '3MSS-016', 'a ready-to-use aerosol product designed to clean and polish metal surfaces in a single step, leaving a high-gloss, streak-free finish.', 180.00, NULL, '/sweepxpress/assets/3M Stainless Steel Cleaner and Polish.jpg', '2025-09-15 09:21:09', 0, NULL),
(17, '3M Super Shine', 'Cleaning Supplies', '3MSSH-017', 'a floor finish that creates a durable, high-gloss, and protective layer on various hard floors, including ceramic tiles, vinyl, rubber, and terrazzo.', 300.00, NULL, '/sweepxpress/assets/3M Super Shine.jpg', '2025-09-15 09:21:09', 0, NULL),
(18, '3M White Super Polish Pad 4100', 'Cleaning Supplies', '3MPD-018', 'a fine-grade, white, non-woven polyester fiber pad designed for light cleaning, buffing soft finishes, and polishing soft waxes on wood or other protected floors.', 60.00, NULL, '/sweepxpress/assets/3M White Super Polish Pad 4100.jpg', '2025-09-15 09:21:09', 0, NULL),
(19, '3m-scotchgard-stone-floor-protector-plus-3-785-liter-bag', 'Floor Mats', 'SGSP-019', 'a high-performance solution that hardens, seals, and protects porous stone floors like concrete, marble, and terrazzo by creating a durable, glossy, and scuff-resistant surface.', 160.00, NULL, '/sweepxpress/assets/3m-scotchgard-stone-floor-protector-plus-3-785-liter-bag.avif', '2025-09-15 09:21:09', 0, NULL),
(20, '3M™ Nomad™ Scraper Matting 7150, Light Green', 'Floor Mats', 'NMS-020', 'Durable vinyl-loops scrape, trap and hide dirt and moisture, minimizing re-tracking into the building', 20.00, NULL, '/sweepxpress/assets/3M™ Nomad™ Scraper Matting 7150, Light Green.jpg', '2025-09-15 09:21:09', 0, NULL),
(21, 'Karchar K 3 Power Control', 'Equipment', 'KARCH-021', 'a mid-range electric pressure washer designed for home and garden use, featuring a Power Control spray gun with an LED display for selecting and monitoring pressure levels.', 1100.00, NULL, '/sweepxpress/assets/Karchar K 3 Power Control.jpg', '2025-09-15 09:21:09', 0, NULL),
(22, 'Karcher Bag-Less Powerful Vacuum Cleaner', 'Equipment', 'KARCHV-022', 'provide consistent, high suction with multi-cyclone technology, eliminating the need for filter bags.', 1500.00, NULL, '/sweepxpress/assets/Karcher Bag-Less Powerful Vacuum Cleaner.jpg', '2025-09-15 09:21:09', 0, NULL),
(23, 'Plain 3M Nomad Z Web Mat', 'Floor Mats', 'NMZW-023', 'a durable, all-vinyl floor mat having an open, continuously patterned surface.', 50.00, NULL, '/sweepxpress/assets/Plain 3M Nomad Z Web Mat.webp', '2025-09-15 09:21:09', 0, NULL),
(24, 'Scotch Brite Ultra Fine Hand Sanding', 'Tools & Accessories', 'SBUF-024', 'a load-resistant, non-woven abrasive pad that uses silicon carbide to achieve a fine, uniform finish, effectively replacing steel wool without the risks of rust, splintering, or shredding.', 20.00, NULL, '/sweepxpress/assets/Scotch Brite Ultra Fine Hand Sanding.jpg', '2025-09-15 09:21:09', 0, NULL),
(25, 'ScotchBrite Grout Brush', 'Tools & Accessories', 'SBGB-025', 'Easily clean in nooks and crannies with the Scotch-Brite Grout & Detail Brush. Its powerful non-scratch bristles are safe on grout, tile, bathroom fixtures, faucets, drains, and more! The Scotch-Brite® Grout & Detail Brush features antimicrobial bristle protection* that works to prevent bacterial odors. Get the most out of this durable, reusable brush with a thorough cleaning after use.', 70.00, NULL, '/sweepxpress/assets/ScotchBrite Grout Brush.jpg', '2025-09-15 09:21:09', 0, NULL),
(26, '3M Carpet Map', 'Floor Mats', '3MCM-026', 'A 3M carpet mat is an entrance mat designed to trap dirt and moisture, protecting indoor floors. These mats use dual-fiber construction (often nylon and polypropylene) to scrape, hide, and absorb dirt and water from footwear, keeping the interior clean and safe. Key features include durability for high-traffic areas, non-slip backing to prevent movement, and an easy-to-clean design that typically involves regular vacuuming', 1300.00, NULL, '/sweepxpress/uploads/1762169135_6908912f37889.jpg', '2025-10-15 16:38:09', 0, NULL),
(36, 'Ian Paul Barquilla', 'Equipment', 'IPB-6381', 'QSALKFDJSDFUJHSDG', 999999.00, NULL, '/sweepxpress/uploads/1762940455_1594147414402.jfif', '2025-11-12 09:40:55', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_stock_history`
--

CREATE TABLE `product_stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `action` enum('IN','OUT','ADJUST') NOT NULL,
  `quantity` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_stock_history`
--

INSERT INTO `product_stock_history` (`id`, `product_id`, `action`, `quantity`, `note`, `created_at`) VALUES
(1, 7, 'OUT', 6, 'Order #22 checkout', '2025-10-19 09:42:24'),
(2, 8, 'OUT', 1, 'Order #24 checkout', '2025-10-19 09:43:41'),
(3, 7, 'OUT', 3, 'Order #25 checkout', '2025-10-19 14:59:19'),
(4, 7, 'OUT', 4, 'Order #26 checkout', '2025-10-19 15:36:46'),
(5, 8, 'OUT', 4, 'Order #26 checkout', '2025-10-19 15:36:46'),
(6, 8, 'OUT', 1, 'Order #27 checkout', '2025-10-19 17:34:20'),
(7, 9, 'OUT', 1, 'Order #27 checkout', '2025-10-19 17:34:20'),
(8, 8, 'OUT', 1, 'Order #38 checkout', '2025-11-01 08:20:34'),
(9, 9, 'OUT', 4, 'Order #38 checkout', '2025-11-01 08:20:34'),
(10, 7, 'OUT', 2, 'Order #41 checkout', '2025-11-01 13:23:25'),
(11, 8, 'OUT', 1, 'Order #41 checkout', '2025-11-01 13:23:25'),
(12, 9, 'OUT', 1, 'Order #41 checkout', '2025-11-01 13:23:25'),
(13, 11, 'OUT', 100, 'Order #41 checkout', '2025-11-01 13:23:25'),
(14, 9, 'OUT', 4, 'Order #42 checkout', '2025-11-01 13:26:56'),
(15, 7, 'OUT', 1, 'Order #43 checkout', '2025-11-01 15:24:46'),
(16, 26, 'OUT', 1, 'Order #44 checkout', '2025-11-12 05:11:00'),
(17, 26, 'OUT', 1, 'Order #45 checkout', '2025-11-12 05:23:27'),
(18, 26, 'OUT', 1, 'Order #47 checkout', '2025-11-12 05:55:47'),
(19, 8, 'OUT', 1, 'Order #48 checkout', '2025-11-12 06:07:33'),
(20, 9, 'OUT', 1, 'Order #49 checkout', '2025-11-12 07:39:01');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `movement_type` enum('IN','OUT','ADJUST') NOT NULL,
  `quantity` int(11) NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `user_name` varchar(120) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `location_id`, `movement_type`, `quantity`, `remark`, `user_name`, `created_by`, `created_at`) VALUES
(1, 5, 1, 'IN', 100, '', 'BRIAN', NULL, '2025-10-07 03:33:58'),
(2, 5, 1, 'ADJUST', 1, 'Broken', 'BRIAN', NULL, '2025-10-07 03:34:18'),
(3, 5, 1, 'IN', 300, '', 'BRIAN', NULL, '2025-10-07 03:35:09'),
(4, 5, 1, 'ADJUST', 100, 'brokem', 'BRIAN', NULL, '2025-10-07 03:35:35'),
(5, 11, 1, 'IN', 1111, '', 'BRIAN', NULL, '2025-10-07 05:33:30'),
(6, 5, 1, 'IN', 111, 'ssss', 'BRIAN', NULL, '2025-10-07 05:45:51'),
(7, 8, 1, 'IN', 111, 'bb', 'BRIAN', NULL, '2025-10-07 05:46:19'),
(8, 16, 1, 'IN', 12, 'FROM 3M', 'BRIAN', NULL, '2025-10-07 06:00:08'),
(9, 7, 1, 'IN', 122, 'DO', 'BRIAN', NULL, '2025-10-07 06:00:36'),
(10, 5, 1, 'IN', 11111, '111', 'BRIAN', NULL, '2025-10-07 06:11:11'),
(11, 8, 1, 'IN', 55, '3m', 'BRIAN', NULL, '2025-10-07 06:12:11'),
(12, 5, 1, 'ADJUST', 3, '[DEDUCT] broken', 'BRIAN', NULL, '2025-10-07 06:12:57'),
(13, 5, 1, 'OUT', 10000, 'gen', 'BRIAN', NULL, '2025-10-07 06:15:13'),
(14, 9, 1, 'IN', 11, '3M', 'BRIAN', NULL, '2025-10-07 08:39:12'),
(15, 8, 1, 'IN', 11, 'hiii', 'BRIAN', NULL, '2025-10-07 08:42:24'),
(16, 5, 1, 'ADJUST', 1, '[DEDUCT] low', 'BRIAN', NULL, '2025-10-07 08:48:39'),
(17, 5, 1, 'IN', 1, 'aaa', 'BRIAN', NULL, '2025-10-07 08:59:45'),
(18, 5, 1, 'ADJUST', 1, '[DEDUCT] HIIII', 'Brian', NULL, '2025-10-07 09:23:41'),
(19, 5, 1, 'IN', 11, 'HTNL', 'Admin', NULL, '2025-10-07 09:28:58'),
(20, 7, 1, 'IN', 100, '3M', 'Admin', NULL, '2025-10-10 09:54:00'),
(21, 17, 1, 'IN', 100, '3M', 'Admin', NULL, '2025-10-10 10:05:58'),
(22, 5, 1, 'ADJUST', 10, '[DEDUCT] SIRA', 'Admin', NULL, '2025-10-10 10:06:36'),
(23, 9, 1, 'IN', 20, '3M', 'Admin', NULL, '2025-10-11 05:43:30'),
(24, 9, 1, 'ADJUST', 1, '[DEDUCT] BUTAS', 'Admin', NULL, '2025-10-11 05:44:27'),
(25, 5, 1, 'ADJUST', 1, '[DEDUCT] sira', 'Admin', NULL, '2025-10-11 07:02:11'),
(26, 5, 1, 'IN', 10, '3m', 'Admin', NULL, '2025-10-11 07:02:37'),
(27, 5, 1, 'ADJUST', 1, '[DEDUCT] Sira', 'Admin', NULL, '2025-10-11 07:02:57'),
(28, 9, 1, 'IN', 20, '3M', 'Admin', NULL, '2025-10-12 12:02:09'),
(29, 9, 1, 'ADJUST', 2, '[DEDUCT] Damaged', 'Admin', NULL, '2025-10-12 12:04:16'),
(30, 9, 1, 'ADJUST', 2, '[DEDUCT] Damaged', 'Admin', NULL, '2025-10-12 12:04:16'),
(31, 9, 1, 'ADJUST', 2, '[DEDUCT] damaged', 'Admin', NULL, '2025-10-12 12:04:42'),
(32, 12, 1, 'IN', 4, '3M', 'Admin', NULL, '2025-10-14 16:28:41'),
(33, 7, 1, 'OUT', 6, 'Order #22 checkout', 'BIBI', 'BIBI', '2025-10-19 09:42:24'),
(34, 8, 1, 'OUT', 1, 'Order #24 checkout', 'BIBI', 'BIBI', '2025-10-19 09:43:41'),
(35, 7, 1, 'OUT', 3, 'Order #25 checkout', 'BIBI', 'BIBI', '2025-10-19 14:59:19'),
(36, 7, 1, 'OUT', 4, 'Order #26 checkout', 'BIBI', 'BIBI', '2025-10-19 15:36:46'),
(37, 8, 1, 'OUT', 4, 'Order #26 checkout', 'BIBI', 'BIBI', '2025-10-19 15:36:47'),
(38, 8, 1, 'OUT', 1, 'Order #27 checkout', 'BIBI', 'BIBI', '2025-10-19 17:34:20'),
(39, 9, 1, 'OUT', 1, 'Order #27 checkout', 'BIBI', 'BIBI', '2025-10-19 17:34:20'),
(40, 8, 1, 'OUT', 1, 'Order #38 checkout', 'BIBI', 'BIBI', '2025-11-01 08:20:34'),
(41, 9, 1, 'OUT', 4, 'Order #38 checkout', 'BIBI', 'BIBI', '2025-11-01 08:20:34'),
(42, 5, 1, 'ADJUST', 20, '[DEDUCT] dmg', 'Admin', NULL, '2025-11-01 12:55:02'),
(43, 7, 1, 'OUT', 2, 'Order #41 checkout', 'Brian Francisco', 'Brian Francisco', '2025-11-01 13:23:25'),
(44, 8, 1, 'OUT', 1, 'Order #41 checkout', 'Brian Francisco', 'Brian Francisco', '2025-11-01 13:23:25'),
(45, 9, 1, 'OUT', 1, 'Order #41 checkout', 'Brian Francisco', 'Brian Francisco', '2025-11-01 13:23:25'),
(46, 11, 1, 'OUT', 100, 'Order #41 checkout', 'Brian Francisco', 'Brian Francisco', '2025-11-01 13:23:25'),
(47, 9, 1, 'OUT', 4, 'Order #42 checkout', '[NTC-S] Brian Paul Royo Francisco', '[NTC-S] Brian Paul Royo Francisco', '2025-11-01 13:26:56'),
(48, 7, 1, 'OUT', 1, 'Order #43 checkout', 'Brian Francisco', 'Brian Francisco', '2025-11-01 15:24:46'),
(49, 26, 1, 'IN', 111111111, '3M', 'Admin', NULL, '2025-11-03 12:59:01'),
(50, 26, 1, 'OUT', 1111111, '', 'Admin', NULL, '2025-11-03 12:59:52'),
(51, 26, 1, 'OUT', 11, '', 'Admin', NULL, '2025-11-03 13:00:16'),
(52, 26, 1, 'ADJUST', 109999989, '[DEDUCT] SOBRA', 'Admin', NULL, '2025-11-03 13:01:20'),
(53, 5, 1, 'OUT', 6, '', 'Admin', NULL, '2025-11-03 13:04:34'),
(54, 26, 1, 'OUT', 1, 'Order #44 checkout', 'IAN PAUL BARQUILLA', 'IAN PAUL BARQUILLA', '2025-11-12 05:11:00'),
(55, 26, 1, 'OUT', 1, 'Order #45 checkout', 'Ian paul Barquilla', 'Ian paul Barquilla', '2025-11-12 05:23:27'),
(56, 26, 1, 'OUT', 1, 'Order #47 checkout', 'IAN PAUL BARQUILLA', 'IAN PAUL BARQUILLA', '2025-11-12 05:55:47'),
(57, 8, 1, 'OUT', 1, 'Order #48 checkout', 'IAN PAUL BARQUILLA', 'IAN PAUL BARQUILLA', '2025-11-12 06:07:33'),
(58, 9, 1, 'OUT', 1, 'Order #49 checkout', 'IAN PAUL BARQUILLA', 'IAN PAUL BARQUILLA', '2025-11-12 07:39:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin','business') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `dark_mode` tinyint(1) DEFAULT 0,
  `birth_date` date DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `name`, `username`, `email`, `password_hash`, `profile_image`, `role`, `created_at`, `phone`, `dark_mode`, `birth_date`, `street_address`, `city`, `zip_code`, `phone_number`, `gender`) VALUES
(1, NULL, 'Admin', '', 'admin@sx.local', '$2y$10$97C0lN5nIyk7VJ5vUQHcYOmMZ2jCvBRKv8vujwES1jM2xZ2z8yZvy', NULL, 'admin', '2025-09-06 14:52:25', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(2, NULL, 'BRIAN', '', 'brian62@gmail.com', '$2y$10$.X99YppdGcBO8o.spUhTE.n2TZggTNPN1Ug77w1GeAaO9NuKouUrm', '/sweepxpress/uploads/profile_2_1762009131.jpg', 'admin', '2025-09-06 15:31:41', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, 'BRIAN', '', 'brian67@gmail.com', '$2y$10$dCucJfXPYqC7w5yIYtpP.ukSZ2yL71Wy3lLW8ym.hvTP22c9zMauC', NULL, 'customer', '2025-09-06 15:46:02', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(5, NULL, 'BIBI', '', 'brian61@gmail.com', '$2y$10$/MGIinkV.PorGJ9w97xC2uGN.JRdV4IPyRW1BRWnbXAMnGJqq7tMW', '/sweepxpress/uploads/profiles/1760416060_10a21fd7478c90915f00c8a6122735c9.jpg', 'customer', '2025-09-14 14:49:31', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(6, NULL, 'Admin User', '', 'admin@sweepxpress.com', '$2y$10$L9l4Tk6whxGqVZ2U1k7/auuE7nTZcY84XJ3C8f6zvbbtQkl7Agx3u', NULL, 'admin', '2025-09-16 03:02:36', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(7, NULL, 'Bryz', '', 'brian63@gmail.com', '$2y$10$X0qGgGXMwxeotkxoplW2YuxZ/1mM5NcCM7yG.xI0/adUa/8NIofpW', NULL, 'customer', '2025-09-20 15:09:38', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(9, '116798107768038634331', 'Brian Francisco', '', 'brianfrancisco102003@gmail.com', '', '/sweepxpress/uploads/profiles/1762168559_956372b1b403fd1969b995c4de748964.jpg', 'customer', '2025-10-04 14:45:21', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(10, '107546182234256110608', '[NTC-S] Brian Paul Royo Francisco', '', '422000909@ntc.edu.ph', '', NULL, 'customer', '2025-10-07 02:44:12', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(11, NULL, 'ror roxas', '', 'ROXAS1@Gmail.com', '$2y$10$vBfLyIF.X7eftg1hcYeD4.J7yCpYWQ1TLxsow6tqn78WG4ow2UG6y', NULL, 'business', '2025-10-21 04:17:26', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(12, '118145100184117452809', 'IAN PAUL BARQUILLA', 'BARQUILLA05', 'ianpaul.barquilla2001@gmail.com', '', '/sweepxpress/uploads/profiles/1762924817_f93b3ddb55eb28ee9b369588793751c8.jpg', '', '2025-11-12 05:10:45', NULL, 0, '0000-00-00', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY', 'Quezon city', '1103', '9155930658', 'Male'),
(13, NULL, 'mama blue', '', 'admin@gmail.com', '$2y$10$t0r77QOoqRNHGzgawoHgE.SmPOuiQIxfXwMSkkBOM9ped0jvNrLlS', NULL, 'admin', '2025-11-12 05:11:21', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(14, NULL, 'Ian paul Barquilla', 'BARQUILLA05', 'user@gmail.com', '$2y$10$lLOelFUunhapu.e5OsyMPOR5ysQY7XvrVghtecgPAGMIAWVf40RGS', '/sweepxpress/uploads/profiles/1762924979_c36675e6947a5851817794287090a641.jpg', 'customer', '2025-11-12 05:20:46', NULL, 0, '2001-05-05', '98 JASMIN ST ROXAS DISTRICT QUEZON CITY', 'Quezon city', '1103', '9155930658', 'Male');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_location_unique` (`product_id`,`location_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_stock_history`
--
ALTER TABLE `product_stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_stock_history`
--
ALTER TABLE `product_stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_stock_history`
--
ALTER TABLE `product_stock_history`
  ADD CONSTRAINT `product_stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
