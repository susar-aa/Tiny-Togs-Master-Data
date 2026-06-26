-- ============================================================
-- Upgrade: Drop UNIQUE constraints to allow duplicate SKUs/Codes
-- Date: 2026-06-26
-- Description:
--   Ensures that no UNIQUE constraints exist on product_code (SKU)
--   or code (Product Code), allowing all records to be imported.
-- ============================================================

-- Step 1: Drop the UNIQUE KEY on product_code if it exists
-- Note: If you run this and the index name is different or doesn't exist, ignore any warnings.
ALTER TABLE `products` DROP INDEX IF EXISTS `idx_product_code`;
ALTER TABLE `products` DROP INDEX IF EXISTS `product_code`;

-- Step 2: Add a normal (non-unique) index for search performance
ALTER TABLE `products` ADD INDEX `idx_product_code` (`product_code`);

-- Step 3: Ensure product_code column allows NULL and duplicates
ALTER TABLE `products` MODIFY COLUMN `product_code` VARCHAR(100) NULL;

-- Step 4: Drop any unique index on code if it exists, and make it a normal index
ALTER TABLE `products` DROP INDEX IF EXISTS `idx_code`;
ALTER TABLE `products` ADD INDEX `idx_code` (`code`);
ALTER TABLE `products` MODIFY COLUMN `code` VARCHAR(100) NULL;
