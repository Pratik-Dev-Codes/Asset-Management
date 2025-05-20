SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `asset_attachments`;
DROP TABLE IF EXISTS `asset_categories`;
DROP TABLE IF EXISTS `asset_depreciations`;
DROP TABLE IF EXISTS `asset_transfers`;
DROP TABLE IF EXISTS `assets`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `locations`;
DROP TABLE IF EXISTS `maintenance_records`;
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `vendors`;

CREATE TABLE `asset_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_attachments_asset_id_foreign` (`asset_id`),
  KEY `asset_attachments_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `asset_attachments_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `asset_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `asset_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `asset_depreciations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `purchase_cost` decimal(10,2) NOT NULL,
  `salvage_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `useful_life_years` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_depreciations_asset_id_foreign` (`asset_id`),
  CONSTRAINT `asset_depreciations_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `asset_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `from_location_id` bigint(20) unsigned DEFAULT NULL,
  `to_location_id` bigint(20) unsigned DEFAULT NULL,
  `from_department_id` bigint(20) unsigned DEFAULT NULL,
  `to_department_id` bigint(20) unsigned DEFAULT NULL,
  `from_user_id` bigint(20) unsigned DEFAULT NULL,
  `to_user_id` bigint(20) unsigned DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `initiated_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_transfers_asset_id_foreign` (`asset_id`),
  KEY `asset_transfers_from_location_id_foreign` (`from_location_id`),
  KEY `asset_transfers_to_location_id_foreign` (`to_location_id`),
  KEY `asset_transfers_from_department_id_foreign` (`from_department_id`),
  KEY `asset_transfers_to_department_id_foreign` (`to_department_id`),
  KEY `asset_transfers_from_user_id_foreign` (`from_user_id`),
  KEY `asset_transfers_to_user_id_foreign` (`to_user_id`),
  KEY `asset_transfers_initiated_by_foreign` (`initiated_by`),
  CONSTRAINT `asset_transfers_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `asset_transfers_from_department_id_foreign` FOREIGN KEY (`from_department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `asset_transfers_from_location_id_foreign` FOREIGN KEY (`from_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `asset_transfers_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `asset_transfers_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `asset_transfers_to_department_id_foreign` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `asset_transfers_to_location_id_foreign` FOREIGN KEY (`to_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `asset_transfers_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_tag` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `model_number` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(255) DEFAULT NULL,
  `warranty_expires` date DEFAULT NULL,
  `warranty_notes` text DEFAULT NULL,
  `status` enum('available','assigned','under_maintenance','disposed') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `last_audit_date` timestamp NULL DEFAULT NULL,
  `audited_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assets_asset_tag_unique` (`asset_tag`),
  KEY `assets_category_id_foreign` (`category_id`),
  KEY `assets_location_id_foreign` (`location_id`),
  KEY `assets_department_id_foreign` (`department_id`),
  KEY `assets_assigned_to_foreign` (`assigned_to`),
  KEY `assets_vendor_id_foreign` (`vendor_id`),
  KEY `assets_audited_by_foreign` (`audited_by`),
  KEY `assets_asset_tag_index` (`asset_tag`),
  KEY `assets_status_index` (`status`),
  KEY `assets_purchase_date_index` (`purchase_date`),
  KEY `assets_warranty_expires_index` (`warranty_expires`),
  CONSTRAINT `assets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `assets_audited_by_foreign` FOREIGN KEY (`audited_by`) REFERENCES `users` (`id`),
  CONSTRAINT `assets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`),
  CONSTRAINT `assets_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `assets_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `assets_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_manager_id_foreign` (`manager_id`),
  CONSTRAINT `departments_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `maintenance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('preventive','corrective','inspection','upgrade') NOT NULL,
  `start_date` datetime NOT NULL,
  `completion_date` datetime DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `cost` decimal(10,2) DEFAULT NULL,
  `technician_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_records_asset_id_foreign` (`asset_id`),
  KEY `maintenance_records_technician_id_foreign` (`technician_id`),
  KEY `maintenance_records_status_index` (`status`),
  KEY `maintenance_records_start_date_index` (`start_date`),
  KEY `maintenance_records_completion_date_index` (`completion_date`),
  CONSTRAINT `maintenance_records_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `maintenance_records_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign Key Constraints

ALTER TABLE `assets` 
  ADD CONSTRAINT `assets_assigned_to_foreign` 
  FOREIGN KEY (`assigned_to`) 
  REFERENCES `users` (`id`);

ALTER TABLE `assets` 
  ADD CONSTRAINT `assets_audited_by_foreign` 
  FOREIGN KEY (`audited_by`) 
  REFERENCES `users` (`id`);

ALTER TABLE `assets` 
  ADD CONSTRAINT `assets_category_id_foreign` 
  FOREIGN KEY (`category_id`) 
  REFERENCES `asset_categories` (`id`);

ALTER TABLE `assets` 
  ADD CONSTRAINT `assets_department_id_foreign` 
  FOREIGN KEY (`department_id`) 
  REFERENCES `departments` (`id`);

ALTER TABLE `assets` 
  ADD CONSTRAINT `assets_location_id_foreign` 
  FOREIGN KEY (`location_id`) 
  REFERENCES `locations` (`id`);

ALTER TABLE `assets` 
  ADD CONSTRAINT `assets_vendor_id_foreign` 
  FOREIGN KEY (`vendor_id`) 
  REFERENCES `vendors` (`id`);

ALTER TABLE `asset_attachments` 
  ADD CONSTRAINT `asset_attachments_asset_id_foreign` 
  FOREIGN KEY (`asset_id`) 
  REFERENCES `assets` (`id`);

ALTER TABLE `asset_attachments` 
  ADD CONSTRAINT `asset_attachments_uploaded_by_foreign` 
  FOREIGN KEY (`uploaded_by`) 
  REFERENCES `users` (`id`);

ALTER TABLE `asset_depreciations` 
  ADD CONSTRAINT `asset_depreciations_asset_id_foreign` 
  FOREIGN KEY (`asset_id`) 
  REFERENCES `assets` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_asset_id_foreign` 
  FOREIGN KEY (`asset_id`) 
  REFERENCES `assets` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_from_department_id_foreign` 
  FOREIGN KEY (`from_department_id`) 
  REFERENCES `departments` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_from_location_id_foreign` 
  FOREIGN KEY (`from_location_id`) 
  REFERENCES `locations` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_from_user_id_foreign` 
  FOREIGN KEY (`from_user_id`) 
  REFERENCES `users` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_initiated_by_foreign` 
  FOREIGN KEY (`initiated_by`) 
  REFERENCES `users` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_to_department_id_foreign` 
  FOREIGN KEY (`to_department_id`) 
  REFERENCES `departments` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_to_location_id_foreign` 
  FOREIGN KEY (`to_location_id`) 
  REFERENCES `locations` (`id`);

ALTER TABLE `asset_transfers` 
  ADD CONSTRAINT `asset_transfers_to_user_id_foreign` 
  FOREIGN KEY (`to_user_id`) 
  REFERENCES `users` (`id`);

ALTER TABLE `departments` 
  ADD CONSTRAINT `departments_manager_id_foreign` 
  FOREIGN KEY (`manager_id`) 
  REFERENCES `users` (`id`);

ALTER TABLE `maintenance_records` 
  ADD CONSTRAINT `maintenance_records_asset_id_foreign` 
  FOREIGN KEY (`asset_id`) 
  REFERENCES `assets` (`id`);

ALTER TABLE `maintenance_records` 
  ADD CONSTRAINT `maintenance_records_technician_id_foreign` 
  FOREIGN KEY (`technician_id`) 
  REFERENCES `users` (`id`);

SET FOREIGN_KEY_CHECKS=1;