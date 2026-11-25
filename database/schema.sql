-- FreshNest 1.1.0 — database schema (MySQL 8.0+ / MariaDB 10.11+, utf8mb4)
-- Creates every table, key and index from an empty database. Same result as: php artisan migrate
-- Import: mysql -u USER -p DATABASE < database/schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `admin_password_reset_tokens`;
CREATE TABLE `admin_password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admin_views`;
CREATE TABLE `admin_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `resource` varchar(30) NOT NULL,
  `name` varchar(40) NOT NULL,
  `query` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`query`)),
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_views_admin_id_index` (`admin_id`),
  KEY `admin_views_resource_index` (`resource`),
  CONSTRAINT `admin_views_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'owner',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `locale` char(2) NOT NULL DEFAULT 'en',
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tour_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tour_state`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `subject_type` varchar(60) NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `subject_label` varchar(255) DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_admin_id_index` (`admin_id`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `placement` varchar(20) NOT NULL,
  `title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`title`)),
  `eyebrow` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eyebrow`)),
  `text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`text`)),
  `button_label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`button_label`)),
  `url` varchar(500) DEFAULT NULL,
  `image_id` bigint(20) unsigned DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banners_image_id_index` (`image_id`),
  CONSTRAINT `banners_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `slug` varchar(255) NOT NULL,
  `origin` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`origin`)),
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description`)),
  `logo` varchar(255) DEFAULT NULL,
  `logo_small` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint(20) unsigned NOT NULL,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `options_hash` varchar(40) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_items_cart_id_product_variant_id_options_hash_unique` (`cart_id`,`product_variant_id`,`options_hash`),
  KEY `cart_items_product_variant_id_index` (`product_variant_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `carts`;
CREATE TABLE `carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `discount_code` varchar(40) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `reminder_stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carts_token_unique` (`token`),
  UNIQUE KEY `carts_user_id_unique` (`user_id`),
  KEY `carts_updated_at_index` (`updated_at`),
  KEY `carts_reminder_stage_updated_at_index` (`reminder_stage`,`updated_at`),
  CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `slug` varchar(255) NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description`)),
  `image_id` bigint(20) unsigned DEFAULT NULL,
  `seo_title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_title`)),
  `seo_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_description`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_image_id_index` (`image_id`),
  KEY `categories_parent_id_sort_order_index` (`parent_id`,`sort_order`),
  CONSTRAINT `categories_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `category_product`;
