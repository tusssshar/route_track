# RouteTrac

Delivery / pickup route management: an admin web panel (manifests, routes, customers, drivers, reports, live driver map)
plus JSON APIs for the driver Android app (`webservices/`) and for office-system sync (`webservice-dipali/`).

Plain PHP (no framework) + MySQL/MariaDB. Runs on PHP 8.2 (originally written for PHP 5.6).

## Run it locally (XAMPP on Windows, or any Apache + PHP 8 + MySQL)

1. **Code.** Put the repo where Apache serves it as `/projects/routetrac`, e.g.
   `C:\xampp\htdocs\projects\routetrac` (or a junction to it). The URL path is set in `libraries/config.php` (`SITEROOT` / `ROOTPATH`).
2. **PHP.** Enable `short_open_tag = On` in `php.ini` (templates use `<? ?>`), plus the `mysqli`, `gd` and `curl` extensions.
   Apache needs `AllowOverride All` (the app ships `.htaccess` files).
3. **Database.** `mysql -uroot < database/routetrac_schema.sql`
   Creates `RouteTrac_template_repository` and `routetra_RouteTrac_master` with the reference data and a demo login.
4. **Config.** Local defaults (host `localhost`, user `root`, no password) work as-is.
   For anything else: copy `libraries/config.example.php` to `libraries/config.local.php` (git-ignored) and fill in the DB
   credentials, `GOOGLE_MAPS_JS_KEY` / `GOOGLE_MAPS_API_KEY` (Google project with billing) and `FCM_SERVER_KEY`.
5. **Open** http://localhost/projects/routetrac/ and log in with **admin / 12345**, then change the password.

Mobile API quick check: `curl -d "app_key=123&acct_username=admin&acct_password=12345" http://localhost/projects/routetrac/webservices/login_user.php`

## Check that everything works

```bash
bash docs/regression_test.sh
```

This covers roughly 105 end-to-end checks: security, every admin page, CRUD, manifest create/edit/import, exports, reports, mobile API
and office API. It creates test rows and removes them afterwards, so run it on a local or test copy only. Exit code = number of failures.

Debugging: create an empty `.debug` file in the app root, and PHP errors go to `logs/php_errors.log`.

## Layout

| Path | What |
|---|---|
| `index.php`, `admin/` | admin panel (`admin/m/master` master data + manifests, `admin/m/reports`, `admin/m/google_map`) |
| `webservices/` | driver app API (login, routes, orders, proof of delivery uploads) |
| `webservice-dipali/` | office sync API (areas, routes, customers, addresses, package types) keyed by a user `api_key` |
| `libraries/` | config, DB layer (`dbSqliConnection.php`), helpers |
| `uploads/` | signatures / photos from the app (content not committed) |
| `docs/` | audit and setup docs; `ROUTETRAC_FIXES_2026-09-30.md` lists the recent fixes |

## Known limitations

- Google Maps and FCM push need valid keys (FCM still uses the legacy endpoint).
- Passwords are unsalted MD5, and the mobile API does not validate `app_key`.
