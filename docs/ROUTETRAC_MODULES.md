# RouteTrac — Modules

_All URLs are relative to `http://localhost/projects/routetrac/`. Status legend: **WORKING** (tested via HTTP, 200 + expected output, no fatal) · **PARTIALLY WORKING** (loads but a sub-feature unverified / needs params/data) · **NOT VERIFIED** (not exercised) · **BROKEN**._

Grouping is derived from `routetra_RouteTrac_master.access_menu` (the app's own menu taxonomy) plus the `webservices/` API surface.

## Web application (admin panel)

| Module | URL | Controller (PHP) | Views / fragments | Models / data access | AJAX endpoints | Tables | Main functionality | Status |
|---|---|---|---|---|---|---|---|---|
| Authentication | `index.php`, `admin/logout.php`, `admin/authorized.php` | `index.php` | inline login form + `admin/templates/*` | `database` class → `users` | — | `users` (repo) | md5 login, session `routetrac-admin`, blocks access_level 5, per-page menu guard via `functions::user_access_level()` | **WORKING** (login `admin`/`12345` → 302 → dashboard) |
| Dashboard | `admin/home.php` | `admin/home.php` | `admin/home.php` + `admin/ajax/home.php`, `admin/ajax/home_route_status.php` | `database` → JOIN deliveries/manifests/customers/routes | `admin/ajax/home.php`, `admin/ajax/home_route_status.php` | `deliveries`,`manifests`,`customers`,`routes` | "Quick Delivery Lookup" form + recent Delivery/Pickup widget + route status | **WORKING** (home.php 200; ajax/home.php 200) |
| Areas | `admin/m/master/area.php` | `area.php` | `area.php`, `ajax/area.php` (list), `ajax/area_new.php` (form) | `database` → `areas` | `ajax/area.php`, `ajax/area_new.php`, `admin/ajax/admin_ajax_action.php?action=check_area\|check_default_area` | `areas` | CRUD, one default area, search, paging | **WORKING** (list endpoint 200, 5 rows) |
| Routes | `admin/m/master/routes.php` | `routes.php` | `routes.php`, `ajax/routes.php`, `ajax/routes_new.php` | `database` → `routes` (combo from `areas`) | `ajax/routes.php`, `ajax/routes_new.php`, `admin_ajax_action.php?action=check_route\|check_route_area` | `routes`,`areas` | CRUD, area dropdown, uniqueness by (name,area) | **WORKING** (list 200, 2 rows) |
| Package Types | `admin/m/master/packages.php` | `packages.php` | `packages.php`, `ajax/packages.php`, `ajax/packages-new.php` | `database` → `package_types` | `ajax/packages.php`, `ajax/packages-new.php`, `admin_ajax_action.php?action=check_package\|get_packages_list` | `package_types` | CRUD | **WORKING** (list 200, 2 rows) |
| Customers | `admin/m/master/customer.php` | `customer.php` | `customer.php`, `ajax/customer.php`, `ajax/customer_new.php` | `database` → `customers` | `ajax/customer.php`, `ajax/customer_new.php`, `admin_ajax_action.php?action=check_acct_no` | `customers` | CRUD, unique account number | **WORKING** (list 200, 1 row) |
| Customer Addresses | `admin/m/master/customer_address.php` | `customer_address.php` | `customer_address.php`, `ajax/customer_address.php`, `ajax/customer_address_new.php` | `database` → `customer_addresses`; state/city combos from `mast_state` | `ajax/customer_address.php`, `ajax/customer_address_new.php`, `admin_ajax_action.php?action=get_address` | `customer_addresses`,`customers`,`mast_state` | CRUD addresses per customer | **PARTIALLY WORKING** (page 200; `ajax/customer_address.php` returned 0 bytes — expects a `customer_id` param) |
| Users / Drivers | `admin/m/master/users.php` | `users.php` | `users.php`, `ajax/users.php`, `ajax/users_new.php` | `database` → `users` LEFT JOIN `routetra_RouteTrac_master.access_levels` | `ajax/users.php`, `ajax/users_new.php`, `admin_ajax_action.php?action=check_email\|check_user_name` | `users`,`access_levels`,`routes` | CRUD, access-level & default-route dropdowns, CSV **import**, **export** (`export.php?value=export_users`), API-access checkbox | **WORKING** (list 200, 7 `<tr>`; import/export code present, not run) |
| Access Levels | `admin/m/master/access_level.php` | `access_level.php` | `access_level.php`, `ajax/access_level.php`, `ajax/access_level_new.php` | `database` (master conn) → `access_levels`,`access_menu` | `ajax/access_level.php`, `ajax/access_level_new.php` | `access_levels`,`access_menu` (master DB) | CRUD roles, tick allowed menus (stored as CSV) | **WORKING** (list 200) |
| Manifests (list) | `admin/m/master/manifests.php`, `manifests_1.php` | `manifests.php` | `manifests.php`, `ajax/manifests.php` | `database` → `manifests` JOIN users | `ajax/manifests.php`, `ajax/detail_report.php` | `manifests`,`users`,`deliveries` | Browse submitted manifests, drill to detail | **WORKING** (page 200; `ajax/manifests.php` 200, ~17 rows) |
| Create / Edit Manifest | `admin/m/master/create_manifest.php` | `create_manifest.php` | `create_manifest.php`, `ajax/manifests_new.php` | `database` → inserts `manifests`,`deliveries`,`delivery_items`,`delivery_packages`; recomputes `manifests.num_*` | `ajax/manifests_new.php`, `admin_ajax_action.php?action=get_delivery_table\|get_packages_list\|delete_item` | `manifests`,`deliveries`,`delivery_items`,`delivery_packages`,`routes`,`users`,`customers`,`customer_addresses`,`package_types` | Build a Delivery/Pickup manifest: pick route/driver/customer/address, add line items & packages | **PARTIALLY WORKING** (`create_manifest.php` 200, `ajax/manifests_new.php` renders; full submit → multi-table insert not run end-to-end) |
| Manifest Detail Report | `admin/m/master/detail_report.php` + `ajax/detail_report.php` | `detail_report.php` | `ajax/detail_report.php` | big multi-JOIN over deliveries/customers/routes/addresses/users/signature_pics/items/packages/product_pics | `ajax/detail_report.php?manifest_id=N` | `manifests`,`deliveries`,`delivery_items`,`delivery_packages`,`customers`,`customer_addresses`,`routes`,`users`,`signature_pics`,`product_pics`,`package_types` | Printable per-manifest breakdown incl. POD images | **PARTIALLY WORKING** (returns 57–64 bytes without `manifest_id`; needs a valid id) |
| Reports – Delivery Status | `admin/m/reports/delivery_status.php` | `delivery_status.php` | same file | `SELECT DISTINCT name FROM routes`, `SELECT DISTINCT status FROM deliveries` | inline | `routes`,`deliveries` | Filter deliveries by route / status / type | **WORKING** (200, 14.9 KB) |
| Reports – Orders | `admin/m/reports/orders_report.php` | `orders_report.php` | same file | routes list, driver list (`users WHERE is_driver=1`), status/type static | inline | `deliveries`,`routes`,`users`,`manifests` | Orders by status/route/driver/type | **WORKING** (200, 15.6 KB) |
| Reports – Route Status | `admin/m/reports/route_status.php` | `route_status.php` | same file | `database` → routes/deliveries | inline | `routes`,`deliveries` | Route completion overview | **PARTIALLY WORKING** (200, 11.4 KB; no query text visible in header scan — needs UI check) |
| Live Fleet Map | `admin/m/google_map/locate_drivers.php` | `locate_drivers.php` | same + Leaflet/OpenStreetMap (no Google key) | `trips`, `driver_location`, `deliveries`, `users`, `vehicles` | `ajax/live_positions.php` (`mode=live` / `mode=history`) | `trips`,`driver_location`,`deliveries`,`users`,`vehicles` | Running vehicles at last GPS position with track, signal age, other drivers, 30 s refresh; driver history by date range | **WORKING** (rebuilt 2026-10-02) |
| Master-value config | `admin/m/master/manage_master.php`, `manage_master_sub_values.php`, `options.php` | those files | those files + `ajax/options.php` | `database` → generic master tables | `ajax/options.php` | (generic) | Manage lookup values | **PARTIALLY WORKING** (`options.php` 200 but 0 data rows — backing table appears empty/absent locally) |
| CSV Import (Users) | `admin/m/master/users.php` (`submit1`) | `users.php` | inline import panel | reads CSV, `database->insert("users")`, auto-creates missing `access_levels`/`routes` | — | `users`,`access_levels`,`routes` | Bulk user import from `IMPORT_JOBS_TEMPLATE.csv` / `CUSTOMER.csv` template | **NOT VERIFIED** (no upload performed) |
| CSV Export | `admin/m/master/export.php?value=export_users` | `export.php` | streamed CSV | `database` → `users` | — | `users` | Export users to CSV | **NOT VERIFIED** |

### Dead / historical (present but not part of the live app)

`admin/m/master/ajax/_BK_/*` · `admin/m/master/ajax/*_<date>.php` (e.g. `manifests_new_09_01_2017.php`, `customer_address_19_3_19.php`) · `admin/m/master/ajax/skills*.php`, `points_consumption*.php`, `new_points_consumption.php` (features from a different product — reference tables like `pc_*` / `skills` do **not** exist in the DB) · `admin/index1.php`, `admin/test.php`, `phpinfo.php`, `test.php`.

## Mobile / REST API — `webservices/` (the live API)

| Endpoint | Method | Params (all via `$_REQUEST`) | Reads / Writes | Tables | Status |
|---|---|---|---|---|---|
| `login_user.php` | POST | `app_key`, `acct_username`, `acct_password`, `imei_no` | read `users`; **write** `users.imei_no` | `users` | **WORKING** (returns success JSON) |
| `check_licence_key.php` | GET/POST | `app_key` | read `app_keys`, `clients` | `app_keys`,`clients` (master) | **WORKING** (code verified; returns client_name/repository_db) |
| `reasons.php` | POST | `app_key` | none — **static PHP array** | — | **WORKING** (verified: 6 hardcoded reasons) |
| `route_list.php` | POST | `app_key`, `driver_id` | read deliveries/routes/manifests/items/packages/route_accept for CURDATE | `deliveries`,`routes`,`manifests`,`delivery_items`,`delivery_packages`,`route_accept` | **PARTIALLY WORKING** ("Please pass all parameter" without `driver_id`) |
| `route_accept.php` | POST | `app_key`, `driver_id`, `route_id`, `abendon_status` | **write** `route_accept` (insert/update) | `route_accept` | **NOT VERIFIED** (needs valid ids) |
| `order_id_list.php` | POST | `app_key`, driver/route | read `deliveries` | `deliveries` | **NOT VERIFIED** |
| `order_list.php` | POST | `app_key`, `customer_id`, `driver_id`, `route_id` | read deliveries + addresses + packages | `deliveries`,`customers`,`customer_addresses`,`manifests`,`delivery_packages` | **PARTIALLY WORKING** ("Please pass all parameter") |
| `order_details.php` | POST | `app_key`, `delivery_id` | read full delivery + items + packages | `deliveries`,`delivery_items`,`delivery_packages`,`customers`,`customer_addresses` | **NOT VERIFIED** |
| `order_summary.php` | POST | `app_key`, driver/route | aggregate counts | `deliveries`,`delivery_items`,`delivery_packages` | **NOT VERIFIED** |
| `customer_list.php` | POST | `app_key`, `driver_id`, `route_id` | read deliveries+customers+route_accept | `deliveries`,`customers`,`route_accept` | **PARTIALLY WORKING** ("Please pass all parameter") |
| `customer_list_test.php` | POST | — | test variant | — | dev scratch |
| `cust_order_wise_lat_long.php` | POST | `app_key`, driver/route | read `deliveries` lat/long | `deliveries` | **NOT VERIFIED** |
| `get_driver_current_location.php` | POST | `app_key`, `driver_id`, `latitude`, `longitude` | **write** `driver_location` (insert), `users` (update current lat/long) | `driver_location`,`users` | **NOT VERIFIED** (writes; needs valid driver) |
| `get_order_exception_reason_and_note.php` | POST | `app_key`, `delivery_id` | read + possibly write exception fields | `deliveries` | **NOT VERIFIED** |
| `upload_signature.php` | POST | `app_key`, `delivery_id`, `signature_text`, `server_filename`, `package_id[]`, `package_qty`, `item_qty`, `item_id`, `driver_notes` | **write** `signature_pics` (insert), `deliveries` (update), `delivery_packages` (update), `delivery_items` (update) | `signature_pics`,`deliveries`,`delivery_packages`,`delivery_items` | **NOT VERIFIED** (multi-table write) |
| `upload_photo.php` | POST + file | `app_key`, `delivery_id`, image | `move_uploaded_file`, **write** `driver_photos` / `product_pics` | `driver_photos`,`product_pics` | **NOT VERIFIED** |
| `update_item_qty.php` | POST | `app_key`, `item_id`, `qty`, `delivery_id`, `driver_notes`, `image1..3`, `is_scan_image` | **write** `product_pics` (insert ×N), `delivery_items` (update) | `product_pics`,`delivery_items` | **NOT VERIFIED** |
| `edit_item_qty.php` | POST | `app_key`, `item_id`, `qty` | **write** `delivery_items` | `delivery_items` | **NOT VERIFIED** |
| `update_package_qty.php` | POST | `app_key`, `packages_id`, `qty` | **write** `delivery_packages` | `delivery_packages` | **NOT VERIFIED** |
| `update_driver_note.php` | POST | `app_key`, `delivery_id`, `driver_notes` | **write** `deliveries` | `deliveries` | **NOT VERIFIED** |
| `check_upc_code.php` | POST | `app_key`, `upc_code`, `delivery_id` | read `delivery_items` | `delivery_items` | **NOT VERIFIED** |
| `message.php` | include | — | defines `$RESPONSE_KEY`/`$RESPONSE_SUCCESS`/`$RESPONSE_ERROR` constants; **not a standalone endpoint** | — | n/a |
| `test.php` | GET | — | dev scratch | — | n/a |

## Secondary API — `webservice-dipali/` (office-side data sync)

| Endpoint(s) | Method | Tables | Notes | Status |
|---|---|---|---|---|
| `search_area.php`, `create_new_area.php`, `delete_area.php` | POST | `areas` | key param name is `api_key` | **PARTIALLY WORKING** (executes, returns `{"status":0,"msg":"Error!! please provide API Key."}` without key) |
| `search_customer.php`, `create_new_customer.php`, `delete_customer.php`, `create_customer_address.php`, `update_customer_address.php` | POST | `customers`,`customer_addresses` | | **NOT VERIFIED** |
| `search_routes.php`, `create_new_route.php` (+`_old`), `delete_route.php` | POST | `routes`,`areas` | | **NOT VERIFIED** |
| `search_packages.php`, `create_new_pckg_type.php`, `delete_pckg_type.php` | POST | `package_types` | | **NOT VERIFIED** |
| `search_address.php` | POST | `customer_addresses` | | **NOT VERIFIED** |
| `api_call/*.php` | — | — | client-side call helpers, not endpoints | n/a |

## Legacy — `_api/`

| File | Purpose | Status |
|---|---|---|
| `_api/create_delivery_manifest.php` | Older single-call manifest creator (`include ../bootstrap.php`, POST fields) | **NOT VERIFIED** — routable, superseded by `create_manifest.php` UI flow |
| `_api/api_call/api_call.php`, `_api/library*/api_functions*.php`, `_api/libraries/custom_validations*.php` | helper libs | n/a |

## `api/`

`api/bootstrap.php` + `api/libraries/` only. **No endpoints. Dead scaffold** — not shimmed for PHP 8.
