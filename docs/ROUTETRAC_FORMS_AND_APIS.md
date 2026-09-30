# RouteTrac — Forms & APIs

_Field-level detail from the actual form files under `admin/m/master/ajax/` and the endpoints under `webservices/` + `webservice-dipali/`._

## Dropdown / option-source summary (DATABASE vs STATIC)

| Field | Where used | Source | Detail |
|---|---|---|---|
| Area (route form) | `routes_new.php` | **DATABASE** | `createCombo("areas","area_id","name", $sel, " order by name asc")` → value = `areas.area_id`, label = `areas.name` |
| Default Route (user form) | `users_new.php` | **DATABASE** | `createCombo("routes","route_id","name", …)` |
| Access Level (user form) | `users_new.php` | **DATABASE (master DB)** | `createCombo("access_levels","level_id","name", …, $apiOnlyWhere)` — `$apiOnlyWhere = " level_id != 5 "` when the user edits their own record, else `"+"` (no filter) |
| Menu permissions (access-level form) | `access_level_new.php` | **DATABASE (master DB)** | one checkbox per `access_menu` row; `value = access_menu_id`; submitted `menu_name[]` joined to CSV → `access_levels.access_menu_id` |
| Status (user form) | `users_new.php` | **STATIC** | hardcoded `<option value="1">Active` / `<option value="0">Inactive` |
| API Access (user form) | `users_new.php` | **STATIC** checkbox | value `1`; visible only if logged-in `access_level` ∈ {1,2}; forced-checked+disabled when selected access level = 5 |
| Is Driver (user form) | `users_new.php` | **STATIC** checkbox | value `1`; toggles the Default-Route row |
| Is Default (area form) | `area_new.php` | **STATIC** checkbox | value `1`; server enforces single default via `admin_ajax_action.php?action=check_default_area` |
| Route / Driver / Customer / Address / Package (manifest builder) | `manifests_new.php` | **DATABASE** | `createCombo("routes","route_id","name")`, `createCombo("users","user_id","first_name", …, "is_driver=1")`, `createCombo("customers","customer_id","customer_name")`, `createCombo("customer_addresses","address_id","address_line_1,address_line_2,address_line_3", …, "customer_id=".$cid)`, `createCombo("package_types","package_type_id","name")` |
| Order status (manifest builder) | `manifests_new.php` | **STATIC** | only `<option value="In Transit">` (older `manifests_new_09_01_2017.php` also had `Completed`,`Refused` — that file is dead) |
| Route filter (reports, map) | `delivery_status.php`, `orders_report.php` | **DATABASE** | `SELECT DISTINCT name FROM routes` (value = label = `routes.name`) |
| Delivery status filter | `delivery_status.php` | **DATABASE** | `SELECT DISTINCT status FROM deliveries WHERE status!=''` |
| Order status filter | `orders_report.php` | **STATIC** | `In Transit`, `Delivered`, `Unscheduled` (label "Unscheduled Orders"), `Exception`, `Refused` |
| Type filter (Delivery/Pickup) | `home.php`, `delivery_status.php`, `orders_report.php` | **STATIC** | `Delivery` / `Pickup` (+ `Both` / `All`) |
| Driver filter (orders report) | `orders_report.php` | **DATABASE** | `SELECT CONCAT(first_name,' ',last_name) AS DRIVER_NAME, user_id FROM users WHERE is_driver=1` |
| Driver select (map) | `google_map/locate_drivers.php` | **DATABASE** | `users WHERE is_driver=1` (or drivers with in-transit deliveries today) |
| State / City (customer address) | `customer_address_new.php` | **STATIC (free text)** | the `createCombo("mast_state","state","state",…)` selects are **commented out**; live inputs are plain `<input type="text" name="state">` / `name="city"`. `mast_state` / `city_master` tables are effectively unused. |
| Exception reasons (mobile API) | `webservices/reasons.php` | **STATIC PHP array** | `array("Building Closed","No One Available to Sign","Refused Delivery","Could not find Address","Aborted  Road Hazards  Closures","Other")` — returned as `{1:..,2:..}` |
| Marital status / self-rating / passport / travelling | `libraries/define.php` | **STATIC** `serialize()`d arrays | **legacy leftovers from another product — not referenced anywhere in RouteTrac** |

## Forms — field detail

### Area (`admin/m/master/ajax/area_new.php`) → table `areas`
| Field | name | Type | Required | Default | Options | Submit |
|---|---|---|---|---|---|---|
| Area Name | `area_name` | text (maxlen 50) | yes (`required`) | current `name` | — | `$db->data("name", $_POST['area_name'])` |
| Is Default | `is_default` | checkbox | no | checked if `is_default==1` | value `1` | `$db->data("is_default", $_POST['is_default'])` ; on set, `UPDATE areas SET is_default=0` for others |
| (hidden) | `hidden_area_id` | hidden | — | `area_id` | — | decides insert vs `update("areas") where area_id=` |
Endpoint: `POST ajax/area_new.php` (`submit`), returns JSON `{success,error,msg}`. Uniqueness: `admin_ajax_action.php?action=check_area`.

