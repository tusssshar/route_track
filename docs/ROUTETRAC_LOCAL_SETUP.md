# RouteTrac — Local Setup (Windows / XAMPP)

_Last verified: 2026-08-29_

## 1. Required software (what is actually installed here)

| Component | Required | Installed on this machine | Notes |
|---|---|---|---|
| OS | Windows | Windows 11 Pro 26200 | |
| Web server | Apache 2.4 | Apache/2.4.58 (XAMPP, Apache Lounge VS17) | XAMPP Apache, **port 80** |
| PHP | Originally **5.6 / 7.0** (see below) | PHP **8.2.12** (XAMPP, `C:\xampp\php`) | Runs after compat shims — see §7 |
| Database | MySQL/MariaDB | **MariaDB 10.4.32** (XAMPP, `C:\xampp\mysql`) | dumps were generated on MariaDB 10.1 / PHP 5.6 |
| Composer | not used | present (`C:\ProgramData\ComposerSetup`) | project has **no `composer.json`** |
| Node.js | not used | n/a | project has **no `package.json`** |

There are **no dependency managers** in this project. All third-party PHP code (PHPMailer 5.x, Twilio SDK is *not* present here, Google Maps via CDN JS) is vendored directly under `libraries/classes/`.

## 2. Required PHP extensions (all present in XAMPP PHP 8.2)

`mysqli` (primary DB driver), `pdo_mysql`, `mbstring`, `curl`, `openssl`, `gd`, `intl`, `json`, `xml`/`libxml`/`SimpleXML`, `zip`, `fileinfo`.
The app itself only strictly needs: **mysqli**, `json`, `gd` (image handling for signature/photo uploads), `curl`/`openssl` (mail/Maps geocode).

## 3. Required Apache configuration

* `DocumentRoot "C:/xampp/htdocs"` (XAMPP default — unchanged).
* No `.htaccess` and **no rewrite rules** are used by the app (`mod_rewrite` not required). URLs map 1:1 to `.php` files.
* `DirectoryIndex index.php` (default) — used for the login page.
* **No virtual host required.**

### Important: the config hardcodes a URL path

`libraries/config.php` (local branch) sets:

```
SITEROOT  = http://{HTTP_HOST}/projects/routetrac
ROOTPATH  = {DOCUMENT_ROOT}/projects/routetrac
```

so the app must be reachable at **`/projects/routetrac`**, not `/route/routetrac`.
This was solved **without editing config** by creating a directory junction:

```
C:\xampp\htdocs\projects\routetrac   -->   C:\xampp\htdocs\route\routetrac
```

The local branch of `config.php` activates when `$_SERVER['SERVER_NAME']` is `routetrac`, `localhost`, or `192.168.0.206`. Using `http://localhost/...` satisfies this.

## 4. Database configuration (read from the project, not assumed)

Source: `libraries/config.php`, `libraries/dbSqliConnection.php`, `libraries/classes/functions.php::masterDBInfo()`.

| Setting | Local value | Constant |
|---|---|---|
| Host | `localhost` | `HST`, `HST_1` |
| Port | 3306 (default, not specified in code) | — |
| User | `root` | `USR`, `USR_1` |
| Password | _(empty)_ | `PWD`, `PWD_1` |
| Primary DB (per-client operational data) | `RouteTrac_template_repository` | `DBN` |
| Master DB (client registry / access levels) | `routetra_RouteTrac_master` | `DBN_1` |
| Table prefix | _(none)_ | `DB_PREFIX` |

The app opens **two** mysqli connections: the default one to `DBN`, and a second one (re-`Connect(HST_1,USR_1,PWD_1,DBN_1)`) whenever it needs `access_levels` / `clients` from the master DB. Some queries cross-join using the literal `DBN_1` constant as a schema qualifier (e.g. `... LEFT JOIN routetra_RouteTrac_master.access_levels`).

## 5. Provided database dumps

