-- ============================================================
-- Upgrade: Insert missing suppliers from products table to suppliers table
-- Date: 2026-06-26
-- Description:
--   Syncs all unique supplier names currently in the `products` table
--   that do not exist in the `suppliers` table, making them visible
--   in the Supplier Management page.
-- ============================================================

INSERT IGNORE INTO `suppliers` (`supplier_name`)
SELECT DISTINCT `supplier`
FROM `products`
WHERE `supplier` IS NOT NULL AND `supplier` != '';
