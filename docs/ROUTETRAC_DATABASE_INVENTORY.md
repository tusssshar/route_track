# RouteTrac — Database Inventory

_Generated from the live local MariaDB on 2026-08-29 (`information_schema` + `SHOW CREATE TABLE`). Row counts are exact `COUNT(*)` at import time._

## Server

| | |
|---|---|
| Engine | MariaDB **10.4.32** (`mariadb.org binary distribution`, XAMPP) |
| Host / Port | `localhost` / `3306` |
| User (app) | `root`, empty password |
| `lower_case_table_names` | `1` (DB/table names folded to lowercase, matched case-insensitively) |
| Character set | tables are `utf8 / utf8_general_ci`; DBs recreated as `utf8mb4` on import |

## Databases used by RouteTrac

| Database | App constant | Tables | Views | Procedures | Functions | Triggers | Events |
|---|---|---:|---:|---:|---:|---:|---:|
| `RouteTrac_template_repository` | `DBN` | 19 | 0 | 0 | 0 | 0 | 0 |
| `routetra_RouteTrac_master` | `DBN_1` | 5 | 0 | 0 | 0 | 0 | 0 |

**No foreign-key constraints exist in either database.** All relationships below are *implicit* (by column naming / JOINs in PHP).

---

## DB 1 — `RouteTrac_template_repository` (operational data)

| Table | Rows | Engine | PK | Auto-inc | Purpose (from PHP usage) | Key implicit relations |
|---|---:|---|---|---|---|---|
| `areas` | 5 | MyISAM | `area_id` | next 6 | Geographic areas; one may be `is_default=1` | `routes.area_id → areas.area_id` |
| `routes` | 2 | MyISAM | `route_id` | next 3 | Delivery routes within an area | `→ areas`; `deliveries.route_id`, `users.default_route_id/current_route_id` |
| `customers` | 1 | MyISAM | `customer_id` | next 2 | Customer accounts | `customer_addresses.customer_id`, `deliveries.customer_id` |
| `customer_addresses` | 2 | MyISAM | `address_id` | next 3 | Ship-to addresses per customer | `→ customers`; `deliveries.address_id` |
| `manifests` | 2 | MyISAM | `manifest_id` | next 3 | A batch of deliveries or pickups submitted by a user; denormalised counts `num_*` | `→ users (submitted_by_user_id)`; `deliveries.manifest_id` |
| `deliveries` | 2 | MyISAM | `delivery_id` | next 3 | One stop on a manifest (delivery or pickup) | `→ manifests, routes, users(driver_id), customers, customer_addresses, signature_pics` |
| `delivery_items` | 2 | MyISAM | `item_id` | next 3 | Line items (SKU/qty/UOM) for a delivery | `→ deliveries` |
| `delivery_packages` | 2 | MyISAM | `packages_id` | next 3 | Package-type counts for a delivery | `→ deliveries`, `→ package_types` |
| `package_types` | 2 | MyISAM | `package_type_id` | next 3 | Master list of package types (`premium`, `Silver`) | `delivery_packages.package_type_id` |
| `users` | 4 | MyISAM | `user_id` | next 22 | Office users **and** drivers (`is_driver`) | `→ access_levels (access_level → level_id)`; `default_route_id/current_route_id → routes` |
| `driver_location` | 0 | MyISAM | `driver_location_id` | next 1 | GPS breadcrumb history per driver | `driver_id → users.user_id` |
| `driver_photos` | 0 | MyISAM | `photo_id` | next 1 | Proof-of-delivery photos (file names) | `→ deliveries` |
| `product_pics` | 0 | MyISAM | `product_pic_id` | next 1 | Product / scan images per delivery | `delivery_id → deliveries` |
| `signature_pics` | 0 | MyISAM | `signature_pic_id` | next 1 | Signature image file names | `deliveries.signature_pic_id` |
| `mast_state` | 2 | InnoDB | `state_id` | next 3 | US/region state master (name, code, route, ship_via, country_id) | used by `customer_address` state/city combos |
| `city_master` | 0 | InnoDB | `city_id` | next 1 | City master (has `city_name_vietnam`) — **empty, unused in current code paths** | — |
| `mast_state_19_3_19` | 55 | InnoDB | `state_id` | next 62 | **Dead backup** of `mast_state` (dated 19-03-2019) | — |
| `client_id` | 1 | MyISAM | `client_id` | — | **Dead** single-column scratch table | — |
| `route_accept` | 0 | MyISAM | `route_accept_id` | next 1 | Driver route-acceptance log (`status` enum YES/NO) — table exists, **no writer found in canonical code** (`webservices/route_accept.php` references it) | `route_id`, `driver_id → users` |

### Column detail — `RouteTrac_template_repository`

Format: `column` `type` `NULL?` `default` `extra` `key`

