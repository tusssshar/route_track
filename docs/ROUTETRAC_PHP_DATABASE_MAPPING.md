# RouteTrac — PHP → Database Mapping

_Traced from the actual code. Every query below is built by string concatenation via the `database` class (`libraries/dbSqliConnection.php`); `->myCustomQuery($sql)` runs raw SQL, `->data()/->where()/->insert()/->update()/->delete()` build it._

## Data-access primitives

| Call | Effect |
|---|---|
| `new database()` | grabs the global mysqli connection (`$oGlobalConnection`, opened in `dbSqliConnection.php` against `DBN`) |
| `new dbSqlConnection(); ->Connect(HST_1,USR_1,PWD_1,DBN_1)` | re-points `$oGlobalConnection` at the **master** DB for the next queries |
| `$db->myCustomQuery($sql)` | SELECT → fills `$db->row_count`, `$db->f_<column>` (current row), `$db->moveto($i)` |
| `$db->data('col',$val); $db->where('...'); $db->insert('t')` / `->update('t')` | write; `->id` = last insert id |
| `$functions->createCombo($table,$idCol,$labelCol,$selected,$orderBy,$where)` | renders `<option>`s straight from a table |
| `$functions->user_access_level($menuId,$levelId)` | `SELECT access_menu_id FROM access_levels WHERE level_id=?` then `in_array()` on the CSV |

---

## Business flow 1 — Admin login

| Step | Detail |
|---|---|
| UI | `index.php` — username / password form (POST to self) |
| Controller | `index.php` (lines 8–42) |
| Model | `database` class |
| Query | `SELECT user_id,acct_username,acct_password,access_level,api_key FROM users WHERE acct_username='{u}' AND acct_password='{md5(p)}' AND status='1'` |
| DB / table | `RouteTrac_template_repository.users` |
| Columns | `user_id`, `acct_username`, `acct_password` (md5), `access_level`, `api_key`, `status` |
| Rule | reject if `access_level == 5` ("API Only") |
| Result | `$_SESSION['routetrac-admin']` = `al_id`, `al_username`, `access_level`, `api_key` → redirect `admin/home.php` |
| Guard on every page | `admin/authorized.php` (session check) + `functions::user_access_level($menuId, $_SESSION[...]['access_level'])` vs `routetra_RouteTrac_master.access_levels.access_menu_id` |

## Business flow 2 — Areas master (representative of area/route/customer/package/user)

| | |
|---|---|
| UI | `admin/m/master/area.php` (shell) → loads `admin/m/master/ajax/area.php` (list) via `$.post` |
| Controller (list) | `admin/m/master/ajax/area.php` |
| Query (list) | `SELECT SQL_CALC_FOUND_ROWS areas.* FROM areas WHERE 1=1 {search} ORDER BY areas.area_id DESC LIMIT {off},25` ; total via `SELECT FOUND_ROWS()` |
| Controller (create/update) | `admin/m/master/ajax/area_new.php` — `$db->data('name',...); $db->data('is_default',...);` then `insert('areas')` or `where('area_id='.$id); update('areas')` |
| Controller (delete) | same list file: `?delete_id=N` → `$db->where('area_id='.N); $db->delete('areas')` |
| Validators (AJAX) | `admin/ajax/admin_ajax_action.php?action=check_area` → `SELECT name FROM areas WHERE name='{x}' AND area_id!='{id}'` (echo `true`/`false`); `check_default_area` enforces single `is_default=1` |
| DB / table | `RouteTrac_template_repository.areas` |
| → Next.js field mapping | `area_id` → value, `name` → label; `is_default` boolean; `added_by_api` read-only origin flag |

Same pattern, different tables:

| Module | List file | Form file | Table | Uniqueness validator (`admin_ajax_action.php?action=`) | Dropdowns it renders |
|---|---|---|---|---|---|
| Routes | `ajax/routes.php` | `ajax/routes_new.php` | `routes` | `check_route`, `check_route_area` | `areas` (`createCombo("areas","area_id","name",...)`) |
| Package Types | `ajax/packages.php` | `ajax/packages-new.php` | `package_types` | `check_package` | — |
| Customers | `ajax/customer.php` | `ajax/customer_new.php` | `customers` | `check_acct_no` | — |
| Customer Addr | `ajax/customer_address.php` | `ajax/customer_address_new.php` | `customer_addresses` | — | `mast_state` (state **and** city both from `mast_state.state`) |
| Users | `ajax/users.php` | `ajax/users_new.php` | `users` (+ `LEFT JOIN routetra_RouteTrac_master.access_levels AL ON U.access_level=AL.level_id`) | `check_email`, `check_user_name` | `routes` (default route), `access_levels` (master DB, `level_id != 5` when editing self), `status` (static Active/Inactive) |
| Access Levels | `ajax/access_level.php` | `ajax/access_level_new.php` | `access_levels`, `access_menu` (master DB) | — | `access_menu` checkboxes → stored as CSV in `access_levels.access_menu_id` |