Two phpMyAdmin dumps sit at the repo root (`C:\xampp\htdocs\route\`):

| File | Target database | Tables | Purpose |
|---|---|---|---|
| `RouteTrac_template_repository.sql` | `RouteTrac_template_repository` | 19 | operational data (areas, routes, customers, deliveries, manifests, users, …) — matches `DBN` |
| `routetra_RouteTrac_master (2).sql` | `routetra_RouteTrac_master` | 5 | client registry, access levels, API versions, app keys — matches `DBN_1` |

Confirmed as RouteTrac's databases by matching the dump's `-- Database:` header and every table name against the queries in the PHP code — not by filename alone.

## 6. Database import process (commands actually run)

```bash
# MariaDB data dir was corrupted on arrival — see ROUTETRAC_SETUP_STATUS.md for the repair.
# After the server was healthy:

C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS \`RouteTrac_template_repository\` CHARACTER SET utf8mb4;"
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS \`routetra_RouteTrac_master\` CHARACTER SET utf8mb4;"

C:\xampp\mysql\bin\mysql.exe -u root RouteTrac_template_repository < "C:\xampp\htdocs\route\RouteTrac_template_repository.sql"
C:\xampp\mysql\bin\mysql.exe -u root routetra_RouteTrac_master     < "C:\xampp\htdocs\route\routetra_RouteTrac_master (2).sql"
```

`lower_case_table_names=1` on this MariaDB, so the databases are stored as `routetrac_template_repository` / `routetra_routetrac_master` and are matched case-insensitively — the app's mixed-case constants work fine.

### Verification (actual results)

```
RouteTrac_template_repository : 19 tables
routetra_RouteTrac_master     :  5 tables
users        = 4 rows      deliveries = 2 rows      clients = 3 rows
areas        = 5 rows      routes     = 2 rows      access_levels = 5 rows
```

PHP → MySQL connectivity verified two ways:
* CLI: instantiated the `database` class, ran `SELECT ... FROM users` → 1 row returned.
* HTTP: `POST /projects/routetrac/index.php` with `admin` / `12345` → **HTTP 302 → `/admin/home.php`**, authenticated dashboard renders.
* API: `POST /projects/routetrac/webservices/login_user.php` → `{"Key":"0","msg":"Success",...}` (also performs a DB `UPDATE users SET imei_no=...`).

## 7. PHP 8.2 compatibility shims (required only to make it run)

The code is PHP 5.6-era. The following **minimal, reversible** changes were made — no business logic altered:

| File | Change |
|---|---|
| `C:\xampp\php\php.ini` | `short_open_tag=Off` → **`On`** (views use `<? ?>` / `<?= ?>` everywhere) |
| `libraries/dbSqliConnection.php` | added `__construct()` → calls legacy `dbSqlConnection()` / `database()` (PHP 8 ignores PHP4-style constructors) |
| `libraries/dbSqliConnection.php` | `field_array` initialised as `array()` (was `""`, used as array → fatal in PHP 8); added property declarations `isUpdate/row_count/result/id` |
| `libraries/dbSqliConnection.php` | `database::update()` : `each()` → `foreach` (`each()` removed in PHP 8) |
| `libraries/dbSqliConnection.php` | `sanitize()` : `get_magic_quotes_gpc()` guarded, `mysql_real_escape_string()` → `mysqli_real_escape_string()` |
| `libraries/classes/pagination.php` | added `__construct()` → `clsPagination()` |
| `libraries/classes/functions.php` | added `__construct()` → `functions()` |

`api/libraries/*` is a **dead copy** (the `api/` folder has no endpoints) and was left untouched. `webservices/`, `webservice-dipali/`, `_api/` all include the main `bootstrap.php`, so they inherit the shims.

**To restore a true baseline instead of shimming:** install PHP 7.0 (or 7.4) alongside 8.2 and point an Apache vhost at it; then revert the 7 changes above.

## 8. Local URL & login

| Thing | Value |
|---|---|
| App URL | **http://localhost/projects/routetrac/** |
| Admin home (after login) | http://localhost/projects/routetrac/admin/home.php |
| Login users (from `users` table, password = md5) | `admin` / `12345` · `eddie` / `123456` · `Barry` / `11111` · `mdkamble` / `12345` |
| Mobile/REST API base | http://localhost/projects/routetrac/webservices/ |
| API app key (from `app_keys`) | `123` (client_id 1) |

## 9. Known setup issues encountered

1. **XAMPP MariaDB would not start** — corrupt InnoDB system tablespace + crashed Aria system tables (pre-existing, dated 2026-08-21). Repaired: see `ROUTETRAC_SETUP_STATUS.md`. Full `mysql\data` backup kept at `C:\xampp\mysql\data_backup_claude_20260829`.
2. **Port 80 was free but XAMPP Apache was not running** — a separate EnterpriseDB `httpd.exe` occupies port 8080 (unrelated). Started `C:\xampp\apache\bin\httpd.exe` directly.
3. **`short_open_tag` off** made every view leak raw `<? ... ?>` as text — fixed in `php.ini`.
4. **PHP4 constructors** left `$this->conn` empty → *"Call to a member function query() on string"* on every DB call — fixed with `__construct` shims.
5. `config.php` expects the app under `/projects/routetrac` — solved with a directory junction (no code change).

## 10. Commands used (summary)

```bash
# PHP / Apache / MariaDB versions
C:\xampp\php\php.exe -v ; C:\xampp\apache\bin\httpd.exe -v

# MariaDB repair (see SETUP_STATUS for detail)
cp -r C:\xampp\mysql\data  C:\xampp\mysql\data_backup_claude_20260829
C:\xampp\mysql\bin\aria_chk.exe -o -f --page_buffer_size=128M --sort_buffer_size=256M C:\xampp\mysql\data\mysql\*.MAI
#   (InnoDB rebuilt from mysqldump of every DB — see SETUP_STATUS)

# start services
Start-Process C:\xampp\mysql\bin\mysqld.exe  -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini'
Start-Process C:\xampp\apache\bin\httpd.exe  -ArgumentList '-d','C:/xampp/apache'

# deploy path
New-Item -ItemType Junction C:\xampp\htdocs\projects\routetrac -Target C:\xampp\htdocs\route\routetrac

# import RouteTrac DBs (see §6)
```

## 11. Final running status

| Check | Result |
|---|---|
| Apache (80) | **WORKING** |
| PHP 8.2 (with shims) | **WORKING** |
| MariaDB 10.4.32 | **WORKING** (repaired) |
| RouteTrac DBs imported | **WORKING** — 19 + 5 tables |
| PHP → MySQL query | **WORKING** |
| Admin login + dashboard | **WORKING** |
| Master data screens (area/route/customer/user/package/manifest/access level/reports) | **WORKING** (pages + AJAX list endpoints return 200, no fatals) |
| Mobile REST API (`webservices/`) | **WORKING** (login_user, reasons verified; others need valid params) |
| Create/update writes through the app | **WORKING** (login_user IMEI update; manifest builder code path present, not end-to-end tested) |