**areas**
- `area_id` int(10) unsigned · NO · — · auto_increment · **PRI**
- `name` varchar(255) · NO
- `is_default` tinyint(1) · NO · `0`   _(1 = Yes)_
- `added_by_api` tinyint(1) unsigned · NO · `0`   _(1 = via API, 0 = website)_

**routes**
- `route_id` int(10) unsigned · NO · auto_increment · **PRI**
- `area_id` int(10) unsigned · NO
- `name` varchar(255) · NO
- `added_by_api` tinyint(1) unsigned · NO · `0`

**customers**
- `customer_id` int(10) unsigned · NO · auto_increment · **PRI**
- `customer_name` varchar(110) · NO · KEY `customer_name(customer_name,account_number)`
- `account_number` varchar(110) · NO · KEY `account_number`
- `added_by_api` tinyint(1) unsigned · NO · `0`

**customer_addresses**
- `address_id` int(10) unsigned · NO · auto_increment · **PRI**
- `customer_id` int(10) unsigned · NO · KEY
- `ship_to_name`,`address_line_1`,`address_line_2`,`address_line_3`,`city`,`state`,`zip`,`phone` — all varchar(255) NO
- `added_by_api` tinyint(1) unsigned · NO · `0`

**manifests**
- `manifest_id` int(10) unsigned · NO · auto_increment · **PRI**
- `submitted_by_user_id` int(10) unsigned · NO
- `submit_date` timestamp · NO · `current_timestamp()` · KEY
- `manifest_type` enum('Delivery','Pickup') · NO · KEY
- `num_areas`,`num_routes`,`num_deliveries`,`num_packages`,`num_items` smallint(5) unsigned · NO · `0`
- `api_submitted` tinyint(1) unsigned · NO · `0`

**deliveries**
- `delivery_id` int(10) unsigned · NO · auto_increment · **PRI**
- `manifest_id` int(10) unsigned · NO · KEY
- `route_id` int(10) unsigned · NO · KEY `route_id(route_id,driver_id)`
- `driver_id` int(10) unsigned · NO · KEY
- `stop_number` smallint(5) unsigned · NO
- `customer_id` int(10) unsigned · NO · KEY `customer_id(customer_id,address_id)`
- `address_id` int(10) unsigned · NO · KEY
- `status` varchar(255) · NO   _(observed values: `In Transit`; code also uses `Completed`,`Refused`,`Delivered`,`Unscheduled`,`Exception`)_
- `order_number`,`cust_po_num`,`signature_text`,`exception_reason` varchar(255) · NO
- `delivery_notes`,`driver_notes`,`latitude`,`longitude`,`exception_note` mediumtext · NO
- `scheduled_delivery` date · NO
- `delivery_time` datetime · NO
- `signature_pic_id` int(10) unsigned · NO
- `added_date` datetime · NO · `current_timestamp()`

**delivery_items**
- `item_id` int(10) unsigned · NO · auto_increment · **PRI**
- `delivery_id` int(10) unsigned · NO
- `sku`,`product_name` varchar(255) · NO
- `description` varchar(2048) · NO
- `uom` char(2) · NO
- `qty` smallint(6) · NO · `qty_delivered` int(11) · NO
- `upc_code` mediumtext · NO

**delivery_packages**
- `packages_id` int(10) unsigned · NO · auto_increment · **PRI**
- `delivery_id` int(10) unsigned · NO · KEY `delivery_id(delivery_id,package_type_id)`
- `package_type_id` int(10) unsigned · NO
- `qty` smallint(6) · NO · `qty_delivered` int(11) · NO

**package_types**
- `package_type_id` int(10) unsigned · NO · auto_increment · **PRI**
- `name` varchar(255) · NO
- `added_by_api` tinyint(1) unsigned · NO · `0`

**users**
- `user_id` int(10) unsigned · NO · auto_increment · **PRI**
- `status` enum('0','1') · NO · `'0'` · KEY
- `first_name`,`last_name`,`email_addr`,`acct_username`,`acct_password` varchar(100) · NO
- `access_level` tinyint(3) unsigned · NO · KEY   _(→ `routetra_RouteTrac_master.access_levels.level_id`)_
- `is_driver` tinyint(1) unsigned · NO · KEY   _(1 = Yes)_
- `default_route_id`,`current_route_id` int(10) unsigned · NO   _(`current_route_id` KEY; 0 = not logged in on device)_
- `api_key` varchar(255) latin1 · NO · `''`
- `latitude`,`longitude`,`imei_no` text · NO
- UNIQUE `login(acct_username,acct_password)` · UNIQUE `api_key(acct_username,api_key)`

**driver_location**
- `driver_location_id` int(11) · NO · auto_increment · **PRI**
- `driver_id` int(11) · NO
- `latitude`,`longitude` mediumtext · NO
- `add_date_time` datetime · NO · `current_timestamp()`

