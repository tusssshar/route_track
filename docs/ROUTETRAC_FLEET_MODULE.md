# RouteTrac — Fleet Module (vehicles, driver assignment, trips, load, toll & tax)

_Added 2026-10-02. Menu: **Fleet** (access menu id 10, granted to Administrator and Delivery Manager)._

## What it answers

| Question | Where |
|---|---|
| Which driver is driving which vehicle on which route today? | Fleet → Trips & Dispatch |
| Is the vehicle allowed on the road (insurance, fitness, permit, PUC)? | Fleet → Vehicles (warnings 30 days ahead), blocked at dispatch |
| How many SKUs / units are on a vehicle right now? | Vehicles list ("Units On Board"), Trip → Load Sheet |
| Where is the vehicle, and how far did it go? | Trip → Tracking & Timeline (GPS breadcrumbs, odometer km) |
| How much toll and tax did a vehicle/driver pay? | Trip → Toll, Tax & Expenses; Fleet → Vehicle Cost & Load Report |
| Cost per km, fuel mileage per vehicle/driver | Vehicle Cost & Load Report (CSV export) |

## Trip lifecycle and rules

A **trip** = one vehicle + one driver running one route on one date. Number format `TRP-YYYYMMDD-NNNN`.

```
Planned ──Mark Loaded──▶ Loaded ──Start (odometer)──▶ In Transit ──Complete (odometer)──▶ Completed
   └───────────── Cancel (reason) ◀──────────┘
```

Assignment is **blocked** when:
- the vehicle is `Maintenance` / `Inactive`, or any compliance document has expired on the trip date;
- the vehicle or the driver already has an active trip that day, or is `In Transit` on any trip;
- the driver is inactive or the driving licence expires before the trip date;
- the route already has an active trip that day, or has no orders for that date;
- outbound units (delivery-order qty) exceed the vehicle's `capacity_units` (0 = not enforced).

Effects:
- **Plan / Reassign / Mark Loaded / Start** attach the route's orders for the date (`deliveries.trip_id`) and set `deliveries.driver_id` to the trip driver, so they appear in that driver's app. Vehicle and driver can be changed until the trip starts.
- **Start** needs an odometer reading ≥ the vehicle's last reading; vehicle → `On Trip`. Cannot start before the trip date.
- **Complete** needs end odometer ≥ start. All stops must be closed (`Delivered`, `Refused`, `Exception`). The office can force-close with a return note; undelivered orders are released (`trip_id=0`) for re-dispatch. Vehicle → `Available`, odometer updated.
- **Cancel** (Planned/Loaded only, reason required) releases the orders.
- Every change is written to `trip_events` (timeline).

## Load on the vehicle (per SKU)

`loaded` = delivery-order qty · `delivered` = qty handed over (`qty_delivered`) · `picked_up` = qty collected on pickup orders ·
**on board = loaded − delivered + picked_up**. Refused goods therefore stay on board. Same calculation for packages.

## Expenses

Types: Toll, State Entry / Border Tax, Permit Tax, Green / Municipal Tax, Fuel, Parking, Loading / Unloading, Challan / Fine, Repair, Driver Allowance, Other.
"Tax" in reports = the three tax types. Payment modes: Cash, FASTag, UPI, Card, Company Account.
Each entry: amount > 0, location (toll plaza / check post), receipt no., optional receipt (jpg/png/pdf ≤ 5 MB → `uploads/expense/`), litres required for Fuel.
Drivers can add expenses only while In Transit; the office can add late receipts to completed trips.

## Screens (`admin/m/fleet/`)

| Page | Purpose |
|---|---|
| `trips.php` | Today's KPIs (trips, vehicles free/on trip, orders without a trip, toll/tax today), trip list with filters, plan/reassign form with live rule check |
| `trip_detail.php?trip_id=N` | Actions (Mark Loaded, Refresh Orders, Reassign, Start, Complete, Cancel), KPIs, Load Sheet, Stops, Toll/Tax/Expenses (+add with receipt), Tracking map (OpenStreetMap/Leaflet, no Google key) + timeline |
| `vehicles.php` | Vehicle master: capacity, FASTag, odometer, document expiry dates, status, current trip, units on board |
| `fleet_report.php` | Per vehicle / driver / trip: trips, km, stops, SKUs, units, toll (count + amount), tax, fuel, litres, other, total, cost/km, km/litre; CSV export |
| `admin/m/google_map/locate_drivers.php` (Map → Track Drivers) | **Live Fleet Map** on OpenStreetMap (no Google key): running vehicles at their last GPS position with today's track and closed stops, signal age (green < 5 min, amber 5–30 min, red > 30 min), other drivers' positions in the last 24 h, refresh every 30 s; History mode = one driver's track for a date range. Data: `ajax/live_positions.php`. Replaces the old Google Maps version (its `ajax/locate_drivers.php` and `ajax/get_datewise_route.php` were removed 2026-10-02; copies in `db_backups/removed_code_20261002/`). |

