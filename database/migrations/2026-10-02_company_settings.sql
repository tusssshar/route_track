-- Company settings (per client repository DB): timezone, currency, distance and fuel units.
-- Defaults: India (Asia/Kolkata, INR, km, litres). Idempotent.
USE `RouteTrac_template_repository`;

CREATE TABLE IF NOT EXISTS `company_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_by` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `company_settings` (`setting_key`, `setting_value`) VALUES
  ('timezone', 'Asia/Kolkata'),
  ('currency', 'INR'),
  ('distance_unit', 'km'),
  ('fuel_unit', 'L');

-- When the phone took the GPS reading may differ from when it reached the server (offline queue)
ALTER TABLE `driver_location`
  ADD COLUMN IF NOT EXISTS `accuracy_m` smallint(5) unsigned DEFAULT NULL COMMENT 'GPS accuracy reported by the phone, metres',
  ADD COLUMN IF NOT EXISTS `received_at` datetime DEFAULT NULL COMMENT 'When the server received the point (add_date_time = when it was recorded)';