### Route (`routes_new.php`) → table `routes`
| Field | name | Type | Required | Options |
|---|---|---|---|---|
| Route | `name` | text | yes | — |
| Area | `area_id` | select | yes | **DB** `areas` (id→name) |
| (hidden) | `hidden_route_id`, `area_name` | hidden | — | — |
Submit: `insert/update("routes")`. Uniqueness: `check_route`, `check_route_area`.

### Package Type (`packages-new.php`) → table `package_types`
| Field | name | Type | Required |
|---|---|---|---|
| Package name | `name` | text | yes |
| (hidden) | `hidden_package_id` | hidden | — |
Uniqueness: `check_package`.

### Customer (`customer_new.php`) → table `customers`
| Field | name | Type | Required |
|---|---|---|---|
| Customer name | `cust_name` | text (100) | yes → `customers.customer_name` |
| Account number | `cust_act_no` | text (50) | (validated) → `customers.account_number` |
| (hidden) | `hidden_cust_id` | hidden | — |
Uniqueness: `check_acct_no`.

### Customer Address (`customer_address_new.php`) → table `customer_addresses`
| Field | name | Type | Required | Source |
|---|---|---|---|---|
| Customer | `cust_name` | hidden (preset) | yes | id passed in |
| Ship-to name | `ship_to_name` | text | no (blank ⇒ use customer name) | — |
| Address line 1 | `address_line_1` | text | yes | — |
| Address line 2 / 3 | `address_line_2/3` | text | no | — |
| City | `city` | **text** | yes | free text (combo commented out) |
| State | `state` | **text** | yes | free text (combo commented out) |
| Zip | `zip` | text | yes | — |
| Phone | `phone` | text | (validated) | — |
| (hidden) | `hidden_address_id` | hidden | — | insert vs update |

### User (`users_new.php`) → table `users`
| Field | name | Type | Required | Options / source |
|---|---|---|---|---|
| First name | `first_name` | text | yes | — |
| Last name | `last_name` | text | yes | — |
| Email | `email_addr` | text | yes + `email` + remote `check_email` | — |
| User name | `acct_username` | text | yes + remote `check_user_name` | **only shown on create** |
| Password | `acct_password` | password | yes | **create only**; stored `md5()` |
| Is Driver | `is_driver` | checkbox (`1`) | no | STATIC |
| Default Route | `default_route_id` | select | yes (when driver) | **DB** `routes` |
| Access Level | `access_level` | select | yes | **DB (master)** `access_levels`, `level_id!=5` when self |
| Status | `status` | select | yes | STATIC `1`/`0` |
| API Access | `is_api_access` | checkbox (`1`) | no | STATIC; admin/user-manager only; if checked → (commented) `RouteTracAPI::makeAPIKey()`, else `api_key=''` |
| (hidden) | `hidden_user_id` | hidden | — | insert vs `update("users") where user_id=` |
Endpoint: `POST ajax/users_new.php`, JSON `{success,error,msg}`.

### Access Level (`access_level_new.php`) → table `access_levels` (master DB)
| Field | name | Type | Required | Options |
|---|---|---|---|---|
| Name | `name` | text | yes | — |
| Menus | `menu_name[]` | checkboxes | — | **DB (master)** one per `access_menu` row; `value=access_menu_id` |
| (hidden) | `hidden_level_id` | hidden | — | — |
Submit: `menu_name[]` → `implode(',', ...)` → `access_levels.access_menu_id`.

### CSV Import — Users (`admin/m/master/users.php`, field `submit1`, file `import_file_name`)
Expected columns (from code): `Status, First_Name, Last_Name, Email_ID, User_Name, Password, Access_Level, Is_Driver, Default_Root`.
Per row: `mysqli_real_escape_string` each field; skip if email/username already exists; resolve `Access_Level` name → `access_levels.level_id` (auto-insert if missing); if driver, resolve `Default_Root` name → `routes.route_id` (auto-insert if missing); `INSERT INTO users` with `api_key = time()`. Template files: `admin/uploads/IMPORT_JOBS_TEMPLATE.csv`, `admin/m/master/templates/CUSTOMER.csv`.

### Manifest builder (`admin/m/master/ajax/manifests_new.php`)
Header: `manifest_type` (Delivery/Pickup), `route_id` (**DB** routes), `driver_id` (**DB** users is_driver=1), `customer_id` (**DB** customers), `address_id` (**DB** customer_addresses filtered by customer), `order_status` (STATIC "In Transit"), `order_number`, `cust_po_num`, notes.
Repeatable rows: `package_id[]` (**DB** package_types) + `package_qty[]`; item rows `sku/product_name/description/uom/qty`.
Submit endpoint: `POST ajax/manifests_new.php` (`hidden_manifest_id`, `delivery_id`, arrays) → JSON `{success,error,msg,manifest_id}`.

