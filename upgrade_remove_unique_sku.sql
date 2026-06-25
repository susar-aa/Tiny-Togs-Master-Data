-- ============================================================
-- Upgrade: Remove UNIQUE constraint on product_code (SKU)
-- Date: 2026-06-25
-- Description:
--   Allows duplicate SKUs/codes during import.
--   All Excel rows are now inserted as new records.
-- ============================================================

-- Step 1: Drop the UNIQUE KEY on product_code
ALTER TABLE `products` DROP INDEX `idx_product_code`;

-- Step 2: Add a normal (non-unique) index instead for search performance
ALTER TABLE `products` ADD INDEX `idx_product_code` (`product_code`);

-- Step 3: Allow product_code to be NULL (in case some rows don't have SKU)
ALTER TABLE `products` MODIFY COLUMN `product_code` VARCHAR(100) NULL;
