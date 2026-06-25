-- ============================================================
-- Upgrade: Add 'code' column and 'cost_price' column to products table
-- Date: 2026-06-25
-- Description:
--   1. Adds a new 'code' column (separate from product_code/SKU)
--   2. Adds a 'cost_price' column for cost price tracking
--   3. Renames existing 'price' to 'selling_price'
-- ============================================================

-- Step 1: Add the new 'code' column (product code, separate from SKU)
ALTER TABLE `products` ADD COLUMN `code` VARCHAR(100) NULL AFTER `id`;

-- Step 2: Add the 'cost_price' column
ALTER TABLE `products` ADD COLUMN `cost_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER `current_category`;

-- Step 3: Rename 'price' to 'selling_price'
ALTER TABLE `products` CHANGE COLUMN `price` `selling_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00;

-- Step 4: Add an index on the new 'code' column (optional, for fast lookups)
ALTER TABLE `products` ADD INDEX `idx_code` (`code`);