CREATE TABLE `category_product` (
  `category_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`category_id`,`product_id`),
  KEY `category_product_product_id_index` (`product_id`),
  CONSTRAINT `category_product_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `code_versions`;
CREATE TABLE `code_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `css` longtext DEFAULT NULL,
  `head` longtext DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `domains` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`domains`)),
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `code_versions_admin_id_index` (`admin_id`),
  CONSTRAINT `code_versions_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` char(3) NOT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `symbol` varchar(10) NOT NULL,
  `symbol_ar` varchar(10) DEFAULT NULL,
  `decimal_places` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `exchange_rate` decimal(18,8) NOT NULL DEFAULT 1.00000000,
  `is_base` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_code_unique` (`code`),
  KEY `currencies_is_active_sort_order_index` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `custom_order_requests`;
CREATE TABLE `custom_order_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `category` varchar(60) DEFAULT NULL,
  `dimensions` varchar(160) DEFAULT NULL,
  `material` varchar(120) DEFAULT NULL,
  `finish` varchar(120) DEFAULT NULL,
  `quantity` smallint(5) unsigned NOT NULL DEFAULT 1,
  `budget` varchar(60) DEFAULT NULL,
  `timeline` varchar(60) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `admin_note` text DEFAULT NULL,
  `locale` varchar(5) NOT NULL DEFAULT 'en',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_order_requests_user_id_index` (`user_id`),
  KEY `custom_order_requests_product_id_index` (`product_id`),
  KEY `custom_order_requests_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `custom_order_requests_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custom_order_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `customer_addresses`;
CREATE TABLE `customer_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `label` varchar(40) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `emirate` varchar(30) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `address_line` varchar(200) NOT NULL,
  `building` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `country` char(2) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_addresses_user_id_index` (`user_id`),
  KEY `customer_addresses_user_id_is_default_index` (`user_id`,`is_default`),
  CONSTRAINT `customer_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `discount_usages`;
CREATE TABLE `discount_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discount_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `discount_usages_order_id_index` (`order_id`),
  KEY `discount_usages_discount_id_email_index` (`discount_id`,`email`),
  CONSTRAINT `discount_usages_discount_id_foreign` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discount_usages_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `discounts`;
CREATE TABLE `discounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `type` varchar(20) NOT NULL,
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_subtotal` decimal(12,2) DEFAULT NULL,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `usage_limit_per_customer` int(10) unsigned DEFAULT NULL,
  `used_count` int(10) unsigned NOT NULL DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discounts_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `email_opt_outs`;
CREATE TABLE `email_opt_outs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_opt_outs_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fulfillment_items`;
CREATE TABLE `fulfillment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fulfillment_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fulfillment_items_fulfillment_id_index` (`fulfillment_id`),
  KEY `fulfillment_items_order_item_id_index` (`order_item_id`),
  CONSTRAINT `fulfillment_items_fulfillment_id_foreign` FOREIGN KEY (`fulfillment_id`) REFERENCES `fulfillments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fulfillment_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fulfillments`;
CREATE TABLE `fulfillments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `sequence` smallint(5) unsigned NOT NULL,
  `carrier` varchar(60) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `label_url` varchar(500) DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fulfillments_order_id_index` (`order_id`),
  KEY `fulfillments_admin_id_index` (`admin_id`),
  CONSTRAINT `fulfillments_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fulfillments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `funnel_counts`;
CREATE TABLE `funnel_counts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `day` date NOT NULL,
  `stage` varchar(20) NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funnel_counts_day_stage_unique` (`day`,`stage`),
  KEY `funnel_counts_day_index` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `inventory_movements`;
CREATE TABLE `inventory_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `change` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reason` varchar(30) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_movements_admin_id_index` (`admin_id`),
  KEY `inventory_movements_product_variant_id_created_at_index` (`product_variant_id`,`created_at`),
  CONSTRAINT `inventory_movements_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_movements_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `disk` varchar(30) NOT NULL,
  `collection` varchar(20) NOT NULL DEFAULT 'library',
  `directory` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(50) NOT NULL,
  `size` int(10) unsigned NOT NULL,
  `width` smallint(5) unsigned NOT NULL,
  `height` smallint(5) unsigned NOT NULL,
  `alt` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alt`)),
  `sizes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`sizes`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_collection_index` (`collection`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `media_product`;
CREATE TABLE `media_product` (
  `media_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `position` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`,`media_id`),
  KEY `media_product_media_id_index` (`media_id`),
  CONSTRAINT `media_product_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `media_product_review`;
CREATE TABLE `media_product_review` (
  `product_review_id` bigint(20) unsigned NOT NULL,
  `media_id` bigint(20) unsigned NOT NULL,
  `position` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_review_id`,`media_id`),
  KEY `media_product_review_media_id_index` (`media_id`),
  CONSTRAINT `media_product_review_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_product_review_product_review_id_foreign` FOREIGN KEY (`product_review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`label`)),
  `url` varchar(500) NOT NULL,
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_parent_id_index` (`parent_id`),
  KEY `menu_items_menu_id_parent_id_sort_order_index` (`menu_id`,`parent_id`,`sort_order`),
  CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_handle_unique` (`handle`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_drafts`;
CREATE TABLE `order_drafts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(120) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `items_count` int(10) unsigned NOT NULL DEFAULT 0,
  `subtotal` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_drafts_admin_id_index` (`admin_id`),
  KEY `order_drafts_customer_id_index` (`customer_id`),
  KEY `order_drafts_updated_at_index` (`updated_at`),
  CONSTRAINT `order_drafts_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_drafts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_events`;
CREATE TABLE `order_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `type` varchar(40) NOT NULL,
  `message` text DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_events_order_id_index` (`order_id`),
  KEY `order_events_admin_id_index` (`admin_id`),
  CONSTRAINT `order_events_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_events_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `variant_title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_title`)),
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `sku` varchar(64) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `compare_at_price` decimal(12,2) DEFAULT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_index` (`order_id`),
  KEY `order_items_product_id_index` (`product_id`),
  KEY `order_items_product_variant_id_index` (`product_variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(30) DEFAULT NULL,
  `access_token` varchar(64) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `fulfillment_status` varchar(20) NOT NULL DEFAULT 'unfulfilled',
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `locale` varchar(5) NOT NULL,
  `marketing_opt_in` tinyint(1) NOT NULL DEFAULT 0,
  `ship_name` varchar(120) NOT NULL,
  `ship_phone` varchar(20) DEFAULT NULL,
  `ship_emirate` varchar(30) DEFAULT NULL,
  `ship_city` varchar(100) NOT NULL,
  `ship_address_line` varchar(200) NOT NULL,
  `ship_building` varchar(100) DEFAULT NULL,
  `ship_country` varchar(2) NOT NULL DEFAULT 'AE',
  `currency` varchar(3) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `shipping_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_code` varchar(40) DEFAULT NULL,
  `promotion_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `promotion_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`promotion_names`)),
  `tax_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL,
  `refunded_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `prices_include_tax` tinyint(1) NOT NULL DEFAULT 1,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_label` varchar(60) DEFAULT NULL,
  `display_currency` varchar(3) NOT NULL,
  `display_rate` decimal(14,6) NOT NULL DEFAULT 1.000000,
  `shipping_method` varchar(40) NOT NULL,
  `shipping_method_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`shipping_method_name`)),
  `payment_method` varchar(30) NOT NULL,
  `carrier` varchar(60) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `placed_at` timestamp NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ship_state` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_access_token_unique` (`access_token`),
  UNIQUE KEY `orders_number_unique` (`number`),
  KEY `orders_user_id_index` (`user_id`),
  KEY `orders_status_placed_at_index` (`status`,`placed_at`),
  KEY `orders_payment_status_payment_method_placed_at_index` (`payment_status`,`payment_method`,`placed_at`),
  KEY `orders_email_index` (`email`),
  KEY `orders_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`title`)),
  `eyebrow` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eyebrow`)),
  `heading` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`heading`)),
  `intro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`intro`)),
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`body`)),
  `footnote` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`footnote`)),
  `image_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`image_ids`)),
  `seo_title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_title`)),
  `seo_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_description`)),
  `show_contact_form` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `gateway` varchar(30) NOT NULL,
  `status` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_index` (`order_id`),
  KEY `payments_reference_index` (`reference`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_option_values`;
CREATE TABLE `product_option_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_option_id` bigint(20) unsigned NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `position` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_option_values_product_option_id_index` (`product_option_id`),
  CONSTRAINT `product_option_values_product_option_id_foreign` FOREIGN KEY (`product_option_id`) REFERENCES `product_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_options`;
CREATE TABLE `product_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `position` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_options_product_id_index` (`product_id`),
  CONSTRAINT `product_options_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_reviews`;
CREATE TABLE `product_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `title` varchar(120) DEFAULT NULL,
  `body` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `locale` varchar(5) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_reviews_product_id_user_id_unique` (`product_id`,`user_id`),
  KEY `product_reviews_user_id_index` (`user_id`),
  KEY `product_reviews_product_id_status_index` (`product_id`,`status`),
  CONSTRAINT `product_reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_variant_option_value`;
CREATE TABLE `product_variant_option_value` (
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `product_option_value_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_variant_id`,`product_option_value_id`),
  KEY `product_variant_option_value_product_option_value_id_index` (`product_option_value_id`),
  CONSTRAINT `product_variant_option_value_product_option_value_id_foreign` FOREIGN KEY (`product_option_value_id`) REFERENCES `product_option_values` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_option_value_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(64) DEFAULT NULL,
  `barcode` varchar(64) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `compare_at_price` decimal(12,2) DEFAULT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `weight_grams` int(10) unsigned DEFAULT NULL,
  `track_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `allow_backorder` tinyint(1) NOT NULL DEFAULT 0,
  `media_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variants_media_id_index` (`media_id`),
  KEY `product_variants_product_id_position_index` (`product_id`,`position`),
  KEY `product_variants_sku_index` (`sku`),
  CONSTRAINT `product_variants_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `slug` varchar(255) NOT NULL,
  `short_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`short_description`)),
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description`)),
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dimensions`)),
  `delivery_returns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delivery_returns`)),
  `trust_badges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trust_badges`)),
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `made_to_order` tinyint(1) NOT NULL DEFAULT 0,
  `from_price` tinyint(1) NOT NULL DEFAULT 0,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `brand` varchar(100) DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `rating_average` decimal(3,2) DEFAULT NULL,
  `rating_count` int(10) unsigned NOT NULL DEFAULT 0,
  `seo_title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_title`)),
  `seo_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_description`)),
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_status_published_at_index` (`status`,`published_at`),
  KEY `products_brand_id_index` (`brand_id`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name`)),
  `buy_quantity` smallint(5) unsigned NOT NULL,
  `get_quantity` smallint(5) unsigned NOT NULL,
  `get_percent` tinyint(3) unsigned NOT NULL DEFAULT 100,
  `applies_to` varchar(20) NOT NULL DEFAULT 'all',
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `product_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_ids`)),
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotions_category_id_index` (`category_id`),
  CONSTRAINT `promotions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `refund_items`;
CREATE TABLE `refund_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `refund_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  `restocked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `refund_items_refund_id_index` (`refund_id`),
  KEY `refund_items_order_item_id_index` (`order_item_id`),
  CONSTRAINT `refund_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refund_items_refund_id_foreign` FOREIGN KEY (`refund_id`) REFERENCES `refunds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `refunds`;
CREATE TABLE `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `shipping_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `gateway_reference` varchar(255) DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refunds_order_id_index` (`order_id`),
  KEY `refunds_admin_id_index` (`admin_id`),
  CONSTRAINT `refunds_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `return_items`;
CREATE TABLE `return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_request_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `return_items_return_request_id_index` (`return_request_id`),
  KEY `return_items_order_item_id_index` (`order_item_id`),
  CONSTRAINT `return_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_items_return_request_id_foreign` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `return_requests`;
CREATE TABLE `return_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(30) DEFAULT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'requested',
  `resolution` varchar(20) NOT NULL,
  `reason` varchar(30) NOT NULL,
  `customer_note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `restocked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_requests_number_unique` (`number`),
  KEY `return_requests_order_id_index` (`order_id`),
  KEY `return_requests_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `return_requests_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `search_misses`;
CREATE TABLE `search_misses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(120) NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT 1,
  `last_searched_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `search_misses_term_unique` (`term`),
  KEY `search_misses_count_index` (`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
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

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(50) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_group_key_unique` (`group`,`key`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stock_alerts`;
CREATE TABLE `stock_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `locale` varchar(5) NOT NULL DEFAULT 'en',
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_alerts_product_variant_id_email_unique` (`product_variant_id`,`email`),
  UNIQUE KEY `stock_alerts_token_unique` (`token`),
  KEY `stock_alerts_notified_at_index` (`notified_at`),
  KEY `stock_alerts_user_id_index` (`user_id`),
  CONSTRAINT `stock_alerts_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `subscribers`;
CREATE TABLE `subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `locale` varchar(5) NOT NULL DEFAULT 'en',
  `source` varchar(40) DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscribers_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `traffic_bot_counts`;
CREATE TABLE `traffic_bot_counts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `day` date NOT NULL,
  `kind` varchar(10) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `traffic_bot_counts_day_kind_unique` (`day`,`kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `traffic_visits`;
CREATE TABLE `traffic_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitor` char(16) NOT NULL,
  `country` char(2) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `lat` decimal(7,4) DEFAULT NULL,
  `lon` decimal(7,4) DEFAULT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT 1,
  `pages` int(10) unsigned NOT NULL DEFAULT 1,
  `path` varchar(255) NOT NULL,
  `started_at` datetime NOT NULL,
  `last_seen_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `traffic_visits_visitor_last_seen_at_index` (`visitor`,`last_seen_at`),
  KEY `traffic_visits_started_at_index` (`started_at`),
  KEY `traffic_visits_last_seen_at_index` (`last_seen_at`),
  KEY `traffic_visits_country_started_at_index` (`country`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `locale` char(2) NOT NULL DEFAULT 'en',
  `admin_note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `wishlist_items`;
CREATE TABLE `wishlist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlist_items_user_id_product_id_unique` (`user_id`,`product_id`),
  KEY `wishlist_items_product_id_index` (`product_id`),
  CONSTRAINT `wishlist_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_09_21_000100_create_admins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_09_21_000200_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_09_21_000300_create_currencies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_09_21_000400_create_menus_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_09_22_000100_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_09_22_000200_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_09_22_000300_create_products_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_09_22_000400_create_inventory_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_09_23_000100_create_carts_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_09_24_000100_create_orders_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_09_25_000100_create_promotions_and_content_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_09_26_000100_create_audit_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_09_27_000100_add_roles_and_address_book',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_09_28_000100_create_batch3_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_09_29_000100_create_admin_views_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_09_30_000100_create_fulfillments_and_refunds',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_10_01_000100_add_admin_note_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_10_02_000100_create_code_versions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_10_03_000100_create_subscribers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_10_03_000200_create_brands_and_product_flags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_10_04_000100_add_configuration_to_products_and_lines',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_10_05_000100_create_custom_order_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_10_06_000100_trim_over_long_meta_description',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_10_07_000100_create_order_drafts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_10_12_000100_create_stock_alerts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_10_13_000100_add_label_url_to_fulfillments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_10_14_000100_add_reminder_stage_to_carts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_10_15_000100_create_funnel_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_10_16_000100_activate_base_currency',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_10_17_000100_add_rich_text_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_10_22_000100_add_search_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_10_23_000100_add_country_and_state_to_addresses',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_10_24_000100_add_tour_state_to_admins',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_10_25_000100_create_traffic_tables',1);

SET FOREIGN_KEY_CHECKS = 1;