## APIs

### Auth model
* **`app_key`** — every `webservices/*` and `webservice-dipali/*` call expects it (`$_REQUEST['app_key']`, or `api_key` in `webservice-dipali`). Locally `123`. Resolved against `app_keys` (→ `clients.repository_db`) only in `check_licence_key.php`; most endpoints just read the param and proceed.
* **user identity** — `login_user.php` validates username/email + `md5(password)`; other endpoints trust `driver_id` / `customer_id` passed in the body.
* No sessions, tokens, rate limiting, or HTTPS enforcement.

### `webservices/` (mobile REST — JSON) — request/response shape
All are effectively `POST`, params via `$_REQUEST`, response `echo json_encode(...)`.
Response envelope: `{"Key":"0"|"1", "msg":"...", "info":[...]}` (success `Key=0`) or `{"Key":"1","msg":"...","last_id":"0"}` (error). Constants from `webservices/message.php`.

| Endpoint | Required params | Response `info` / effect | Tables |
|---|---|---|---|
| `login_user.php` | `app_key,acct_username,acct_password,imei_no` | user profile array; updates `users.imei_no` | users |
| `check_licence_key.php` | `app_key` | `{client_name, repository_db}` | app_keys, clients |
| `reasons.php` | `app_key` | 6 static exception reasons | — |
| `route_list.php` | `app_key,driver_id` | routes for today + counts + accept status | deliveries, routes, manifests, delivery_items, delivery_packages, route_accept |
| `route_accept.php` | `app_key,driver_id,route_id[,abendon_status]` | accept/abandon today's route | route_accept |
| `order_id_list.php` | `app_key,driver_id[,route_id]` | delivery ids | deliveries |
| `order_list.php` | `app_key,customer_id,driver_id,route_id` | stops for a customer on a route today | deliveries, customers, customer_addresses, manifests, delivery_packages |
| `order_details.php` | `app_key,delivery_id` | full delivery + items + packages | deliveries, delivery_items, delivery_packages, customers, customer_addresses |
| `order_summary.php` | `app_key,driver_id,route_id` | aggregate counts | deliveries, delivery_items, delivery_packages |
| `customer_list.php` | `app_key,driver_id,route_id` | customers on a route today | deliveries, customers, route_accept |
| `cust_order_wise_lat_long.php` | `app_key,driver_id,route_id` | stop coordinates | deliveries |
| `get_driver_current_location.php` | `app_key,driver_id,latitude,longitude` | inserts `driver_location`, updates `users` lat/long | driver_location, users |
| `get_order_exception_reason_and_note.php` | `app_key,delivery_id` | exception reason/note | deliveries |
| `upload_signature.php` | `app_key,delivery_id,signature_text,server_filename,package_id[],package_qty,item_qty,item_id,driver_notes` | insert signature, update delivery + qty delivered | signature_pics, deliveries, delivery_packages, delivery_items |
| `upload_photo.php` | `app_key,delivery_id` + multipart file | store file, insert photo row | driver_photos / product_pics |
| `update_item_qty.php` | `app_key,item_id,qty,delivery_id,driver_notes,image1..3,is_scan_image` | insert product pics, update item qty | product_pics, delivery_items |
| `edit_item_qty.php` | `app_key,item_id,qty` | update item qty | delivery_items |
| `update_package_qty.php` | `app_key,packages_id,qty` | update package qty | delivery_packages |
| `update_driver_note.php` | `app_key,delivery_id,driver_notes` | update note | deliveries |
| `check_upc_code.php` | `app_key,upc_code,delivery_id` | validate UPC against line items | delivery_items |
| `message.php` | (include only) | defines response constants | — |

### `webservice-dipali/` (office data-sync REST — JSON)
Envelope: `{"status":0|1,"msg":"..."}`. `POST` only, param `api_key`.
| Endpoint | Params | Table | Op |
|---|---|---|---|
| `search_area.php` | `api_key,area_name` | areas | SELECT |
| `create_new_area.php` | `api_key,area_id,area_name,...` | areas | INSERT/UPDATE |
| `delete_area.php` | `api_key,area_id` | areas | DELETE |
| `search_customer.php` / `create_new_customer.php` / `delete_customer.php` | `api_key` + fields | customers | S/I/U/D |
| `create_customer_address.php` / `update_customer_address.php` / `search_address.php` | `api_key` + fields | customer_addresses | I/U/S |
| `search_routes.php` / `create_new_route.php` (+`_old`) / `delete_route.php` | `api_key` + fields | routes, areas | S/I/U/D |
| `search_packages.php` / `create_new_pckg_type.php` / `delete_pckg_type.php` | `api_key` + fields | package_types | S/I/U/D |

### `_api/create_delivery_manifest.php` (legacy)
`POST` `api_key,manifest_type,...` → inserts `manifests` + `deliveries` (+ items/packages). Superseded by the `create_manifest.php` UI flow. Routable but not part of the current app.
