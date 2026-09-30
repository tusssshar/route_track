# RouteTrac — External Integrations

_Only integrations that actually exist in the code are listed. "Wired" = code path present. "Active locally" = would function on this machine as configured._

| # | Integration | Where | How | Wired | Active locally | Notes |
|---|---|---|---|---|---|---|
| 1 | **Google Maps JavaScript API** | `admin/m/google_map/locate_drivers.php`, `admin/m/google_map/ajax/*` | `<script src="https://maps.googleapis.com/maps/api/js?key=<redacted: now GOOGLE_MAPS_JS_KEY / FCM_SERVER_KEY in config.local.php>">` | ✅ | ⚠️ only if that API key is still valid & unrestricted | Renders the live driver/stop map. Key **hardcoded in client JS** (2 different keys appear across files). |
| 2 | **Google Maps Geocoding API** | `admin/m/google_map/*` (`maps.googleapis.com/maps/api/geocode/json`) | client-side fetch with same key | ✅ | ⚠️ | Address → lat/long for map markers. |
| 3 | **Firebase Cloud Messaging (FCM)** push | `admin/m/master/ajax/manifests_new.php` → `send_notification()` (line 60), called at line 331 | server-side `curl` `POST https://fcm.googleapis.com/fcm/send` with `Authorization: key = <redacted: now GOOGLE_MAPS_JS_KEY / FCM_SERVER_KEY in config.local.php>`; device token = `users.imei_no` of the assigned driver | ✅ (only on **manifest update** with a driver set) | ❌ legacy FCM endpoint + legacy server key; `imei_no` is used as the token but is populated with a raw IMEI by `login_user.php`, not an FCM token. `CURLOPT_SSL_VERIFYPEER=false`. | Notifies the driver app that an order changed. Not triggered on manifest **create**. |
| 4 | **Email — PHP `mail()`** | `libraries/classes/functions.php` → `send_mail()`, `multi_attach_mail()` | plain `mail()` with hand-built MIME; template pulled from `SITEROOT."/m/ajax/email_template.php"` (path does not exist in this tree) | ⚠️ defined but **no caller** anywhere in the codebase | ❌ (XAMPP has no MTA; template missing) | Hardcoded `From: nitinr@aosware.com`. Dead code inherited from another product. |
| 5 | **PHPMailer 5.x / SMTP** | `libraries/classes/class.phpmailer.php`, `class.smtp.php` (vendored, included by every `bootstrap.php`) | — | ❌ **never instantiated** (`new PHPMailer` / `IsSMTP` / `->Send()` appear nowhere outside the class files) | ❌ | Included but unused. |
| 6 | **Multi-tenant DB routing** | `libraries/config.php`, `libraries/classes/functions.php::masterDBInfo()`, `webservices/check_licence_key.php` | master DB `clients.repository_db` selects which `*_template_repository` schema a client's data lives in; `app_keys` maps an API key → client | ✅ (design) | ⚠️ partial — local `config.php` hardcodes a single repository DB; `masterDBInfo()` returns a hardcoded host/password and is not exercised | Effectively single-tenant locally. |
| 7 | **CSV import/export** | `admin/m/master/users.php` (import), `admin/m/master/export.php` | `fgetcsv` / streamed CSV; templates in `admin/uploads/`, `admin/m/master/templates/` | ✅ | ✅ (file-system only, no external service) | Users import auto-creates missing access levels / routes. |
| 8 | **File uploads (POD media)** | `webservices/upload_photo.php`, `upload_signature.php`, `update_item_qty.php` | `move_uploaded_file()` into local folders; filenames recorded in `driver_photos` / `signature_pics` / `product_pics` | ✅ | ✅ (local disk) | No cloud storage (no S3/GCS). |
| 9 | **GPS / device location** | `webservices/get_driver_current_location.php` | driver app POSTs lat/long → `INSERT driver_location` + `UPDATE users.latitude/longitude` | ✅ | ✅ | Consumed by the Google Map module. |
| 10 | **`webservice-dipali/` outbound curl** | `webservice-dipali/libraries/api_functions.php`, `_api/libraries/api_functions*.php` | generic `curl_init($url)` helper | ⚠️ helper only; `api_call/*.php` client stubs mostly commented | ❌ | Scaffolding for calling RouteTrac's own API from elsewhere. |

## Payment / SMS / other

* **No payment gateway** (no Stripe/PayPal/Braintree/Authorize.net references).
* **No SMS** (no Twilio/Nexmo/Plivo — the Twilio SDK seen elsewhere on this machine belongs to the unrelated `htdocs/sms` project, not RouteTrac).
* **No queue / cron** — the `cron`/`schedule` grep hits are all the word "schedule**d**_delivery" (a `deliveries` column), not job scheduling. No scheduled tasks.
* **No webhooks** inbound.
* **No analytics / monitoring / error-tracking** SDKs.

## Hardcoded secrets found (for remediation, not fixed in this baseline)

| Secret | File |
|---|---|
| Google Maps API key `<redacted: now GOOGLE_MAPS_JS_KEY / FCM_SERVER_KEY in config.local.php>` | `admin/m/google_map/*` (client JS) |
| Google Maps API key `<redacted: now GOOGLE_MAPS_JS_KEY / FCM_SERVER_KEY in config.local.php>` | `admin/m/google_map/*`, and reused as the **FCM server key** in `manifests_new.php` |
| DB password `<redacted: now in libraries/config.local.php>` (user `routetra_spark`) | `libraries/config.php` (non-local branch) |
| DB password `?b6ArR-tUsu)` + host `65.60.3.216` | `libraries/classes/functions.php::masterDBInfo()` |
| `From: nitinr@aosware.com` | `libraries/classes/functions.php::send_mail()` |
| API app key `123` | `routetra_RouteTrac_master.app_keys` (data) |