## Business flow 3 — Build a manifest

| Step | File | SQL / write | Tables |
|---|---|---|---|
| Open builder | `admin/m/master/create_manifest.php` → `admin/m/master/ajax/manifests_new.php` | dropdowns: `createCombo("routes",...)`, `createCombo("users",...,"is_driver=1")`, `createCombo("customers",...)`, `createCombo("customer_addresses",...,"customer_id=".$cid)`, `createCombo("package_types",...)` | `routes`,`users`,`customers`,`customer_addresses`,`package_types` |
| Address for chosen customer | `admin/ajax/admin_ajax_action.php?action=get_address&cust_id=N` | `createCombo("customer_addresses","address_id","address_line_1,address_line_2,address_line_3",...,"customer_id=".$cid)` | `customer_addresses` |
| Submit (new) | `ajax/manifests_new.php` (≈ lines 203–365) | `insert("manifests")` → `insert("deliveries")` (with `manifest_id`) → per row `insert("delivery_packages")`, `insert("delivery_items")` | `manifests`,`deliveries`,`delivery_packages`,`delivery_items` |
| Submit (edit) | same | `update("deliveries")`, `update/insert("delivery_packages")`, `update/insert("delivery_items")` | same |
| Recompute counts | same (≈ 378–425) | `SELECT count(delivery_id), count(DISTINCT route_id) ...`; `SELECT SUM(DP.qty) ...`; `SELECT SUM(DI.qty) ...`; then `update("manifests")` setting `num_deliveries/num_routes/num_areas/num_packages/num_items` | `manifests`,`deliveries`,`delivery_packages`,`delivery_items`,`routes` |
| Delete a stop | `admin_ajax_action.php?action=get_delivery_table&delete_id=N` | `$db->where("delivery_id=".N); $db->delete("deliveries")` | `deliveries` |
| Delete a line item | `admin_ajax_action.php?action=delete_item&item_id=N` | `delete("delivery_items")` | `delivery_items` |

## Business flow 4 — Manifest detail report

| | |
|---|---|
| UI | `admin/m/master/detail_report.php` → `admin/m/master/ajax/detail_report.php?manifest_id=N` |
| Header query | `SELECT ... FROM manifests M LEFT JOIN users U ON M.submitted_by_user_id=U.user_id WHERE M.manifest_id='{N}'` |
| Body query | `SELECT ... FROM manifests M JOIN deliveries D ON M.manifest_id=D.manifest_id LEFT JOIN customers C ON D.customer_id=C.customer_id LEFT JOIN routes R ON D.route_id=R.route_id LEFT JOIN customer_addresses CA ON D.address_id=CA.address_id LEFT JOIN users U ON D.driver_id=U.user_id LEFT JOIN signature_pics SP ON D.signature_pic_id=SP.signature_pic_id LEFT JOIN users SB ON M.submitted_by_user_id=SB.user_id WHERE D.manifest_id='{N}'` |
| Per delivery | items: `SELECT DI.sku,DI.qty,DI.product_name,DI.description,DI.uom,DI.qty_delivered FROM deliveries D LEFT JOIN delivery_items DI ON D.delivery_id=DI.delivery_id WHERE DI.delivery_id='{id}'` ; packages: `... delivery_packages DP LEFT JOIN package_types PT ON DP.package_type_id=PT.package_type_id ...` ; images: `SELECT product_pic_id,product_file_path FROM product_pics WHERE delivery_id='{id}'` |
| Tables | `manifests`,`deliveries`,`delivery_items`,`delivery_packages`,`package_types`,`customers`,`customer_addresses`,`routes`,`users`,`signature_pics`,`product_pics` |

## Business flow 5 — Live driver map

