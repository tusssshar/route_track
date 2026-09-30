# RouteTrac — Setup Status

_As of 2026-08-29. This is the baseline record: what was done to bring the existing project up locally, what works, what is unverified, and what the risks are._

## Environment

| Component | Version | Status |
|---|---|---|
| OS | Windows 11 Pro 26200 | — |
| Apache | 2.4.58 (XAMPP) | **WORKING** — started `C:\xampp\apache\bin\httpd.exe`, listening on **:80** |
| PHP | 8.2.12 (XAMPP) | **WORKING with compat shims** (project is PHP 5.6-era) |
| MariaDB | 10.4.32 (XAMPP) | **WORKING** — repaired from a corrupt data directory (see below) |
| Composer | present, **not used** (no `composer.json`) | n/a |
| Node | n/a (no `package.json`) | n/a |

## Projects

| Directory | What it is | Status |
|---|---|---|
| `C:\xampp\htdocs\route\routetrac` | The live PHP application (own `.git`, branch `master`). 166 PHP files. | **RUNNING** at http://localhost/projects/routetrac/ |
| `C:\xampp\htdocs\route\routetrac_newserver` | "New server" work folder — 4 historical copies of the same app + HTML mockups + `modules/api` stub. `libraries/config.php` identical to canonical. | **Not deployed** (reference/snapshots only) |

## Database

| Item | Result |
|---|---|
| Databases | `RouteTrac_template_repository` (`DBN`), `routetra_RouteTrac_master` (`DBN_1`) |
| Imported | **YES** — from `RouteTrac_template_repository.sql` (19 tables) and `routetra_RouteTrac_master (2).sql` (5 tables) at repo root |
| Connected from PHP | **YES** — verified via CLI (`database` class SELECT), web login, and API |
| Verified | table counts 19 / 5; key row counts: `users`=4, `deliveries`=2, `manifests`=2, `areas`=5, `routes`=2, `package_types`=2, `clients`=3, `access_levels`=5 |
| Views / procedures / functions / triggers / events | none in either DB |
| Foreign keys | none (implicit relationships only) |

### MariaDB repair performed (with approval)

The XAMPP `mysql\data` directory was **already corrupt on arrival** (errors dated 2026‑08‑21, before this session): InnoDB "pages in the future" in `ibdata1`, plus crashed Aria system tables (`mysql.proxies_priv` etc.). MariaDB would not start at all.

Steps taken:
1. **Full backup**: `C:\xampp\mysql\data` → `C:\xampp\mysql\data_backup_claude_20260829` (126 MB, retained).
2. Repaired Aria system tables: `aria_chk -o -f --page_buffer_size=128M --sort_buffer_size=256M mysql\data\mysql\*.MAI`.
3. Started once with `--innodb-force-recovery=1`; **logical-dumped every user database** (`aqaar_admin_db`, `nadsoft`, `realcube_lems_oa`, `smsdb`, `snipeit`, `phpmyadmin`, `test`) — all dumped clean.
4. Moved the corrupt InnoDB files (`ibdata1`, `ib_logfile*`, `ibtmp1`) and the dumped DB folders aside to `C:\xampp\mysql\_claude_corrupt_innodb_stash\`.
5. Started MariaDB normally → fresh `ibdata1` created, server healthy.
6. **Reimported all user databases** from the dumps; verified table counts match the pre-repair dumps (aqaar 85, smsdb 60, snipeit 56, …).
7. Imported the two RouteTrac databases.

Result: all **12** databases present and the server runs with no `innodb-force-recovery`.
Rollback if ever needed: stop MariaDB, restore `C:\xampp\mysql\data_backup_claude_20260829` over `C:\xampp\mysql\data`.

## Application code changes (compat shims — "only to make it run")

| File | Change | Reversible |
|---|---|---|
| `C:\xampp\php\php.ini` | `short_open_tag` `Off` → `On` | yes |
| `libraries/dbSqliConnection.php` | `__construct()` added to `dbSqlConnection` & `database` (PHP 8 ignores PHP4 constructors); `field_array` init `array()`; declared `isUpdate/row_count/result/id`; `each()`→`foreach` in `update()`; `sanitize()` → `mysqli_real_escape_string` + guarded `get_magic_quotes_gpc` | yes |
| `libraries/classes/pagination.php` | `__construct()` → `clsPagination()` | yes |
| `libraries/classes/functions.php` | `__construct()` → `functions()` | yes |

No business logic, SQL, or schema was changed. `git diff --stat`: 4 files, +39 / −17.
Deployment path handled by a **directory junction** `C:\xampp\htdocs\projects\routetrac → …\route\routetrac` (config expects `/projects/routetrac`) — no code change.

> A pre-existing uncommitted edit to `admin/m/master/ajax/users_new.php` (commenting out `RouteTracAPI::makeAPIKey()`) was already in the working tree on arrival — not made by this setup.

## Feature status (tested locally over HTTP)

| Area | Status | Evidence |
|---|---|---|
| Apache / PHP / MySQL up | **WORKING** | `curl http://localhost/projects/routetrac/` → 200 |
| DB connection from app | **WORKING** | login query returns row; API `login_user` success |
| Admin login + session | **WORKING** | `admin`/`12345` → HTTP 302 → `/admin/home.php`, authenticated |
| Dashboard | **WORKING** | `admin/home.php` 200; `admin/ajax/home.php` 200 |
| Master lists: area, route, customer, package, user, access-level, manifest, options | **WORKING** | each `admin/m/master/ajax/<x>.php` → 200, rows rendered, no fatals |
| Customer-address list | **PARTIALLY WORKING** | page 200; `ajax/customer_address.php` needs a `customer_id` param (0 bytes without it) |
| Manifest detail report | **PARTIALLY WORKING** | needs `?manifest_id=N` (57 bytes without) |
| Reports (delivery status, orders, route status) | **WORKING** (pages load, DB-backed filters populate) | 200, 11–16 KB |
| Live driver map | **PARTIALLY WORKING** | page 200; map tiles need a valid Google Maps key + today-dated in-transit rows (none locally) |
| Mobile API — `login_user`, `reasons`, `check_licence_key` | **WORKING** | JSON success responses verified |
| Mobile API — `route_list`, `order_list`, `customer_list` | **PARTIALLY WORKING** | execute; return "Please pass all parameter" without valid driver/route ids |
| Mobile API — writes (`upload_signature`, `upload_photo`, `update_*_qty`, `route_accept`, `get_driver_current_location`) | **NOT VERIFIED** | code paths present & correct; not exercised (need real delivery/driver data) |
| Secondary API `webservice-dipali/*` | **PARTIALLY WORKING** | `search_area` executes, returns key-missing JSON |
| CSV import / export | **NOT VERIFIED** | no file uploaded |
| DB writes through the app | **WORKING** (proven) | `login_user.php` performs `UPDATE users SET imei_no=…` successfully |
| Manifest create (multi-table insert) | **NOT VERIFIED end-to-end** | builder UI renders; full submit not run |

