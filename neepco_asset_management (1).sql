-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 01:04 PM
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
-- Database: `neepco_asset_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purchase_order_number` varchar(255) DEFAULT NULL,
  `asset_condition` varchar(255) DEFAULT NULL,
  `warranty_start_date` date DEFAULT NULL,
  `warranty_expiry_date` date DEFAULT NULL,
  `warranty_provider` varchar(255) DEFAULT NULL,
  `warranty_details` text DEFAULT NULL,
  `depreciation_method` varchar(255) DEFAULT NULL,
  `expected_lifetime_years` int(11) DEFAULT NULL,
  `salvage_value` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT NULL,
  `depreciation_start_date` date DEFAULT NULL,
  `depreciation_frequency` varchar(255) DEFAULT NULL,
  `insurer_company` varchar(255) DEFAULT NULL,
  `policy_number` varchar(255) DEFAULT NULL,
  `insurance_start_date` date DEFAULT NULL,
  `insurance_end_date` date DEFAULT NULL,
  `insurance_amount` decimal(15,2) DEFAULT NULL,
  `insurance_contact_person` varchar(255) DEFAULT NULL,
  `insurance_contact_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_code`, `name`, `description`, `category_id`, `location_id`, `department_id`, `status`, `manufacturer`, `model`, `serial_number`, `purchase_date`, `purchase_cost`, `supplier_id`, `purchase_order_number`, `asset_condition`, `warranty_start_date`, `warranty_expiry_date`, `warranty_provider`, `warranty_details`, `depreciation_method`, `expected_lifetime_years`, `salvage_value`, `current_value`, `depreciation_rate`, `depreciation_start_date`, `depreciation_frequency`, `insurer_company`, `policy_number`, `insurance_start_date`, `insurance_end_date`, `insurance_amount`, `insurance_contact_person`, `insurance_contact_number`, `notes`, `assigned_to`, `assigned_at`, `created_by`, `updated_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'AST-001', 'Dell OptiPlex 7080', 'Office desktop computer', 1, 1, 3, 'In Use', 'Dell', 'OptiPlex 7080', 'DEL12345678', '2024-01-15', 850.00, 1, NULL, 'Excellent', '2024-01-15', '2026-01-15', 'Dell India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-21 09:00:00', '2025-05-21 09:00:00'),
(2, 'AST-002', 'HP LaserJet Pro M404n', 'Office printer', 3, 1, 3, 'In Use', 'HP', 'LaserJet Pro M404n', 'HP98765432', '2024-02-10', 450.00, 2, NULL, 'Good', '2024-02-10', '2025-02-10', 'HP India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-21 09:00:00', '2025-05-21 09:00:00'),
(3, 'AST-003', 'Dell UltraSharp 27\" Monitor', '27-inch 4K monitor', 2, 1, 3, 'In Use', 'Dell', 'U2720Q', 'DELU2720Q123', '2024-01-20', 600.00, 1, NULL, 'Excellent', '2024-01-20', '2026-01-20', 'Dell India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-21 09:00:00', '2025-05-21 09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `asset_categories`
--

INSERT INTO `asset_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Computer', 'Desktop computers, laptops, and workstations', '2025-05-21 08:45:17', '2025-05-21 08:45:17'),
(2, 'Monitor', 'Computer monitors and displays', '2025-05-21 08:45:17', '2025-05-21 08:45:17'),
(3, 'Printer', 'Printers and multifunction devices', '2025-05-21 08:45:17', '2025-05-21 08:45:17'),
(4, 'Network', 'Network equipment like routers and switches', '2025-05-21 08:45:17', '2025-05-21 08:45:17'),
(5, 'Furniture', 'Office furniture and fixtures', '2025-05-21 08:45:17', '2025-05-21 08:45:17'),
(6, 'Vehicle', 'Company vehicles', '2025-05-21 08:45:17', '2025-05-21 08:45:17'),
(7, 'Software', 'Software licenses and subscriptions', '2025-05-21 08:45:17', '2025-05-21 08:45:17');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `manager_id`, `location_id`, `created_at`, `updated_at`) VALUES
(1, 'Administration', 'Administrative department', NULL, 1, '2025-05-21 08:46:08', '2025-05-21 08:46:08'),
(2, 'Finance', 'Finance and accounting', NULL, 1, '2025-05-21 08:46:08', '2025-05-21 08:46:08'),
(3, 'IT', 'Information Technology', NULL, 1, '2025-05-21 08:46:08', '2025-05-21 08:46:08'),
(4, 'Operations', 'Operations department', NULL, 2, '2025-05-21 08:46:08', '2025-05-21 08:46:08'),
(5, 'HR', 'Human Resources', NULL, 1, '2025-05-21 08:46:08', '2025-05-21 08:46:08');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`, `city`, `state`, `country`, `postal_code`, `created_at`, `updated_at`) VALUES
(1, 'Head Office', '123 Main Street', 'Guwahati', 'Assam', 'India', '781001', '2025-05-21 08:45:54', '2025-05-21 08:45:54'),
(2, 'Regional Office', '456 Park Avenue', 'Shillong', 'Meghalaya', 'India', '793001', '2025-05-21 08:45:54', '2025-05-21 08:45:54');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\\\Models\\\\User', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'web', '2025-05-21 08:49:39', '2025-05-21 08:49:39'),
(2, 'Manager', 'web', '2025-05-21 08:49:39', '2025-05-21 08:49:39'),
(3, 'User', 'web', '2025-05-21 08:49:39', '2025-05-21 08:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `country`, `postal_code`, `tax_number`, `website`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Tech Solutions Ltd.', 'John Doe', 'john@techsolutions.com', '+91 9876543210', '789 Tech Park', 'Bangalore', 'Karnataka', 'India', '560001', 'GST123456789', 'https://techsolutions.com', NULL, '2025-05-21 08:47:10', '2025-05-21 08:47:10'),
(2, 'Office World', 'Jane Smith', 'jane@officeworld.in', '+91 9876543211', '456 Business Center', 'Mumbai', 'Maharashtra', 'India', '400001', 'GST987654321', 'https://officeworld.in', NULL, '2025-05-21 08:47:10', '2025-05-21 08:47:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `department_id`) VALUES
(1, 'Admin User', 'admin@example.com', '2025-05-21 08:41:58', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2025-05-21 08:41:58', '2025-05-21 08:41:58', 1),
(2, 'IT Manager', 'it.manager@neepco.com', '2025-05-21 08:57:02', '\\.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2025-05-21 08:57:02', '2025-05-21 08:57:02', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assets_asset_code_unique` (`asset_code`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_categories_name_unique` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_name_unique` (`name`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
