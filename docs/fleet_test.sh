#!/usr/bin/env bash
# RouteTrac Fleet module end-to-end test (vehicles, trips, load, expenses, tracking, driver phone page, report).
# Same requirements as regression_test.sh. WRITES and then deletes its own test data: run on a local/test copy only.
# Usage: bash docs/fleet_test.sh      (exit code = number of failed checks)

BASE=${BASE:-http://localhost/projects/routetrac}
MYSQL=${MYSQL:-/c/xampp/mysql/bin/mysql}
DB=${DB:-RouteTrac_template_repository}
APP=$(cd "$(dirname "$0")/.." && pwd)
TMP=$(mktemp -d); command -v cygpath >/dev/null && TMP=$(cygpath -m "$TMP")
JAR=$TMP/cookies.txt; DJAR=$TMP/driver_cookies.txt; RJAR=$TMP/rm_cookies.txt
TODAY=$(date +%F); YESTERDAY=$(date -d yesterday +%F); NEXTYEAR=$(date -d "+1 year" +%F)
TAG=FT$(date +%H%M%S)
FAIL=0; PASS=0

q()     { "$MYSQL" -uroot -N "$DB" -e "$1"; }
ok()    { PASS=$((PASS+1)); printf "  PASS  %s\n" "$1"; }
bad()   { FAIL=$((FAIL+1)); printf "  FAIL  %s  -- %s\n" "$1" "$2"; }
check() { if [ "$2" = "$3" ]; then ok "$1"; else bad "$1" "expected [$3] got [$2]"; fi; }
has()   { if printf '%s' "$2" | grep -q -- "$3"; then ok "$1"; else bad "$1" "missing [$3] in [$(printf '%s' "$2" | head -c 200)]"; fi; }
post()  { curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/$1" "${@:2}"; }
api()   { curl -s -X POST "$BASE/webservices/$1.php" -d "app_key=123" "${@:2}"; }
fleet() { post admin/m/fleet/ajax/$1.php "${@:2}"; }
act()   { fleet trip_action -d "trip_id=$TRIP" "$@"; }
dapi()  { curl -s -b "$DJAR" -c "$DJAR" -X POST "$BASE/driver/api.php" "$@"; }

touch "$APP/.debug"; mkdir -p "$APP/logs"; : > "$APP/logs/php_errors.log"
printf '\x89PNG\r\n\x1a\n' > "$TMP/r.png"; printf '%%PDF-1.4\n%%%%EOF\n' > "$TMP/r.pdf"; echo "x" > "$TMP/r.exe"

echo "== Test data ($TAG)"
P=$(printf %s pass123 | md5sum | cut -d' ' -f1)
q "INSERT INTO users (status,first_name,last_name,email_addr,acct_username,acct_password,access_level,is_driver,default_route_id,license_expiry) VALUES
   ('1','$TAG','DriverOne','$TAG.1@t.local','${TAG}d1','$P',3,1,0,'$NEXTYEAR'),
   ('1','$TAG','DriverTwo','$TAG.2@t.local','${TAG}d2','$P',3,1,0,'$YESTERDAY'),
   ('1','$TAG','RouteMgr','$TAG.3@t.local','${TAG}rm','$P',3,0,0,NULL)"
D1=$(q "SELECT user_id FROM users WHERE acct_username='${TAG}d1'"); D2=$(q "SELECT user_id FROM users WHERE acct_username='${TAG}d2'")
q "INSERT INTO routes (area_id,name) VALUES (1,'$TAG R1'),(1,'$TAG R2')"
R1=$(q "SELECT route_id FROM routes WHERE name='$TAG R1'"); R2=$(q "SELECT route_id FROM routes WHERE name='$TAG R2'")
q "INSERT INTO customers (customer_name,account_number) VALUES ('$TAG Cust','$TAG')"; C=$(q "SELECT customer_id FROM customers WHERE account_number='$TAG'")
q "INSERT INTO manifests (submitted_by_user_id,manifest_type) VALUES (4,'Delivery')"; MD=$(q "SELECT MAX(manifest_id) FROM manifests")
q "INSERT INTO manifests (submitted_by_user_id,manifest_type) VALUES (4,'Pickup')"; MP=$(q "SELECT MAX(manifest_id) FROM manifests")
for spec in "$MD,$R1,1" "$MD,$R1,2" "$MP,$R1,3" "$MD,$R2,1"; do IFS=, read m r s <<< "$spec"
  q "INSERT INTO deliveries (manifest_id,route_id,driver_id,stop_number,customer_id,address_id,status,order_number,cust_po_num,delivery_notes,driver_notes,scheduled_delivery,delivery_time,signature_text,signature_pic_id,latitude,longitude,exception_reason,exception_note)
     VALUES ($m,$r,4,$s,$C,0,'In Transit','$TAG-$r-$s','','','','$TODAY','2000-01-01 00:00:00','',0,'18.5$s','73.8$s','','')"; done
O1=$(q "SELECT delivery_id FROM deliveries WHERE order_number='$TAG-$R1-1'"); O2=$(q "SELECT delivery_id FROM deliveries WHERE order_number='$TAG-$R1-2'")
O3=$(q "SELECT delivery_id FROM deliveries WHERE order_number='$TAG-$R1-3'"); O4=$(q "SELECT delivery_id FROM deliveries WHERE order_number='$TAG-$R2-1'")
q "INSERT INTO delivery_items (delivery_id,sku,qty,product_name,description,uom,qty_delivered,upc_code) VALUES
   ($O1,'$TAG-A',10,'Widget A','','EA',0,''),($O1,'$TAG-B',5,'Widget B','','EA',0,''),($O2,'$TAG-A',4,'Widget A','','EA',0,''),
   ($O3,'$TAG-R',3,'Returned crate','','EA',0,''),($O4,'$TAG-A',2,'Widget A','','EA',0,'')"
q "INSERT INTO delivery_packages (delivery_id,package_type_id,qty,qty_delivered) VALUES ($O1,1,2,0),($O2,1,1,0)"
echo "  orders $O1 $O2 $O3(pickup) on R1, $O4 on R2; drivers $D1 $D2"

echo "== Access"
check "fleet ajax needs login" "$(curl -s -o /dev/null -w '%{http_code}' $BASE/admin/m/fleet/ajax/vehicles.php)" "302"
curl -s -c "$RJAR" -o /dev/null -d "al_username=${TAG}rm&al_password=pass123&submit=SIGN+IN" $BASE/index.php
check "route manager has no fleet access" "$(curl -s -b "$RJAR" -o /dev/null -w '%{http_code}' -X POST $BASE/admin/m/fleet/ajax/vehicles.php)" "403"
check "admin login" "$(curl -s -c "$JAR" -o /dev/null -w '%{http_code}' -d 'al_username=admin&al_password=12345&submit=SIGN+IN' $BASE/index.php)" "302"
has "Fleet menu shown" "$(curl -s -b "$JAR" $BASE/admin/home.php)" "Trips &amp; Dispatch"
for p in vehicles trips fleet_report; do has "page $p" "$(curl -s -b "$JAR" $BASE/admin/m/fleet/$p.php)" "detail-page"; done

echo "== Vehicles"
vf="registration_no=${TAG}V1&vehicle_type=Mini+Truck&make_model=Tata+Ace&fuel_type=Diesel&capacity_units=100&capacity_kg=750&odometer_km=1000&fastag_id=FT1&status=Available&notes="
docs="insurance_expiry=$NEXTYEAR&fitness_expiry=$NEXTYEAR&permit_expiry=$NEXTYEAR&pollution_expiry=$NEXTYEAR"
has "add vehicle" "$(fleet vehicles_new -d "$vf&$docs&submit_vehicle=1")" '"success":1'
V1=$(q "SELECT vehicle_id FROM vehicles WHERE registration_no='${TAG}V1'")
has "duplicate registration rejected" "$(fleet vehicles_new -d "$vf&$docs&submit_vehicle=1")" "already exists"
has "add vehicle with expired insurance" "$(fleet vehicles_new -d "${vf/V1/V2}&insurance_expiry=$YESTERDAY&fitness_expiry=&permit_expiry=&pollution_expiry=&submit_vehicle=1")" '"success":1'
has "add small vehicle" "$(fleet vehicles_new -d "${vf/V1/V3}&$docs&submit_vehicle=1" -d capacity_units=5)" '"success":1'
V2=$(q "SELECT vehicle_id FROM vehicles WHERE registration_no='${TAG}V2'"); V3=$(q "SELECT vehicle_id FROM vehicles WHERE registration_no='${TAG}V3'")
check "blank expiry stored as NULL" "$(q "SELECT IFNULL(fitness_expiry,'NULL') FROM vehicles WHERE vehicle_id=$V2")" "NULL"
check "small vehicle capacity" "$(q "SELECT capacity_units FROM vehicles WHERE vehicle_id=$V3")" "5"
has "cannot set On Trip by hand" "$(fleet vehicles_new -d "${vf/Available/On+Trip}&$docs&vehicle_id=$V1&submit_vehicle=1")" "set automatically"
has "vehicle list shows expiry warning" "$(fleet vehicles -d "txtSearch=${TAG}V2")" "Insurance expired"

echo "== Trip planning rules"
plan="trip_date=$TODAY&route_id=$R1&helper_name=Ramu&notes=&submit_trip=1"
pv=$(fleet trip_action -d "action=preview&trip_date=$TODAY&route_id=$R1&vehicle_id=$V1&driver_id=$D1")
has "preview counts orders" "$pv" '"orders":3'
has "preview counts outbound units (pickups excluded)" "$pv" '"units":19'
has "expired document blocks dispatch" "$(fleet trips_new -d "$plan&vehicle_id=$V2&driver_id=$D1")" "documents not valid"
has "overload blocked" "$(fleet trips_new -d "$plan&vehicle_id=$V3&driver_id=$D1")" "Overloaded: 19 units"
has "expired licence blocked" "$(fleet trips_new -d "$plan&vehicle_id=$V1&driver_id=$D2")" "licence"
has "past date blocked" "$(fleet trips_new -d "${plan/$TODAY/$YESTERDAY}&vehicle_id=$V1&driver_id=$D1")" "past"
q "UPDATE users SET license_expiry='$NEXTYEAR' WHERE user_id=$D2"
q "UPDATE users SET license_classes='MCWG' WHERE user_id=$D2"
has "licence class blocks the vehicle" "$(fleet trip_action -d "action=preview&trip_date=$TODAY&route_id=$R1&vehicle_id=$V1&driver_id=$D2")" "needs LMV-TR or HMV"
q "UPDATE users SET license_classes='LMV-TR' WHERE user_id=$D2"
r=$(fleet trips_new -d "$plan&vehicle_id=$V1&driver_id=$D1"); has "plan trip" "$r" '"success":1'
TRIP=$(q "SELECT trip_id FROM trips WHERE vehicle_id=$V1 AND status='Planned'")
has "trip number format" "$(q "SELECT trip_number FROM trips WHERE trip_id=$TRIP")" "TRP-$(date +%Y%m%d)-"
check "orders attached to trip" "$(q "SELECT COUNT(*) FROM deliveries WHERE trip_id=$TRIP")" "3"
check "orders handed to trip driver" "$(q "SELECT COUNT(*) FROM deliveries WHERE trip_id=$TRIP AND driver_id=$D1")" "3"
has "second trip on same route blocked" "$(fleet trips_new -d "$plan&vehicle_id=$V3&driver_id=$D2")" "already has trip"
has "vehicle double-booking blocked" "$(fleet trips_new -d "${plan/route_id=$R1/route_id=$R2}&vehicle_id=$V1&driver_id=$D2")" "already on trip"
has "driver double-booking blocked" "$(fleet trips_new -d "${plan/route_id=$R1/route_id=$R2}&vehicle_id=$V3&driver_id=$D1")" "already on trip"
has "reassign driver" "$(fleet trips_new -d "trip_id=$TRIP&vehicle_id=$V1&driver_id=$D2&helper_name=&notes=&submit_trip=1")" '"success":1'
check "orders follow the new driver" "$(q "SELECT COUNT(*) FROM deliveries WHERE trip_id=$TRIP AND driver_id=$D2")" "3"
has "reassign back" "$(fleet trips_new -d "trip_id=$TRIP&vehicle_id=$V1&driver_id=$D1&helper_name=Ramu&notes=&submit_trip=1")" '"success":1'
has "reassignment logged" "$(q "SELECT note FROM trip_events WHERE trip_id=$TRIP AND event_type='Reassigned' ORDER BY event_id LIMIT 1")" "DriverOne -> $TAG DriverTwo"
has "trip list shows trip" "$(fleet trips -d "vehicle_id=$V1")" "$TAG R1"

echo "== Loading and dispatch"
has "mark loaded" "$(act -d action=loaded)" '"success":1'
has "loaded event has SKU count" "$(q "SELECT note FROM trip_events WHERE trip_id=$TRIP AND event_type='Loaded'")" "3 SKUs, 19 units, 3 packages"
cur=$(api trip_current -d "driver_id=$D1")
has "driver app sees trip" "$cur" '"status":"Loaded"'
has "driver app can start" "$cur" '"can_start":1'
has "start below last odometer rejected" "$(api trip_start -d "driver_id=$D1&trip_id=$TRIP&odometer=900")" "below the vehicle"
has "other driver cannot start" "$(api trip_start -d "driver_id=$D2&trip_id=$TRIP&odometer=1000")" "not found for this driver"
has "driver starts trip" "$(api trip_start -d "driver_id=$D1&trip_id=$TRIP&odometer=1010&latitude=18.5&longitude=73.8")" '"Trip started"'
check "vehicle On Trip" "$(q "SELECT status FROM vehicles WHERE vehicle_id=$V1")" "On Trip"
has "vehicle cannot go to maintenance mid-trip" "$(fleet vehicles_new -d "${vf/Available/Maintenance}&$docs&vehicle_id=$V1&odometer_km=1000&submit_vehicle=1")" "complete the trip first"
has "loading a running trip refused" "$(act -d action=loaded)" "Only a Planned"

echo "== Tracking"
has "GPS ping tagged with trip" "$(api get_driver_current_location -d "driver_id=$D1&latitude=18.52&longitude=73.85")" "\"trip_id\":\"$TRIP\""
api get_driver_current_location -d "driver_id=$D1&latitude=18.53&longitude=73.86" > /dev/null
has "bad GPS rejected" "$(api get_driver_current_location -d "driver_id=$D1&latitude=999&longitude=73")" "Location refused"
check "points stored with vehicle" "$(q "SELECT COUNT(*) FROM driver_location WHERE trip_id=$TRIP AND vehicle_id=$V1")" "2"
map=$(curl -s -b "$JAR" $BASE/admin/m/google_map/locate_drivers.php)
has "live map page uses OpenStreetMap" "$map" "leaflet.js"
check "live map page does not load Google Maps" "$(printf '%s' "$map" | grep -c 'maps.googleapis' | tr -d '\r')" "0"
live=$(curl -s -b "$JAR" "$BASE/admin/m/google_map/ajax/live_positions.php?mode=live")
has "live map shows running vehicle" "$live" "\"registration_no\":\"${TAG}V1\""
has "live map shows last GPS signal" "$live" '"state":"live"'
has "history track for the driver" "$(curl -s -b "$JAR" "$BASE/admin/m/google_map/ajax/live_positions.php?mode=history&driver_id=$D1&from=$TODAY&to=$TODAY")" '"count":2'
check "map feed refused without map access" "$(curl -s -b "$RJAR" -o /dev/null -w '%{http_code}' "$BASE/admin/m/google_map/ajax/live_positions.php")" "403"

echo "== Toll, tax, expenses"
has "toll with receipt (app)" "$(curl -s -X POST $BASE/webservices/trip_add_expense.php -F app_key=123 -F driver_id=$D1 -F trip_id=$TRIP -F expense_type=Toll -F amount=85 -F payment_mode=FASTag -F 'location=Khed Shivapur Plaza' -F "receipt=@$TMP/r.png")" '"toll":85'
has "state tax (app)" "$(api trip_add_expense -d "driver_id=$D1&trip_id=$TRIP&amount=500&payment_mode=Cash" --data-urlencode "expense_type=State Entry / Border Tax")" '"tax":500'
has "fuel needs litres" "$(api trip_add_expense -d "driver_id=$D1&trip_id=$TRIP&expense_type=Fuel&amount=2000")" "fuel quantity"
has "negative amount rejected" "$(api trip_add_expense -d "driver_id=$D1&trip_id=$TRIP&expense_type=Toll&amount=-5")" "greater than zero"
has "unknown type rejected" "$(api trip_add_expense -d "driver_id=$D1&trip_id=$TRIP&expense_type=Party&amount=5")" "select an expense type"
has "bad receipt type rejected" "$(curl -s -X POST $BASE/webservices/trip_add_expense.php -F app_key=123 -F driver_id=$D1 -F trip_id=$TRIP -F expense_type=Toll -F amount=5 -F "receipt=@$TMP/r.exe")" "Receipt must be"
has "other driver cannot add expense" "$(api trip_add_expense -d "driver_id=$D2&trip_id=$TRIP&expense_type=Toll&amount=5")" "not found for this driver"
has "fuel (office, pdf receipt)" "$(post admin/m/fleet/ajax/trip_action.php -F action=add_expense -F trip_id=$TRIP -F expense_type=Fuel -F amount=2000 -F fuel_litres=20 -F payment_mode=Card -F "receipt=@$TMP/r.pdf")" '"success":1'
check "receipts stored" "$(q "SELECT COUNT(*) FROM trip_expenses WHERE trip_id=$TRIP AND receipt_file<>''")" "2"
F=$(q "SELECT receipt_file FROM trip_expenses WHERE trip_id=$TRIP AND expense_type='Toll'")
check "receipt file served" "$(curl -s -o /dev/null -w '%{http_code}' $BASE/uploads/expense/$F)" "200"
el=$(api trip_expense_list -d "driver_id=$D1&trip_id=$TRIP"); has "expense list total" "$el" '"total":2585'
has "expense list has types" "$el" '"expense_types"'
X=$(q "SELECT expense_id FROM trip_expenses WHERE trip_id=$TRIP AND expense_type='Fuel'")
has "delete expense of another trip refused" "$(fleet trip_action -d "action=delete_expense&trip_id=999999&expense_id=$X")" "not found"

echo "== Driver phone page"
has "driver page loads" "$(curl -s $BASE/driver/index.php)" "Driver Login"
has "driver page needs login" "$(dapi -d action=trip)" "Please log in"
has "non-driver cannot log in" "$(dapi -d action=login -d username=${TAG}rm -d password=pass123)" "not an active driver"
has "driver logs in" "$(dapi -d action=login -d username=${TAG}d1 -d password=pass123)" '"success":1'
has "driver page trip" "$(dapi -d action=trip)" '"status":"In Transit"'
has "driver page GPS" "$(dapi -d action=location --data-urlencode "points=[{\"lat\":18.54,\"lng\":73.87,\"t\":$(date +%s)000,\"acc\":12}]")" "\"trip_id\":$TRIP"
has "driver page parking expense" "$(dapi -d action=expense -d expense_type=Parking -d amount=40 -d payment_mode=UPI)" '"success":1'

echo "== Stops and load on vehicle"
# Stop 1 fully delivered, stop 2: 1 of 4 refused, pickup collected 3
q "UPDATE delivery_items SET qty_delivered=qty WHERE delivery_id=$O1"; q "UPDATE delivery_items SET qty_delivered=3 WHERE delivery_id=$O2"
q "UPDATE delivery_packages SET qty_delivered=qty WHERE delivery_id IN ($O1,$O2)"
q "UPDATE deliveries SET status='Delivered', delivery_time=NOW() WHERE delivery_id IN ($O1,$O2)"
cur=$(api trip_current -d "driver_id=$D1")
has "on board = loaded - delivered + picked up (before pickup)" "$cur" '"on_board_units":1'
has "complete refused while pickup stop open (app)" "$(api trip_complete -d "driver_id=$D1&trip_id=$TRIP&odometer=1100")" "1 of 3 stops are still open"
q "UPDATE delivery_items SET qty_delivered=3 WHERE delivery_id=$O3"; q "UPDATE deliveries SET status='Delivered' WHERE delivery_id=$O3"
has "picked-up goods counted on board" "$(api trip_current -d "driver_id=$D1")" '"on_board_units":4'
has "trip page shows load sheet" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_detail.php?trip_id=$TRIP")" "$TAG-R"
has "trip page shows toll" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_detail.php?trip_id=$TRIP")" "Khed Shivapur Plaza"

echo "== Completion"
has "end odometer below start rejected" "$(api trip_complete -d "driver_id=$D1&trip_id=$TRIP&odometer=1000")" "at least the start"
has "driver completes trip" "$(api trip_complete -d "driver_id=$D1&trip_id=$TRIP&odometer=1130")" '"distance_km":120'
check "vehicle available again" "$(q "SELECT status FROM vehicles WHERE vehicle_id=$V1")" "Available"
check "vehicle odometer updated" "$(q "SELECT odometer_km FROM vehicles WHERE vehicle_id=$V1")" "1130"
has "app shows no trip" "$(api trip_current -d "driver_id=$D1")" "No trip assigned"
has "app cannot add expense after completion" "$(api trip_add_expense -d "driver_id=$D1&trip_id=$TRIP&expense_type=Toll&amount=5")" "only while the trip is In Transit"
has "office can add late receipt" "$(act -d action=add_expense -d expense_type=Toll -d amount=15 -d payment_mode=Cash)" '"success":1'

echo "== Report"
rep=$(curl -s -b "$JAR" "$BASE/admin/m/fleet/fleet_report.php?export=1&group_by=vehicle&vehicle_id=$V1&date_from=$TODAY&date_to=$TODAY")
has "CSV header" "$rep" "Toll Plazas"
# trips,completed,km,stops,delivered,skus,loaded,delivered units,toll plazas,toll,tax,fuel,litres,other,total,cost/km,km/l
has "CSV vehicle row" "$rep" "${TAG}V1,1,1,120,3,3,3,19,18,2,100.00,500.00,2000.00,20,40.00,2640.00,22.00,6"
has "report by driver" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/fleet_report.php?group_by=driver&driver_id=$D1&date_from=$TODAY&date_to=$TODAY")" "$TAG DriverOne"

echo "== Trip sheet and driver settlement"
q "UPDATE deliveries SET signature_text='R. Joshi' WHERE delivery_id=$O1"
sheet=$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_sheet.php?trip_id=$TRIP")
has "trip sheet for completed trip" "$sheet" "TRIP SHEET"
has "sheet pending settlement" "$sheet" "PENDING SETTLEMENT"
has "sheet proof of delivery: receiver" "$sheet" "R. Joshi"
has "sheet shows short delivery" "$sheet" '<td class="n bad">1</td>'
has "sheet goods reconciliation" "$sheet" "Returned to depot"
has "sheet lists toll plaza" "$sheet" "Khed Shivapur Plaza"
has "sheet flags cash expense without receipt" "$sheet" "Missing"
csv=$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_sheet.php?trip_id=$TRIP&export=csv")
has "sheet CSV: POD" "$csv" "PROOF OF DELIVERY"
has "sheet CSV: settlement" "$csv" "DRIVER SETTLEMENT"
has "delivery receipt for one stop" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_sheet.php?trip_id=$TRIP&delivery_id=$O1")" "DELIVERY RECEIPT"
has "receipt refused for order of another trip" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_sheet.php?trip_id=$TRIP&delivery_id=$O4")" "not on trip"
check "sheet needs fleet access" "$(curl -s -b "$RJAR" "$BASE/admin/m/fleet/trip_sheet.php?trip_id=$TRIP")" "You do not have access to Fleet"
has "trip list links the sheet" "$(fleet trips -d "status=Completed&vehicle_id=$V1")" "trip_sheet.php?trip_id=$TRIP"
has "set cash advance" "$(act -d action=set_advance -d cash_advance=3000)" '"success":1'
# driver paid: state tax 500 (Cash) + parking 40 (UPI) + late toll 15 (Cash) = 555 -> driver returns 2445
has "settlement balance" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_detail.php?trip_id=$TRIP")" "Driver returns ₹2,445.00"
has "settle needs note for missing receipts" "$(act -d action=settle -d settlement_note=)" "have no receipt"
has "settle with note" "$(act -d action=settle -d 'settlement_note=Paper slips lost, verified by phone')" '"success":1'
check "trip settled" "$(q "SELECT settlement_status FROM trips WHERE trip_id=$TRIP")" "Settled"
has "sheet shows settled" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_sheet.php?trip_id=$TRIP")" ">SETTLED<"
has "settled: no new expenses" "$(act -d action=add_expense -d expense_type=Toll -d amount=10 -d payment_mode=Cash)" "expenses are locked"
has "settled: advance locked" "$(act -d action=set_advance -d cash_advance=1)" "locked"
has "settled: expense delete refused" "$(act -d action=delete_expense -d expense_id=$(q "SELECT MIN(expense_id) FROM trip_expenses WHERE trip_id=$TRIP"))" "settled"
has "settle twice refused" "$(act -d action=settle -d settlement_note=x)" "already settled"

echo "== Driver KYC and history"
"${PHP:-/c/xampp/php/php.exe}" -r '$i=imagecreatetruecolor(60,40); imagepng($i, $argv[1]);' "$TMP/dl.png"
uform() { curl -s -b "$JAR" -X POST "$BASE/admin/m/master/ajax/users_new.php" -F submit=1 -F user_id=$1 -F first_name=$TAG -F last_name=DriverOne -F "email_addr=$TAG.1@t.local" \
  -F status=1 -F access_level=3 -F is_driver=1 -F default_route_id=0 -F license_no=mh12abc -F license_expiry=$NEXTYEAR -F phone=9800000000 "${@:2}"; }
has "invalid Aadhaar refused" "$(uform $D1 -F aadhaar_no=234123412345 -F experience_years=5)" "Aadhaar number is not valid"
has "experience must be sensible" "$(uform $D1 -F aadhaar_no= -F experience_years=99)" "0-60"
has "driver KYC saved with licence copy" "$(uform $D1 -F 'aadhaar_no=2341 2341 2346' -F experience_years=7 -F 'license_classes[]=LMV-TR' -F 'license_classes[]=HMV' -F "license_doc=@$TMP/dl.png;type=image/png")" '"success":1'
check "KYC stored" "$(q "SELECT CONCAT(aadhaar_no,'|',experience_years,'|',license_classes,'|',license_no,'|',license_doc<>'') FROM users WHERE user_id=$D1")" "234123412346|7|LMV-TR,HMV|MH12ABC|1"
has "Aadhaar shown masked in the form" "$(curl -s -b "$JAR" -X POST -d user_id=$D1 $BASE/admin/m/master/ajax/users_new.php)" "XXXX XXXX 2346"
check "full Aadhaar never printed in the form" "$(curl -s -b "$JAR" -X POST -d user_id=$D1 $BASE/admin/m/master/ajax/users_new.php | grep -c 234123412346)" "0"
check "blank Aadhaar keeps the number on file" "$(uform $D1 -F aadhaar_no= -F experience_years=7 -F 'license_classes[]=LMV-TR' -F 'license_classes[]=HMV' > /dev/null; q "SELECT aadhaar_no FROM users WHERE user_id=$D1")" "234123412346"
has "same Aadhaar for another user refused" "$(uform $D2 -F aadhaar_no=234123412346 -F "email_addr=$TAG.2@t.local")" "already registered"
DOC=$(q "SELECT license_doc FROM users WHERE user_id=$D1")
check "licence copy not reachable directly" "$(curl -s -o /dev/null -w '%{http_code}' $BASE/uploads/driver_docs/$DOC)" "403"
check "licence copy viewable by admin" "$(curl -s -b "$JAR" -o /dev/null -w '%{http_code} %{content_type}' "$BASE/admin/m/master/driver_doc.php?user_id=$D1&type=license")" "200 image/png"
check "licence copy hidden from other roles" "$(curl -s -b "$RJAR" -o /dev/null -w '%{http_code}' "$BASE/admin/m/master/driver_doc.php?user_id=$D1&type=license")" "403"
printf 'not an image' > "$TMP/fake.png"
has "fake document refused" "$(uform $D1 -F aadhaar_no= -F experience_years=7 -F 'license_classes[]=LMV-TR' -F 'license_classes[]=HMV' -F "aadhaar_doc=@$TMP/fake.png;type=image/png")" "must be a JPG, PNG, WEBP or PDF"
h=$(dapi -d action=history -d page=1)
has "driver sees own trip history" "$h" "\"trip_id\":$TRIP"
has "history shows settlement result" "$h" "You return"
has "history totals" "$h" '"all":{"trips":"1"'
has "driver opens own past trip" "$(dapi -d action=history_trip -d trip_id=$TRIP)" "R. Joshi"
OTHER=$(q "SELECT MIN(trip_id) FROM trips WHERE driver_id<>$D1 AND status='Completed'")
has "driver cannot open another driver's trip" "$(dapi -d action=history_trip -d trip_id=$OTHER)" "Trip not found"
p=$(dapi -d action=profile)
has "profile shows masked Aadhaar" "$p" "XXXX XXXX 2346"
has "profile shows licence classes" "$p" '"license_classes":\["LMV-TR","HMV"\]'
check "profile never sends full Aadhaar" "$(printf '%s' "$p" | grep -c 234123412346)" "0"
rm -f "$APP/uploads/driver_docs/$DOC"

echo "== Cancel and force-close"
has "plan trip on route 2" "$(fleet trips_new -d "trip_date=$TODAY&route_id=$R2&vehicle_id=$V1&driver_id=$D2&helper_name=&notes=&submit_trip=1")" '"success":1'
TRIP=$(q "SELECT trip_id FROM trips WHERE route_id=$R2 AND status='Planned'")
has "cancel needs reason" "$(act -d action=cancel -d reason=)" "reason"
has "cancel trip" "$(act -d action=cancel -d 'reason=Vehicle breakdown')" '"success":1'
check "cancel frees orders" "$(q "SELECT trip_id FROM deliveries WHERE delivery_id=$O4")" "0"
has "re-plan after cancel" "$(fleet trips_new -d "trip_date=$TODAY&route_id=$R2&vehicle_id=$V3&driver_id=$D2&helper_name=&notes=&submit_trip=1")" '"success":1'
TRIP=$(q "SELECT trip_id FROM trips WHERE route_id=$R2 AND status='Planned'")
has "no trip sheet before completion" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_sheet.php?trip_id=$TRIP")" "available after the trip is completed"
has "settle refused before completion" "$(act -d action=settle -d settlement_note=x)" "Only a completed trip"
has "office starts trip" "$(act -d action=start -d odometer=1000)" '"success":1'
has "office complete needs note when stops open" "$(act -d action=complete -d odometer=1010)" "return note"
has "office force-closes with note" "$(act -d action=complete -d odometer=1010 -d 'force_note=Shop closed')" '"success":1'
check "undelivered order released for re-dispatch" "$(q "SELECT trip_id FROM deliveries WHERE delivery_id=$O4")" "0"

echo "== PHP warnings from fleet code"
w=$(grep -i "fleet\|trip_\|driver/" "$APP/logs/php_errors.log" | head -5)
check "no PHP warnings" "$w" ""

echo "== Cleanup"
TRIPS=$(q "SELECT IFNULL(GROUP_CONCAT(trip_id),0) FROM trips WHERE vehicle_id IN (0$V1,0$V2,0$V3)")
for f in $(q "SELECT receipt_file FROM trip_expenses WHERE trip_id IN ($TRIPS) AND receipt_file<>''" | tr -d '\r'); do rm -f "$APP/uploads/expense/$f"; done
for f in $(q "SELECT CONCAT_WS(' ', NULLIF(license_doc,''), NULLIF(aadhaar_doc,'')) FROM users WHERE acct_username LIKE '${TAG}%'" | tr -d '\r'); do rm -f "$APP/uploads/driver_docs/$f"; done
q "DELETE FROM trip_expenses WHERE trip_id IN ($TRIPS); DELETE FROM trip_events WHERE trip_id IN ($TRIPS); DELETE FROM driver_location WHERE driver_id IN ($D1,$D2);
   DELETE FROM trips WHERE trip_id IN ($TRIPS); DELETE FROM vehicles WHERE registration_no LIKE '${TAG}%';
   DELETE FROM delivery_items WHERE delivery_id IN ($O1,$O2,$O3,$O4); DELETE FROM delivery_packages WHERE delivery_id IN ($O1,$O2,$O3,$O4);
   DELETE FROM deliveries WHERE delivery_id IN ($O1,$O2,$O3,$O4); DELETE FROM manifests WHERE manifest_id IN ($MD,$MP);
   DELETE FROM customers WHERE customer_id=$C; DELETE FROM routes WHERE route_id IN ($R1,$R2); DELETE FROM users WHERE acct_username LIKE '${TAG}%'"
check "test data removed" "$(q "SELECT COUNT(*) FROM vehicles WHERE registration_no LIKE '${TAG}%'")" "0"
rm -f "$APP/.debug"; rm -rf "$TMP"

echo; echo "Fleet test: $PASS passed, $FAIL failed"
exit $FAIL
