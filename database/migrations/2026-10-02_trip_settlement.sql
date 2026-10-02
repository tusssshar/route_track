-- Trip sheet / driver cash settlement + company details printed on documents. Idempotent.
USE `RouteTrac_template_repository`;

ALTER TABLE `trips`
  ADD COLUMN IF NOT EXISTS `cash_advance` decimal(10,2) unsigned NOT NULL DEFAULT 0.00 COMMENT 'Cash given to the driver for the trip',
  ADD COLUMN IF NOT EXISTS `settlement_status` enum('Pending','Settled') NOT NULL DEFAULT 'Pending',
  ADD COLUMN IF NOT EXISTS `settled_by` int(10) unsigned NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `settled_at` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `settlement_note` varchar(255) NOT NULL DEFAULT '';

INSERT IGNORE INTO `company_settings` (`setting_key`, `setting_value`) VALUES
  ('company_name', 'RouteTrac Logistics'),
  ('company_address', ''),
  ('company_tax_id', '');