Users form: drivers now have **Licence No**, **Valid Till**, **Mobile**.

## Driver phone page — `driver/` (GPS without the native app)

`http(s)://<server>/routetrac/driver/` — driver logs in with the same username/password. Shows the trip, vehicle, stops delivered, units on board, toll/tax so far; Start/Complete with odometer; add toll/tax/fuel with a camera photo of the receipt; load list.
While open it sends the phone's GPS every 30 s (`navigator.geolocation`) and keeps the screen awake where supported.

- **Needs HTTPS** on the live server — browsers only give location to secure pages (localhost is exempt).
- Tracks only while the page is open/in the foreground. Continuous background tracking needs the native app.
- No Google account, API key or billing needed (maps use OpenStreetMap).

## Mobile API (`webservices/`, same `{Key,msg,info}` envelope, `app_key` param)

| Endpoint | Params | Notes |
|---|---|---|
| `trip_current.php` | `driver_id` | current trip, vehicle, progress, load (per SKU), packages, expense totals, expense types, `can_start` / `can_complete` |
| `trip_start.php` | `driver_id, trip_id, odometer [, latitude, longitude]` | |
| `trip_complete.php` | `driver_id, trip_id, odometer [, latitude, longitude]` | refused while stops are open |
| `trip_add_expense.php` | `driver_id, trip_id, expense_type, amount [, payment_mode, location, reference_no, fuel_litres, odometer_km, latitude, longitude, expense_time]` + file `receipt` | |
| `trip_expense_list.php` | `driver_id, trip_id` | entries with `receipt_url`, totals, types, modes |
| `get_driver_current_location.php` | `driver_id, latitude, longitude` | **changed**: validates coordinates, tags the point with the running trip/vehicle, returns `info.trip_id` |

All trip endpoints refuse a trip that does not belong to `driver_id`. (`app_key` is still not enforced, as before.)

## Database (`database/migrations/2026-10-02_fleet_module.sql`, idempotent)

New: `vehicles`, `trips`, `trip_expenses`, `trip_events`. Added columns: `deliveries.trip_id`, `driver_location.trip_id/vehicle_id`, `users.license_no/license_expiry/phone`. Master DB: `access_menu` 10 "Fleet", granted to levels 1 and 4.
Business rules live in one place: `libraries/classes/fleet.php` (used by admin pages, the API and the driver page).

## Driver KYC and driver history

- **User form (Staff → Users, "Is Driver")**: licence no, valid till, **licence classes** (MCWG, LMV, LMV-TR, HMV), **experience (years)**, **Aadhaar no** (12 digits, Verhoeff checksum, unique per person, always shown masked `XXXX XXXX 1234`; leave empty to keep the number on file), **licence copy** and **Aadhaar copy** (JPG/PNG/WEBP/PDF ≤ 3 MB, checked by content).
- Copies are stored in `uploads/driver_docs/` which Apache refuses to serve (`.htaccess`); staff with the Users menu view them through `admin/m/master/driver_doc.php`.
- **Dispatch rule**: trucks / containers / trailers need **HMV**; vans, pickups, mini trucks and three-wheelers need **LMV-TR or HMV**; two-wheelers need **MCWG**. Checked only when classes are recorded.
- **Driver page tabs**: *Current Trip* · *My Trips* (own finished trips, newest first: route, vehicle, km, stops delivered, toll/tax/fuel, settlement "You return X / Company pays you X" and whether it is settled; tap for stops with receiver names and expenses; month and all-time totals; open settlements) · *Profile* (licence, classes, expiry warning within 30 days, experience, masked Aadhaar, documents on file). A driver can only open their own trips.

## Look & branding (Staff → Company Settings → Branding & Theme)

- **System name, title, logo** (PNG/JPG/WEBP ≤ 1 MB, checked by real image type; SVG refused), **footer text** (`{year}` placeholder), **support phone/email** — shown in the top bar, browser tab, login page, driver page, footer and trip sheets.
- **9 theme presets** (Highway [default], Night Drive [dark], Green Route, Express Red, Ocean Freight, Desert Road, Purple Fleet, Clean White, Classic), optional **custom primary/accent colour**, **font** (Poppins, Inter, Roboto, Montserrat, Nunito, Open Sans, Lato, Rubik, Mukta). Live preview on the settings page; Save applies it for everyone.
- Implementation: `admin/templates/css/routetrac-theme.css` (all colours are CSS variables), `company_settings::head_tags()` writes the company's variables + font + favicon into every page; button text colour is picked automatically for contrast. Page scripts and the stylesheet are versioned by file time (`?v=`), so browsers pick up changes without a manual cache clear.

