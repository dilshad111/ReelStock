-- Codex correction patch for reelstock_2026-06-11_17-13-55.sql
-- Adds schema changes introduced after the backup date and backfills reel locations.

ALTER TABLE `engineering_products`
  ADD COLUMN `fefco_code` varchar(20) DEFAULT NULL AFTER `product_category`;

ALTER TABLE `products`
  ADD COLUMN `dispatch_policy` varchar(30) NOT NULL DEFAULT 'customer_restricted' AFTER `opening_balance`;

ALTER TABLE `reels`
  ADD COLUMN `current_location` varchar(30) NOT NULL DEFAULT 'Warehouse' AFTER `status`;

DROP TABLE IF EXISTS `reel_transfers`;
CREATE TABLE `reel_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reel_id` bigint(20) unsigned NOT NULL,
  `transfer_date` date NOT NULL,
  `from_location` varchar(30) NOT NULL,
  `to_location` varchar(30) NOT NULL,
  `handled_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reel_transfers_transfer_date_to_location_index` (`transfer_date`,`to_location`),
  KEY `reel_transfers_reel_id_transfer_date_index` (`reel_id`,`transfer_date`),
  KEY `reel_transfers_created_by_foreign` (`created_by`),
  CONSTRAINT `reel_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reel_transfers_reel_id_foreign` FOREIGN KEY (`reel_id`) REFERENCES `reels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `reels` r
JOIN (
  SELECT rr.reel_id, rr.return_location
  FROM `reel_returns` rr
  JOIN (
    SELECT reel_id, MAX(id) AS latest_id
    FROM `reel_returns`
    WHERE returned_to = 'stock'
    GROUP BY reel_id
  ) latest ON latest.latest_id = rr.id
  WHERE rr.returned_to = 'stock'
    AND rr.return_location IS NOT NULL
) latest_return ON latest_return.reel_id = r.id
SET r.current_location = CASE
  WHEN latest_return.return_location = 'Factory' THEN 'Factory'
  ELSE 'Warehouse'
END;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_15_120000_add_fefco_code_to_engineering_products_table', 34
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_15_120000_add_fefco_code_to_engineering_products_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_17_120000_add_dispatch_policy_to_products_table', 34
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_17_120000_add_dispatch_policy_to_products_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_17_130000_add_current_location_to_reels_and_create_reel_transfers_table', 34
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_17_130000_add_current_location_to_reels_and_create_reel_transfers_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_17_131000_backfill_reel_current_locations_from_stock_returns', 34
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_17_131000_backfill_reel_current_locations_from_stock_returns'
);
