# RouteTrac — Functional audit & fixes (2026-09-30)

Scope: every admin page / AJAX endpoint, the mobile API (`webservices/`), the office-sync API (`webservice-dipali/`),
CSV import/export, reports and POD uploads, run on PHP 8.2 / MariaDB 10.4 (XAMPP).
Result: `docs/regression_test.sh` — **105 / 105 checks pass, 0 PHP fatals**. Test data was removed afterwards
(DBs restored from `C:\xampp\htdocs\route\db_backups\*_before_test_20260930.sql`).

Re-run any time: `bash docs/regression_test.sh` (writes and then deletes its own test rows — local copy only).
Debug logging: create an empty `routetrac/.debug` file → errors go to `logs/php_errors.log`; delete it to switch off.

## Security (critical)

| Issue | Fix |
|---|---|
| Admin login bypass: username `admin'#` logged in without password | `index.php` escapes username/password |
| Mobile login bypass: `admin') -- ` returned the user record | `webservices/login_user.php` escapes inputs |
| Office API auth bypass: `api_key=' OR '1'='1` | all `webservice-dipali/*` queries escape values |
| All 37 admin AJAX endpoints (users, customers, deletes, exports incl. password hashes) worked **without login**; `authorized.php` only printed a JS redirect | central guard in `bootstrap.php` for every `/admin/` script (302, or 401 for AJAX); `authorized.php` now exits |
| `delete_id=1 or 1=1` style injection in all list pages; unquoted ids in SQL | ids cast to int (`bootstrap.php` admin filter, `webservices/request_filter.php`) |
| Inactive users passed the office-API check (`$status = 0` assignment) | `==` |
| Users CSV import gave every user `api_key = time()` (guessable, shared) | no key unless access level 5 (random key) |
| Uploads could be saved as `.php` under the user-supplied name | generated `.csv` names; `uploads/` and `admin/uploads/` deny PHP execution |
| Reflected XSS in report filters | `htmlspecialchars` |
| `phpinfo.php`, `test.php`, `index1.php`, `docs/`, `logs/`, `.git` publicly reachable | denied in `.htaccess` |

## Broken functionality fixed

| Area | Problem | Fix |
|---|---|---|
| Dashboard | route-status widget fatal (division by zero) for a route with no deliveries today; delivery list fatal without a limit | guarded |
| Customer addresses | page from the menu (no customer selected) fatal; new address saved with customer 0 | DB layer no longer aborts on empty result; customer picker + validation |
| Any list / check | names with an apostrophe (`Joe's`) crashed searches and duplicate checks | values escaped |
| Paging | non-numeric page → fatal; reports never paged past 25 rows (no `SQL_CALC_FOUND_ROWS`) | int cast; added |
| Delivery status report | a copy-pasted handler deleted `access_levels` rows via `delete_id`; PO filter ignored unless a customer was entered | removed; fixed |
| Manifest editor | adding a delivery **without packages** (optional per client) was fatal on PHP 8; removing all items/packages did not delete them; manifest totals not recalculated after deleting a delivery | fixed; shared `update_manifest_counts()` |
| Manifest CSV import | from *Create Manifest* it expected the wrong columns and redirected to a missing page; from *Manifests* the address was never linked, route/driver/date were never set (deliveries invisible to drivers), order numbers matched across other manifests | one shared importer `admin/m/master/includes/manifest_functions.php`; import form now asks route, driver, date, type |
| Master CSV imports | apostrophes stored as `O\'Hare`; customer-address import skipped the first row; user import never detected duplicate usernames and saved "Yes" as blank | fixed |
| Templates | `CUSTOMER_ADDRESS.csv` was UTF-7 garbage; `ROUTE.csv` columns reversed; Users/Addresses pages linked the Customer template | fixed |
| Users export | used removed `mysql_connect()` → fatal | removed |
| Manifest export | "Package Delivered" / "Item Delivered" columns swapped | fixed |
| User form "API access" | key generation was commented out (missing class) | generates a key, keeps an existing one, always for "API Only" users |
| Mobile: POD | `uploads/` folders did not exist (nothing was saved); photos written outside the app so reports could not show them; delivery time stored with a 12-hour clock (2 PM → 02:00); scan-image branch fatal and saved the signature instead; corrupt image → fatal | folders created; paths, 24h time, scan image fixed; invalid images skipped |
| Mobile: JSON | PHP warnings printed as HTML before the JSON when optional fields were omitted | `display_errors Off` in `.htaccess` |
| Mobile: `check_upc_code` | stub (hardcoded `4`, looked up users) → always YES | real lookup by UPC/SKU, optional `delivery_id`; returns item id/qty |
| Office API | every `delete_*` and `search_packages` fatal (`is_deleted` column does not exist); address create/update returned `status:0` on success | hard delete like the admin UI (cascades kept); `status:1` |
| Geocoding | called Google without an API key | sends `GOOGLE_MAPS_API_KEY` (environment variable), 5 s timeout |

## Environment

- MariaDB would not start: garbled `multi-master.info` / relay-log files (log text written into them) → moved to
  `C:\xampp\mysql\_claude_bogus_replication_stash_20260930\`; `mysql.gtid_slave_pos` recreated.
- **Still to do (needs the server stopped):** the `mysql` system schema has Aria tables that need `aria_chk --zerofill`
  and three orphaned InnoDB tables (`innodb_index_stats`, `innodb_table_stats`, `transaction_registry`). The server runs,
  but `mysql_upgrade` fails until this is done. Backup: `db_backups\mysql_system_before_upgrade_20260930.sql`.
  Start/stop MariaDB only via the XAMPP control panel (unclean stops are what corrupted it).

## Needs a decision / external action

- **Google Maps**: the key in `locate_drivers.php` is rejected by Google ("enable Billing"). Live map and geocoding need a
  billing-enabled key; set `GOOGLE_MAPS_API_KEY` for geocoding.
- **Push notifications** use the retired FCM legacy endpoint and server key → never delivered; needs FCM HTTP v1 + service account.
- Mobile API still accepts any `app_key` (only `check_licence_key` validates it) — enforcing it needs the app's real keys.
- Passwords are unsalted MD5 (changing it needs a migration + app release).

## Not part of RouteTrac (left untouched, not linked from the menu)

`admin/m/master/manage_master*.php`, `options.php`, `ajax/skills*.php`, `ajax/*points_consumption*.php`, `libraries/define.php`
(query `vjgm_*` tables from another product), `admin/uploads/IMPORT_JOBS_TEMPLATE.csv` (job-portal template),
dated copies `*_19_3_19.php`, `*_2017.php`, `_BK_/`, `manifests_1.php`, `_api/`, `api/`.
