# RouteTrac — Project Overview

_Baseline audit: 2026-08-29. Source of truth = the actual code under `C:\xampp\htdocs\route\routetrac` and the live MariaDB._

## What RouteTrac is

A **delivery / pickup route-management system** for a courier operation. An office user builds *manifests* (batches of deliveries/pickups) assigned to drivers on routes; drivers use a **mobile app** that talks to the `webservices/` JSON API to see their route, capture proof of delivery (signature + photos), update quantities, record exceptions, and report GPS location. The office side has an admin web panel for master data, manifest building, live driver map, and reports. It is **multi-tenant by design** (a master DB of `clients`, each pointing at its own `*_template_repository` database) though only one repository DB is deployed locally.

## The two project directories

| Directory | What it actually is | Use it? |
|---|---|---|
| **`C:\xampp\htdocs\route\routetrac`** | The live PHP application. Has its own `.git` (branch `master`, latest commits `df` / `er` / `removed sql files`). 166 PHP files. | **YES — this is the canonical project** (per instruction) |
| `C:\xampp\htdocs\route\routetrac_newserver` | A "new server migration" work folder. Contains **four copies** of the same codebase — `routetrac/` (slightly newer: adds `uploads/`, `webservices_old/`, `.circleci/`; drops the dead `_api/` and `admin/m/master/ajax/_BK_/` backups), `routetrac_git/`, `routetrac_15_MAR_2018_before_git/` — plus static HTML mockups (`RouteTrac.html`, `RouteTrac_13-Nov-2017.html`) and a `modules/api/` stub. `libraries/config.php` is byte-identical to the canonical one. | No — snapshots only. Diff `routetrac_newserver/routetrac` against the canonical tree if you want the newer edits. |

## Technology

| Aspect | Reality |
|---|---|
| Language | PHP, procedural + a few hand-rolled classes. **No framework.** Originally PHP 5.6 / MariaDB 10.1. |
| Front-end | Server-rendered PHP views; jQuery 1.x, Bootstrap 3, `jquery.validate`, `jquery.blockUI`, Font Awesome 4.6, a jQuery datepicker. Heavy AJAX (`$.post` returning HTML fragments). |
| DB access | Custom `database` class (`libraries/dbSqliConnection.php`) wrapping **mysqli**. Two global connections (repository DB `DBN`, master DB `DBN_1`). Raw string-concatenated SQL throughout (**SQL injection exposure** — see Security). |
| Auth (web) | `index.php` checks `users` (`acct_password = md5(...)`, `status='1'`, `access_level != 5`), sets `$_SESSION["routetrac-admin"]`. Every admin page includes `admin/authorized.php` (redirects to login if no session) and calls `functions::user_access_level($menu_id, $level)` against `access_levels.access_menu_id` (a CSV of allowed menu ids). |
| Auth (API) | `app_key` (→ `app_keys` table, resolves a client + its repository DB) and/or per-user `api_key` / username+password. No tokens/JWT. |
| Config | `libraries/config.php` — environment picked by `$_SERVER['SERVER_NAME']`. No `.env`. Credentials hardcoded (incl. a **production password in the non-local branch** and a second one in `functions::masterDBInfo()`). |
| Sessions | PHP file sessions (`C:\xampp\tmp`), key `routetrac-admin`. |
| Error handling | `bootstrap.php` sets `error_reporting(0)`. DB errors call `trigger_error(..., E_USER_ERROR)`. |
| Timezone | `Asia/Kolkata` everywhere (web + api bootstrap; MySQL server is IST). Switched from `America/Chicago` on 2026-10-02. |

## High-level structure

```
routetrac/
  index.php                     admin login (POST self)
  bootstrap.php                 session + config + libs + $functions
  libraries/
    config.php                  DB creds + SITEROOT/ROOTPATH per environment
    dbSqliConnection.php        dbSqlConnection + database (mysqli wrapper) + Encode()/sanitize()
    define.php                  a few serialize()d constant arrays (mostly unused legacy)
    classes/
      pagination.php            clsPagination — AJAX pager (showAjaxPaging)
      functions.php             functions extends clsPagination — createCombo(), user_access_level(),
                                get_last_id(), check_csv_columns(), send_mail(), multi_attach_mail() ...
      class.phpmailer.php       PHPMailer 5.x (vendored)
      class.smtp.php            SMTP (vendored)
  admin/
    home.php                    dashboard (recent deliveries/pickups widget)
    authorized.php              session guard (included by every page)
    logout.php
    ajax/  admin_ajax_action.php (big switch: check_* validators, get_address, get_delivery_table, ...)
           home.php  home_route_status.php
    templates/                  header/footer partials, CSS, JS, fonts
    m/
      master/                   19 pages: area, route, customer, customer_address, packages,
                                users, access_level, manifests(+_1), create_manifest,
                                detail_report, export, options, manage_master(_sub_values)
        ajax/                   66 fragment endpoints (list + *_new form + validators);
                                *_BK_/, *_<date>.php  = dead backups
      reports/                  delivery_status, orders_report, route_status
      google_map/               locate_drivers (+ ajax) — live driver/customer map
  webservices/                  23 mobile REST endpoints (JSON) — THE live API
  webservice-dipali/            17 secondary REST endpoints (search_/create_/delete_ area|customer|route|package|address)
  _api/                         legacy API attempt (create_delivery_manifest, api_call) — own libs, still routable
  api/                          bootstrap + libraries only, NO endpoints — dead scaffold
  webservices_old/ (newserver copy only)
  nbproject/                    NetBeans project metadata
  phpinfo.php / test.php        dev scratch files (should not ship)
```