**driver_photos**: `photo_id` PRI auto_inc · `delivery_id` int unsigned KEY · `server_filename` varchar(255)
**signature_pics**: `signature_pic_id` PRI auto_inc · `server_filename` varchar(255)
**product_pics**: `product_pic_id` PRI auto_inc · `product_file_path` mediumtext · `is_scan_image` enum('NO','YES') · `delivery_id` int(11)
**mast_state**: `state_id` PRI auto_inc · `state_name` varchar(100) · `state` varchar(30) · `country_id` int `1` · `route` int · `ship_via` int
**city_master**: `city_id` PRI auto_inc · `city_name` varchar(100) · `city_name_vietnam` varchar(255)
**mast_state_19_3_19**: `state_id` PRI auto_inc · `state_name` varchar(255) · `state` varchar(255)
**client_id**: `client_id` int(10) unsigned · NO · **PRI**
**route_accept**: `route_accept_id` PRI auto_inc · `route_id` int · `driver_id` int · `route_accept_date` date · `status` enum('YES','NO')

---

## DB 2 — `routetra_RouteTrac_master` (tenant registry & access control)

| Table | Rows | Engine | PK | Purpose | Relations |
|---|---:|---|---|---|---|
| `clients` | 3 | MyISAM | `client_id` | Tenant registry — each row = a client + its repository DB name + API tier | `repository_db` → a `*_template_repository` schema; `api_version → api_versions` |
| `access_levels` | 5 | MyISAM | `level_id` | Roles; `access_menu_id` is a **CSV of allowed `access_menu` ids** | `users.access_level → level_id` |
| `access_menu` | 8 | InnoDB | `access_menu_id` | Menu/feature list guarded by access levels | referenced as CSV members by `access_levels.access_menu_id` |
| `api_versions` | 3 | MyISAM | `api_version` | API capability tiers | `clients.api_version` |
| `app_keys` | 1 | MyISAM | `app_key_id` | API keys → client + allowed domain | `client_id → clients.client_id` |

### Column detail — `routetra_RouteTrac_master`

**clients**: `client_id` PRI auto_inc · `client_name` varchar(255) · `repository_db` varchar(255) KEY · `api_version` tinyint(3) unsigned `0` KEY _(0 = no API access)_ · `enabled` tinyint(1) unsigned `1` KEY · `url` mediumtext · `admin_require_packages` tinyint(1) unsigned `0` · `admin_require_items` tinyint(1) unsigned `0`
**access_levels**: `level_id` tinyint(3) unsigned PRI auto_inc · `name` varchar(255) · `access_menu_id` varchar(50) _(CSV)_
**access_menu**: `access_menu_id` int(11) PRI auto_inc · `menu_name` varchar(50) · `is_delete` int(11)
**api_versions**: `api_version` tinyint(3) unsigned **PRI** · `name` varchar(255)
**app_keys**: `app_key_id` int(11) PRI auto_inc · `client_id` int(11) · `app_key` mediumtext · `url_domain` varchar(255)

### Reference data (actual rows)

**access_levels**
| level_id | name | access_menu_id (CSV → menus) |
|---|---|---|
| 1 | Administrator | 1,2,3,4,6,7,8,9 |
| 2 | User Manager | 1,5,7,8 |
| 3 | Route Manager | 2 |
| 4 | Delivery Manager | 1,2,3,4,5,6,7,8 |
| 5 | API Only | 1,2,3,4 |

**access_menu**: 1 Area · 2 Route · 3 Package Type · 4 Customers · 6 Users · 7 Manifest · 8 Access Level · 9 Reports  _(note: id 5 is referenced by levels 2 & 4 but no row 5 exists)_

**api_versions**: 0 No API Access · 1 Simple API · 2 Advanced API

**clients**
| client_id | client_name | repository_db | api_version | enabled | admin_require_packages | admin_require_items |
|---|---|---|---|---|---|---|
| 1 | Test Client | routetra_RouteTrac_template_repository | 2 | 1 | 1 | 1 |
| 2 | Sparken | routetra_RouteTrac_template_sparken | 1 | 1 | 0 | 0 |
| 3 | ABC | routetra_RouteTrac_template_abc | 2 | 1 | 0 | 0 |

**app_keys**: `(1, client_id=1, app_key='123', url_domain='65.60.3.216')`

**areas**: pune(1), mumbai(2), kolhapur(3), **sangli(4, is_default=1)**, Satara(5)
**routes**: `sparken tech`(1 → area 1), `kolh_Dem`(2 → area 3)
**package_types**: `premium`(1), `Silver`(2)
**mast_state** (2 rows) + **mast_state_19_3_19** (55 rows, dead)
**users** (4): admin(al 1, driver), eddie(al 1, driver), Barry(al 1, driver), mdkamble(al 2, driver) — all `status=1`

> Note: `clients.repository_db` values carry a `routetra_` prefix (`routetra_RouteTrac_template_repository`) but the **local** `config.php` connects to `RouteTrac_template_repository` (no prefix). The multi-tenant lookup path in `functions::masterDBInfo()` is not exercised locally.