| | |
|---|---|
| UI | `admin/m/google_map/locate_drivers.php` — driver `<select>` from `SELECT ... FROM users WHERE is_driver=1` (or all in-transit); Google Maps JS API |
| AJAX | `admin/m/google_map/ajax/live_positions.php` (JSON; OpenStreetMap/Leaflet) |
| Driver positions | `SELECT D.delivery_id,D.order_number,U.first_name,U.last_name,U.user_id,U.latitude,U.longitude FROM deliveries D LEFT JOIN users U ON D.driver_id=U.user_id WHERE D.status='In Transit' AND scheduled_delivery='{today}' GROUP BY U.user_id` |
| Stop positions | `SELECT ... FROM deliveries D LEFT JOIN customers C ON D.customer_id=C.customer_id WHERE D.status='In Transit' AND scheduled_delivery='{today}' AND D.latitude!='' AND longitude!=''` |
| Tables | `deliveries`,`users`,`customers` |
| Driver coords source | `users.latitude/longitude` (updated by API `get_driver_current_location.php`) and `deliveries.latitude/longitude` |

## Business flow 6 — Dashboard

| | |
|---|---|
| UI | `admin/home.php` widget → `admin/ajax/home.php` (`deliver_type` = Delivery/Pickup/Both, `limit`) |
| Query | `SELECT d.*,mani.manifest_type,cust.customer_name,cust.account_number,rout.name AS rname FROM deliveries d INNER JOIN manifests mani ON d.manifest_id=mani.manifest_id INNER JOIN customers cust ON d.customer_id=cust.customer_id INNER JOIN routes rout ON d.route_id=rout.route_id WHERE 1=1 {type} ORDER BY d.delivery_time DESC LIMIT {n}` |
| Tables | `deliveries`,`manifests`,`customers`,`routes` |

## Business flow 7 — Mobile API: driver day

| Endpoint | Query core | Tables | Write |
|---|---|---|---|
| `login_user.php` | `SELECT * FROM users WHERE (acct_username=? OR email_addr=?) AND acct_password=md5(?)` → then `UPDATE users SET imei_no=? WHERE user_id=?` | `users` | yes (`imei_no`) |
| `route_list.php` | `SELECT DISTINCT D.route_id,R.name FROM deliveries D LEFT JOIN routes R ... WHERE D.driver_id=? AND D.status='In Transit' AND scheduled_delivery=CURDATE` + per-route delivery/pickup/item/package sums + `route_accept.status` | `deliveries`,`routes`,`manifests`,`delivery_items`,`delivery_packages`,`route_accept` | no |
| `order_list.php` | `SELECT ... FROM deliveries D LEFT JOIN customers C ... LEFT JOIN manifests M ... LEFT JOIN customer_addresses CA ... WHERE D.customer_id=? AND D.driver_id=? AND D.status='In Transit' AND scheduled_delivery=CURDATE AND D.route_id=?` + package count | `deliveries`,`customers`,`manifests`,`customer_addresses`,`delivery_packages` | no |
| `order_details.php` | full delivery record + items + packages by `delivery_id` | `deliveries`,`delivery_items`,`delivery_packages`,`customers`,`customer_addresses` | no |
| `route_accept.php` | check/insert/update `route_accept` for (driver,route,today); `abendon_status='YES'` sets `status='NO'` | `route_accept` | yes |
| `get_driver_current_location.php` | `INSERT INTO driver_location(...)` + `UPDATE users SET latitude/longitude WHERE user_id=?` | `driver_location`,`users` | yes |
| `upload_signature.php` | `INSERT signature_pics` → `UPDATE deliveries SET signature_text/signature_pic_id/driver_notes` → loop `UPDATE delivery_packages SET qty_delivered` → loop `UPDATE delivery_items SET qty_delivered` | `signature_pics`,`deliveries`,`delivery_packages`,`delivery_items` | yes |
| `upload_photo.php` | `move_uploaded_file()` → `INSERT driver_photos` / `INSERT product_pics` | `driver_photos`,`product_pics` | yes |
| `update_item_qty.php` | `INSERT product_pics` ×(image1..3, is_scan_image) → `UPDATE delivery_items SET qty=?, driver_notes=?` | `product_pics`,`delivery_items` | yes |
| `edit_item_qty.php` / `update_package_qty.php` / `update_driver_note.php` | `UPDATE delivery_items` / `delivery_packages` / `deliveries` | those | yes |
| `check_licence_key.php` | `SELECT client_id,url_domain FROM app_keys WHERE app_key=?` → `SELECT client_name,repository_db FROM clients WHERE client_id=?` | `app_keys`,`clients` (master) | no |
| `check_upc_code.php` | `SELECT ... FROM delivery_items WHERE upc_code=? AND delivery_id=?` | `delivery_items` | no |
