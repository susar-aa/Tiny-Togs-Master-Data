-- Database Creation
CREATE DATABASE IF NOT EXISTS `product_category_val` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `product_category_val`;

-- Table: categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(255) NOT NULL,
  `main_category` VARCHAR(255) NULL,
  `including_items` TEXT NULL,
  `is_auto_created` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: category_keywords
CREATE TABLE IF NOT EXISTS `category_keywords` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `keyword` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_keywords_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  INDEX `idx_keyword` (`keyword`),
  INDEX `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products (UPDATED with selling_price column)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_code` VARCHAR(100) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `current_category` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `supplier` VARCHAR(255) NULL,
  `other_fields_json` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_product_code` (`product_code`),
  INDEX `idx_current_category` (`current_category`),
  INDEX `idx_product_name` (`product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: category_suggestions
CREATE TABLE IF NOT EXISTS `category_suggestions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `current_category` VARCHAR(255) NOT NULL,
  `suggested_category` VARCHAR(255) NOT NULL,
  `matched_keyword` VARCHAR(255) NOT NULL,
  `confidence_score` INT NOT NULL,
  `status` ENUM('pending', 'approved', 'ignored') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_suggestions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  INDEX `idx_product_id` (`product_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_confidence` (`confidence_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: logs
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: settings
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('min_confidence_threshold', '60'),
('enable_fuzzy_matching', '1'),
('max_suggestions_per_product', '1'),
('keyword_min_length', '3')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Insert Sample Categories and Keywords
INSERT INTO `categories` (`id`, `category_name`, `including_items`) VALUES
(1, 'Baby Bedding Sets & Pillows', 'pillow, quilt, bedding, pillow case, bolster, sheet'),
(2, 'Toys & Games', 'toy, doll, game, block, puzzle, teddy'),
(3, 'Baby Clothing', 'romper, bodysuit, shirt, pants, socks, bib, frock'),
(4, 'Feeding & Nursing', 'bottle, nipple, bib, breast pump, sterilizer, high chair'),
(5, 'Bath & Skin Care', 'shampoo, soap, lotion, towel, tub, bath wash')
ON DUPLICATE KEY UPDATE `including_items` = VALUES(`including_items`);

INSERT INTO `category_keywords` (`category_id`, `keyword`) VALUES
(1, 'pillow'), (1, 'quilt'), (1, 'bedding'), (1, 'pillow case'), (1, 'bolster'), (1, 'sheet'),
(2, 'toy'), (2, 'doll'), (2, 'game'), (2, 'block'), (2, 'puzzle'), (2, 'teddy'),
(3, 'romper'), (3, 'bodysuit'), (3, 'shirt'), (3, 'pants'), (3, 'socks'), (3, 'bib'), (3, 'frock'),
(4, 'bottle'), (4, 'nipple'), (4, 'bib'), (4, 'breast pump'), (4, 'sterilizer'), (4, 'high chair'),
(5, 'shampoo'), (5, 'soap'), (5, 'lotion'), (5, 'towel'), (5, 'tub'), (5, 'bath wash')
ON DUPLICATE KEY UPDATE `keyword` = VALUES(`keyword`);

-- Insert Sample Products (UPDATED with selling_price)
INSERT INTO `products` (`id`, `product_code`, `product_name`, `current_category`, `price`, `selling_price`, `supplier`, `other_fields_json`) VALUES
(1, 'P001', 'Luxury Baby Pillow', 'Toys & Games', 15.99, 22.99, 'Baby Sleep Corp', '{"color": "blue", "material": "cotton"}'),
(2, 'P002', 'Wooden Building Blocks Set', 'Toys & Games', 24.50, 35.00, 'ToyLand Ltd', '{"pieces": 50}'),
(3, 'P003', 'Organic Cotton Romper', 'Baby Clothing', 12.99, 18.50, 'TinyTreads', '{"size": "6M"}'),
(4, 'P004', 'Baby Towel Set', 'Baby Bedding Sets & Pillows', 18.00, 25.00, 'SoftTouch', '{"pack": 3}'),
(5, 'P005', 'Anti-Colic Feeding Bottle', 'Bath & Skin Care', 9.50, 14.99, 'NurturePro', '{"capacity": "250ml"}')
ON DUPLICATE KEY UPDATE `product_code` = VALUES(`product_code`);

-- ========================================================================
-- UPGRADE SCRIPT: Add selling_price column to existing products table
-- ========================================================================
-- Run this ALTER statement if your products table already exists without selling_price:
-- ALTER TABLE products ADD COLUMN selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `price`;
-- UPDATE products SET selling_price = price WHERE selling_price = 0 AND price > 0;