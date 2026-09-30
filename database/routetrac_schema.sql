-- RouteTrac schema + reference data (no customer data). Import: mysql -uroot < database/routetrac_schema.sql
-- Demo login after import: admin / 12345  (change it)

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `RouteTrac_template_repository` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `RouteTrac_template_repository`;
DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `areas` (
  `area_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = No',
  `added_by_api` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = Added on website',
  PRIMARY KEY (`area_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `city_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `city_master` (
  `city_id` int(11) NOT NULL AUTO_INCREMENT,
  `city_name` varchar(100) NOT NULL,
  `city_name_vietnam` varchar(255) NOT NULL,
  PRIMARY KEY (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_id`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_id` (
  `client_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_addresses` (
  `address_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `ship_to_name` varchar(255) NOT NULL,
  `address_line_1` varchar(255) NOT NULL,
  `address_line_2` varchar(255) NOT NULL,
  `address_line_3` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `zip` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `added_by_api` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = Added on website',
  PRIMARY KEY (`address_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(110) NOT NULL,
  `account_number` varchar(110) NOT NULL,
  `added_by_api` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = Added on website',
  PRIMARY KEY (`customer_id`),
  KEY `account_number` (`account_number`),
  KEY `customer_name` (`customer_name`,`account_number`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliveries` (
  `delivery_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `manifest_id` int(10) unsigned NOT NULL,
  `route_id` int(10) unsigned NOT NULL,
  `driver_id` int(10) unsigned NOT NULL COMMENT 'user_id where is_driver = 1',
  `stop_number` smallint(5) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `address_id` int(10) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `cust_po_num` varchar(255) NOT NULL,
  `delivery_notes` mediumtext NOT NULL,
  `driver_notes` mediumtext NOT NULL,
  `scheduled_delivery` date NOT NULL,
  `delivery_time` datetime NOT NULL,
  `signature_text` varchar(255) NOT NULL,
  `signature_pic_id` int(10) unsigned NOT NULL,
  `latitude` mediumtext NOT NULL,
  `longitude` mediumtext NOT NULL,
  `added_date` datetime NOT NULL DEFAULT current_timestamp(),
  `exception_reason` varchar(255) NOT NULL,
  `exception_note` mediumtext NOT NULL,
  PRIMARY KEY (`delivery_id`),
  KEY `route_id` (`route_id`,`driver_id`),
  KEY `manifest_id` (`manifest_id`),
  KEY `driver_id` (`driver_id`),
  KEY `address_id` (`address_id`),
  KEY `customer_id` (`customer_id`,`address_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_items` (
  `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int(10) unsigned NOT NULL,
  `sku` varchar(255) NOT NULL,
  `qty` smallint(6) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` varchar(2048) NOT NULL,
  `uom` char(2) NOT NULL,
  `qty_delivered` int(11) NOT NULL,
  `upc_code` mediumtext NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_packages` (
  `packages_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int(10) unsigned NOT NULL,
  `package_type_id` int(10) unsigned NOT NULL,
  `qty` smallint(6) NOT NULL,
  `qty_delivered` int(11) NOT NULL,
  PRIMARY KEY (`packages_id`),
  UNIQUE KEY `delivery_id` (`delivery_id`,`package_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `driver_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `driver_location` (
  `driver_location_id` int(11) NOT NULL AUTO_INCREMENT,
  `driver_id` int(11) NOT NULL,
  `latitude` mediumtext NOT NULL,
  `longitude` mediumtext NOT NULL,
  `add_date_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`driver_location_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `driver_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `driver_photos` (
  `photo_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int(10) unsigned NOT NULL,
  `server_filename` varchar(255) NOT NULL,
  PRIMARY KEY (`photo_id`),
  KEY `delivery_id` (`delivery_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manifests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manifests` (
  `manifest_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `submitted_by_user_id` int(10) unsigned NOT NULL,
  `submit_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `manifest_type` enum('Delivery','Pickup') NOT NULL,
  `num_areas` smallint(5) unsigned NOT NULL DEFAULT 0,
  `num_routes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `num_deliveries` smallint(5) unsigned NOT NULL DEFAULT 0,
  `num_packages` smallint(5) unsigned NOT NULL DEFAULT 0,
  `num_items` smallint(5) unsigned NOT NULL DEFAULT 0,
  `api_submitted` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = Created on website',
  PRIMARY KEY (`manifest_id`),
  KEY `manifest_type` (`manifest_type`),
  KEY `submit_date` (`submit_date`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mast_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mast_state` (
  `state_id` int(11) NOT NULL AUTO_INCREMENT,
  `state_name` varchar(100) NOT NULL,
  `state` varchar(30) NOT NULL,
  `country_id` int(11) NOT NULL DEFAULT 1,
  `route` int(11) NOT NULL,
  `ship_via` int(11) NOT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mast_state_19_3_19`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mast_state_19_3_19` (
  `state_id` int(11) NOT NULL AUTO_INCREMENT,
  `state_name` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `package_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_types` (
  `package_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `added_by_api` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = Added on website',
  PRIMARY KEY (`package_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_pics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_pics` (
  `product_pic_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_file_path` mediumtext NOT NULL,
  `is_scan_image` enum('NO','YES') NOT NULL,
  `delivery_id` int(11) NOT NULL,
  PRIMARY KEY (`product_pic_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `route_accept`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `route_accept` (
  `route_accept_id` int(11) NOT NULL AUTO_INCREMENT,
  `route_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `route_accept_date` date NOT NULL,
  `status` enum('YES','NO') NOT NULL,
  PRIMARY KEY (`route_accept_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routes` (
  `route_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `area_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `added_by_api` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = Added on website',
  PRIMARY KEY (`route_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `signature_pics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signature_pics` (
  `signature_pic_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_filename` varchar(255) NOT NULL,
  PRIMARY KEY (`signature_pic_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` enum('0','1') NOT NULL DEFAULT '0',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email_addr` varchar(100) NOT NULL,
  `acct_username` varchar(100) NOT NULL,
  `acct_password` varchar(100) NOT NULL,
  `access_level` tinyint(3) unsigned NOT NULL,
  `is_driver` tinyint(1) unsigned NOT NULL COMMENT '1 = Yes, 0 = No',
  `default_route_id` int(10) unsigned NOT NULL,
  `current_route_id` int(10) unsigned NOT NULL COMMENT '0 if not logged in on smart device',
  `api_key` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `latitude` text NOT NULL,
  `longitude` text NOT NULL,
  `imei_no` text NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `login` (`acct_username`,`acct_password`),
  UNIQUE KEY `api_key` (`acct_username`,`api_key`),
  KEY `access_level` (`access_level`),
  KEY `is_driver` (`is_driver`),
  KEY `status` (`status`),
  KEY `current_route_id` (`current_route_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `routetra_RouteTrac_master` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `routetra_RouteTrac_master`;
DROP TABLE IF EXISTS `access_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_levels` (
  `level_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `access_menu_id` varchar(50) NOT NULL,
  PRIMARY KEY (`level_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `access_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_menu` (
  `access_menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(50) NOT NULL,
  `is_delete` int(11) NOT NULL,
  PRIMARY KEY (`access_menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_versions` (
  `api_version` tinyint(3) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`api_version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_keys` (
  `app_key_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `app_key` mediumtext NOT NULL,
  `url_domain` varchar(255) NOT NULL,
  PRIMARY KEY (`app_key_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `client_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `repository_db` varchar(255) NOT NULL,
  `api_version` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0 = no api access',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `url` mediumtext NOT NULL,
  `admin_require_packages` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0 = no, 1 = yes',
  `admin_require_items` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0 = no, 1 = yes',
  PRIMARY KEY (`client_id`),
  KEY `repository_db` (`repository_db`),
  KEY `api_version` (`api_version`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

USE routetra_RouteTrac_master;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `access_levels` WRITE;
/*!40000 ALTER TABLE `access_levels` DISABLE KEYS */;
INSERT INTO `access_levels` VALUES (1,'Administrator','1,2,3,4,6,7,8,9'),(2,'User Manager','1,5,7,8'),(3,'Route Manager','2'),(4,'Delivery Manager','1,2,3,4,5,6,7,8'),(5,'API Only','1,2,3,4');
/*!40000 ALTER TABLE `access_levels` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `access_menu` WRITE;
/*!40000 ALTER TABLE `access_menu` DISABLE KEYS */;
INSERT INTO `access_menu` VALUES (1,'Area',0),(2,'Route',0),(3,'Package Type',0),(4,'Customers',0),(6,'Users',0),(7,'Manifest',0),(8,'Access Level',0),(9,'Reports',0);
/*!40000 ALTER TABLE `access_menu` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `api_versions` WRITE;
/*!40000 ALTER TABLE `api_versions` DISABLE KEYS */;
INSERT INTO `api_versions` VALUES (0,'No API Access'),(1,'Simple API'),(2,'Advanced API');
/*!40000 ALTER TABLE `api_versions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO app_keys (client_id, app_key, url_domain) VALUES (1, '123', 'localhost');
INSERT INTO clients (client_id, client_name, repository_db, api_version, enabled, url, admin_require_packages, admin_require_items) VALUES (1, 'Demo Client', 'RouteTrac_template_repository', 2, 1, '', 0, 0);
USE RouteTrac_template_repository;
INSERT INTO client_id VALUES (1);
INSERT INTO users (status, first_name, last_name, email_addr, acct_username, acct_password, access_level, is_driver, default_route_id, api_key) VALUES (1, 'Demo', 'Admin', 'admin@example.com', 'admin', MD5('12345'), 1, 1, 0, '');

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `mast_state` WRITE;
/*!40000 ALTER TABLE `mast_state` DISABLE KEYS */;
INSERT INTO `mast_state` VALUES (1,'maharashtra','pune',1,0,0),(2,'maharashtra','sangli',1,0,0);
/*!40000 ALTER TABLE `mast_state` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

