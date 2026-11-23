-- Run once on Azure:  sudo mariadb janpoultry < deploy/migrations/2026-11-22-categories-google.sql
-- Safe to run more than once.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Turn every category already typed on a product into a real category.
INSERT IGNORE INTO `categories` (`name`, `sort_order`)
SELECT DISTINCT TRIM(`category`), 0 FROM `products`
WHERE `category` IS NOT NULL AND TRIM(`category`) <> '';

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `google_id` varchar(64) DEFAULT NULL;
ALTER TABLE `users` ADD UNIQUE INDEX IF NOT EXISTS `uq_users_google_id` (`google_id`);