## Databases (see ROUTETRAC_DATABASE_INVENTORY.md for full detail)

* **`RouteTrac_template_repository`** (`DBN`) — 19 tables. Operational data for one client: `areas`, `routes`, `customers`, `customer_addresses`, `deliveries`, `delivery_items`, `delivery_packages`, `manifests`, `driver_location`, `driver_photos`, `signature_pics`, `product_pics`, `package_types`, `users`, `mast_state`, `city_master`, plus dead: `mast_state_19_3_19`, `client_id`, `route_accept` (empty).
* **`routetra_RouteTrac_master`** (`DBN_1`) — 5 tables: `clients`, `access_levels`, `access_menu`, `api_versions`, `app_keys`.
* No views, stored procedures, functions, triggers or events in either DB.
* No FK constraints — relationships are implicit (naming convention), engines are mostly MyISAM with a few InnoDB.

## Security notes (observed, not fixed — baseline only)

* **SQL injection**: nearly all queries are built by string concatenation of `$_REQUEST`/`$_POST` (`admin_ajax_action.php`, every `ajax/*` list endpoint, all `webservices/*`). `sanitize()` exists but is rarely called.
* **Passwords**: unsalted `md5()`.
* **Hardcoded credentials** in `config.php` (prod branch) and `functions::masterDBInfo()`.
* **Google Maps API keys** hardcoded in client JS (`admin/m/google_map/*`).
* `phpinfo.php`, `test.php`, `admin/test.php` are publicly reachable.
* API `app_key` check is a plain string compare against `app_keys.app_key` (value `123` locally).

## Modules at a glance

| # | Module | Web URL (under `/admin/m`) | Live API |
|---|---|---|---|
| 1 | Authentication / Access Levels | `../index.php`, `master/access_level.php` | `webservices/login_user.php` |
| 2 | Dashboard | `admin/home.php` | — |
| 3 | Areas (master) | `master/area.php` | `webservice-dipali/{search,create_new,delete}_area.php` |
| 4 | Routes (master) | `master/routes.php` | `webservice-dipali/*_route.php`, `webservices/route_list.php`, `route_accept.php` |
| 5 | Package Types (master) | `master/packages.php` | `webservice-dipali/*_pckg_type.php` |
| 6 | Customers + Addresses (master) | `master/customer.php`, `master/customer_address.php` | `webservice-dipali/*_customer*.php`, `webservices/customer_list.php` |
| 7 | Users / Drivers (master) | `master/users.php` | `webservices/login_user.php`, `get_driver_current_location.php` |
| 8 | Manifests & Deliveries | `master/manifests.php`, `master/create_manifest.php`, `master/detail_report.php` | `webservices/order_*.php`, `_api/create_delivery_manifest.php` |
| 9 | Proof of delivery (signature/photo/qty/notes/exceptions) | (viewed in `detail_report.php`) | `webservices/upload_signature.php`, `upload_photo.php`, `update_item_qty.php`, `update_package_qty.php`, `update_driver_note.php`, `edit_item_qty.php`, `get_order_exception_reason_and_note.php`, `reasons.php` |
| 10 | Live driver map | `google_map/locate_drivers.php` | `webservices/get_driver_current_location.php`, `cust_order_wise_lat_long.php` |
| 11 | Reports | `reports/delivery_status.php`, `reports/orders_report.php`, `reports/route_status.php` | — |
| 12 | Master-value config | `master/manage_master.php`, `master/options.php`, `master/manage_master_sub_values.php` | — |
| 13 | Import / Export (CSV) | `master/users.php` (import), `master/export.php` | — |
| 14 | Licence check | — | `webservices/check_licence_key.php` |
| 15 | Messaging | — | `webservices/message.php` |

See `ROUTETRAC_MODULES.md` for the full per-module table with verification status.