## Documentation produced (in `routetrac/docs/`)

| File | Contents |
|---|---|
| `ROUTETRAC_LOCAL_SETUP.md` | required software, versions, extensions, DB config, import process, URL, login, issues, commands, running status |
| `ROUTETRAC_PROJECT_OVERVIEW.md` | what RouteTrac is, the two directories, tech stack, structure, security notes, module list |
| `ROUTETRAC_DATABASE_INVENTORY.md` | server info, both DBs, every table (rows, PK, columns, types, nullable, defaults, auto-inc, indexes), reference data with actual values |
| `ROUTETRAC_PHP_DATABASE_MAPPING.md` | 7 business flows traced UI → controller → query → table → column → result |
| `ROUTETRAC_MODULES.md` | per-module table (URL, controller, views, models, AJAX, tables, function, status) for web + all APIs |
| `ROUTETRAC_FORMS_AND_APIS.md` | every form's fields (name/type/required/source), DATABASE-vs-STATIC dropdown table, full API endpoint catalogue |
| `ROUTETRAC_INTEGRATIONS.md` | Google Maps, FCM push, mail, multi-tenant routing, uploads, GPS; hardcoded secrets |
| `ROUTETRAC_SETUP_STATUS.md` | this file |

## Risks / blockers / follow-ups

1. **PHP version** — running on 8.2 via shims. Deprecation notices are emitted (suppressed by `error_reporting(0)` in normal use). Some rarely-hit code paths (legacy `_api/`, `admin/m/master/ajax/*_BK_/`, points/skills pages referencing non-existent tables) may still fatal. For a fully faithful baseline, install PHP 7.0/7.4 and revert the shims.
2. **MariaDB** — repaired but the root cause (unclean shutdowns / copied datadir) can recur. Always stop MySQL via the XAMPP control panel. Backup retained at `data_backup_claude_20260829`; corrupt originals at `mysql\_claude_corrupt_innodb_stash\`.
3. **Security (pre-existing, not fixed)** — pervasive SQL injection (string-concatenated `$_REQUEST` in queries), unsalted md5 passwords, hardcoded DB passwords & API keys in source, `phpinfo.php`/`test.php` publicly reachable, API `app_key` = `123`, `CURLOPT_SSL_VERIFYPEER=false` in FCM call.
4. **Multi-tenant path not exercised** — local config is single-DB; `clients.repository_db` values carry a `routetra_` prefix the local schema names don't have.
5. **Dead code** — `api/` (no endpoints), `_api/`, `webservice-dipali/api_call/` stubs, `admin/m/master/ajax/_BK_/` and dated backups, `libraries/define.php` constants, PHPMailer (unused), `mast_state_19_3_19` / `client_id` / `city_master` tables. Recommend excluding from any migration scope.
6. **External deps for full UI** — Google Maps needs a live/unrestricted key; FCM push uses a legacy endpoint + legacy server key and IMEI-as-token (won't deliver).
7. **Not verified** — all API write endpoints, manifest create end-to-end, CSV import/export, route-status report internals. These need seeded delivery/driver data and a manual UI pass.