## Company settings (Staff → Company Settings, Administrator only)

Stored per client DB in `company_settings` (migration `2026-10-02_company_settings.sql`, `2026-10-02_trip_settlement.sql`). Defaults: `Asia/Kolkata`, INR, km, litres.

| Setting | Rule |
|---|---|
| Timezone | Decides "today" (trips, orders, reports) and every time shown/stored. Applied to PHP and the MySQL session on every request. Can change any time; with "convert" on, every stored DATETIME is shifted (daylight saving handled per period). DATE fields (trip date, scheduled delivery, expiry dates) are business dates and are not shifted; TIMESTAMP columns follow the session by themselves. |
| Currency | Symbol on all amounts; locks once any expense exists (amounts are not converted). |
| Distance / fuel unit | km or mi, litres or US gallons — labels for what staff and drivers enter; lock once odometer readings / fuel fills exist. |
| Company name, address, tax ID | Printed on trip sheets and delivery receipts. |

GPS and maps do not depend on the timezone.

## GPS timing rules

- The phone sends **when the reading was taken** (`recorded_at` / batch `t`, unix s or ms) and its accuracy; the server stores that time in company time (`add_date_time`) plus `received_at`.
- Readings are tagged with the trip that was running **at that moment** (so late offline points still land on the right trip).
- Refused: more than 5 min in the future, older than 24 h, invalid or 0,0 coordinates. Accuracy worse than 100 m is stored but not drawn on the route.
- A late (older) reading never moves the driver's "current position".
- The driver page queues readings on the phone (kept across reloads, up to 2000) and sends them in batches of up to 500 when the signal returns. `get_driver_current_location.php` accepts the same batch as `points=[{"lat","lng","t","acc"}]`.

## Trip sheet and driver settlement

**Trip Sheet** (`admin/m/fleet/trip_sheet.php?trip_id=N`, button on a completed trip, icon in the trip list) — the closing document of a **completed** trip, printable / Save as PDF, or `&export=csv`:
trip header (vehicle, driver + licence, times, odometer, distance, mileage, cost per km) → **proof of delivery per stop** (ordered / accepted / short or refused per SKU, packages, receiver name + signature, time, GPS, exceptions, driver notes) → **goods reconciliation** (loaded → delivered → picked up → returned to depot) → **every toll, tax, fuel and other expense** (paid by, reference, receipt attached / missing) → **driver cash settlement** → sign-off lines (driver, fleet manager, accounts).
**Delivery receipt** for one stop (`&delivery_id=M`, "Receipt" link on each closed stop) — the POD to hand to the customer.

Settlement rules:
- `cash_advance` per trip (editable until settled). Driver-paid = Cash + UPI expenses; company-paid = FASTag, Card, Company Account.
- Balance = advance − driver-paid → "Driver returns X" or "Company reimburses driver X".
- **Approve Trip Sheet & Settle** only on a Completed trip; if driver-paid expenses have no receipt photo a note is required.
- After settling, expenses can't be added or deleted and the advance is locked (app and office). Logged on the timeline.

## Demo data

`php database/seed_demo_data.php --yes` **wipes all repository data except the `admin` login** (and files under `uploads/`) and loads a Pune FMCG distributor's week:
7 vehicles (one in maintenance, one with insurance expiring soon, one with expired PUC), 6 drivers + dispatcher `priya` (all password `12345`; drivers `ramesh`, `suresh`, `vijay`, `anil`, `sunil`, `ganesh`),
6 routes (Pune West/East, Mumbai, Thane, Nashik, Belagavi in Karnataka), 23 shops, 12 SKUs + 2 return items, ~30 trips over the last 7 days (one cancelled for breakdown and re-dispatched),
real NH48 / Expressway / NH60 toll plazas, Karnataka border tax, fuel, parking, allowances, GPS tracks. Today: one trip In Transit, one Loaded, one Planned, one route with orders but no trip; tomorrow one Planned trip.
Trip sheets: older trips settled, yesterday's and today's waiting for approval; customer signatures and receipt photos generated. Times are relative to "now" in the company timezone, so it can be re-run any time.

## Tests

`bash docs/fleet_test.sh` — 144 end-to-end checks (incl. trip sheet + settlement); `bash docs/settings_test.sh` — 49 checks (settings, locks, GPS timing, timezone round trip, branding/theme/logo; needs the demo data); (rules, lifecycle, load maths, expenses + receipts, GPS tagging, driver page, report CSV, access control); creates and removes its own data. `bash docs/regression_test.sh` still passes (117).
