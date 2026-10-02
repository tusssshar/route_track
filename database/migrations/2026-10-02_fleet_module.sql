-- RouteTrac Fleet module: vehicles, trips (driver + vehicle assignment), trip load,
-- trip expenses (toll / tax / fuel ...), trip events and trip-tagged GPS tracking.
-- Run against the client repository DB (DBN), then the master DB block at the bottom.
-- Safe to re-run: every statement is idempotent.

-- ---------------------------------------------------------------- repository DB
USE `RouteTrac_template_repository`;

CREATE TABLE IF NOT EXISTS `vehicles` (
  `vehicle_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `registration_no` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `make_model` varchar(100) NOT NULL DEFAULT '',
  `fuel_type` varchar(20) NOT NULL DEFAULT 'Diesel',
  `capacity_kg` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `capacity_units` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Max item units per trip, 0 = not enforced',
  `fastag_id` varchar(50) NOT NULL DEFAULT '',
  `odometer_km` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Updated when a trip is completed',
  `insurance_expiry` date DEFAULT NULL,
  `fitness_expiry` date DEFAULT NULL,
  `permit_expiry` date DEFAULT NULL,
  `pollution_expiry` date DEFAULT NULL,
  `status` enum('Available','On Trip','Maintenance','Inactive') NOT NULL DEFAULT 'Available',
  `notes` varchar(1024) NOT NULL DEFAULT '',
  `added_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `registration_no` (`registration_no`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `trips` (
  `trip_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_number` varchar(20) NOT NULL,
  `trip_date` date NOT NULL,
  `route_id` int(10) unsigned NOT NULL,
  `vehicle_id` int(10) unsigned NOT NULL,
  `driver_id` int(10) unsigned NOT NULL COMMENT 'user_id where is_driver = 1',
  `helper_name` varchar(100) NOT NULL DEFAULT '',
  `status` enum('Planned','Loaded','In Transit','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
  `start_odometer` int(10) unsigned DEFAULT NULL,
  `end_odometer` int(10) unsigned DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `close_note` varchar(255) NOT NULL DEFAULT '' COMMENT 'Cancel reason / forced-close note',
  `notes` varchar(1024) NOT NULL DEFAULT '',
  `created_by` int(10) unsigned NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`trip_id`),
  UNIQUE KEY `trip_number` (`trip_number`),
  KEY `trip_date` (`trip_date`),
  KEY `vehicle_status` (`vehicle_id`,`status`),
  KEY `driver_status` (`driver_id`,`status`),
  KEY `route_date` (`route_id`,`trip_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `trip_expenses` (
  `expense_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `vehicle_id` int(10) unsigned NOT NULL,
  `driver_id` int(10) unsigned NOT NULL,
  `expense_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `payment_mode` varchar(30) NOT NULL DEFAULT 'Cash',
  `location` varchar(255) NOT NULL DEFAULT '' COMMENT 'Toll plaza / check post / fuel station',
  `reference_no` varchar(100) NOT NULL DEFAULT '' COMMENT 'Receipt / transaction number',
  `receipt_file` varchar(255) NOT NULL DEFAULT '' COMMENT 'File under uploads/expense/',
  `fuel_litres` decimal(8,2) unsigned NOT NULL DEFAULT 0.00,
  `odometer_km` int(10) unsigned NOT NULL DEFAULT 0,
  `latitude` varchar(30) NOT NULL DEFAULT '',
  `longitude` varchar(30) NOT NULL DEFAULT '',
  `expense_time` datetime NOT NULL,
  `entered_by` enum('Driver App','Admin') NOT NULL DEFAULT 'Admin',
  `entered_by_user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `added_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`expense_id`),
  KEY `trip_id` (`trip_id`),
  KEY `vehicle_time` (`vehicle_id`,`expense_time`),
  KEY `driver_time` (`driver_id`,`expense_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `trip_events` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `event_type` varchar(30) NOT NULL,
  `note` varchar(512) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `source` enum('Admin','Driver App','System') NOT NULL DEFAULT 'Admin',
  `latitude` varchar(30) NOT NULL DEFAULT '',
  `longitude` varchar(30) NOT NULL DEFAULT '',
  `event_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `trip_id` (`trip_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `deliveries`
  ADD COLUMN IF NOT EXISTS `trip_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'trips.trip_id the order is loaded on',
  ADD INDEX IF NOT EXISTS `trip_id` (`trip_id`);

ALTER TABLE `driver_location`
  ADD COLUMN IF NOT EXISTS `trip_id` int(10) unsigned NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `vehicle_id` int(10) unsigned NOT NULL DEFAULT 0,
  ADD INDEX IF NOT EXISTS `trip_id` (`trip_id`),
  ADD INDEX IF NOT EXISTS `driver_time` (`driver_id`,`add_date_time`);

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `license_no` varchar(50) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `license_expiry` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `phone` varchar(20) NOT NULL DEFAULT '';

-- ---------------------------------------------------------------- master DB
USE `routetra_RouteTrac_master`;

INSERT IGNORE INTO `access_menu` (`access_menu_id`, `menu_name`, `is_delete`) VALUES (10, 'Fleet', 0);

-- Administrator and Delivery Manager get the Fleet menu
UPDATE `access_levels` SET `access_menu_id` = CONCAT(`access_menu_id`, ',10')
 WHERE `level_id` IN (1, 4) AND FIND_IN_SET('10', `access_menu_id`) = 0;
