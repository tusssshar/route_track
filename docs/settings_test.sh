#!/usr/bin/env bash
# Company settings (timezone / currency / units) and GPS timing rules.
# Needs the demo data (php database/seed_demo_data.php --yes). It changes the company timezone to New York and back,
# checks every stored time survives the round trip unchanged, and removes the GPS points it adds.
# Usage: bash docs/settings_test.sh      (exit code = number of failed checks)

BASE=${BASE:-http://localhost/projects/routetrac}
MYSQL=${MYSQL:-/c/xampp/mysql/bin/mysql}
DB=${DB:-RouteTrac_template_repository}
APP=$(cd "$(dirname "$0")/.." && pwd)
TMP=$(mktemp -d); command -v cygpath >/dev/null && TMP=$(cygpath -m "$TMP")   # native curl.exe needs a Windows path for -F @file
JAR=$TMP/admin.txt; PJAR=$TMP/priya.txt; DJAR=$TMP/driver.txt
FAIL=0; PASS=0
q()     { "$MYSQL" -uroot -N "$DB" -e "$1" | tr -d '\r'; }
ok()    { PASS=$((PASS+1)); printf "  PASS  %s\n" "$1"; }
bad()   { FAIL=$((FAIL+1)); printf "  FAIL  %s  -- %s\n" "$1" "$2"; }
check() { if [ "$2" = "$3" ]; then ok "$1"; else bad "$1" "expected [$3] got [$2]"; fi; }
has()   { if printf '%s' "$2" | grep -q -- "$3"; then ok "$1"; else bad "$1" "missing [$3] in [$(printf '%s' "$2" | head -c 200)]"; fi; }
api()   { curl -s -X POST "$BASE/webservices/$1.php" -d "app_key=123" "${@:2}"; }
save()  { curl -s -b "$JAR" -o /dev/null -w '%{http_code}' -X POST "$BASE/admin/m/master/company_settings.php" -d save_settings=1 "$@"; }
# fingerprint of every DATETIME value in the DB (company_settings excluded: saving writes updated_at)
times() { "$MYSQL" -uroot -N "$DB" -e "SET SESSION group_concat_max_len=100000000; $(q "SELECT GROUP_CONCAT(CONCAT('SELECT ''',table_name,'.',column_name,''', MD5(IFNULL(GROUP_CONCAT(\`',column_name,'\` ORDER BY \`',column_name,'\`),'''')) FROM \`',table_name,'\`') SEPARATOR ' UNION ALL ') FROM information_schema.columns WHERE table_schema='$DB' AND data_type='datetime' AND table_name<>'company_settings'")" | md5sum | cut -c1-32; }

# Git Bash on Windows does not know zone names: use POSIX rules (IST = UTC+5:30, New York = EST/EDT)
IST='IST-5:30'; NYC='EST5EDT,M3.2.0,M11.1.0'
MARK=19.0000   # test GPS points use this latitude prefix and are deleted at the end
touch "$APP/.debug"; : > "$APP/logs/php_errors.log"
SETTINGS_BEFORE=$(q "SELECT GROUP_CONCAT(CONCAT(setting_key,'=',setting_value,'|',updated_by,'|',IFNULL(updated_at,'')) ORDER BY setting_key) FROM company_settings")
# the trip that has been running longest (and its driver), so a reading from 10 minutes ago falls inside it
read TRIP DRV <<< "$(q "SELECT trip_id, driver_id FROM trips WHERE status='In Transit' ORDER BY start_time LIMIT 1")"
[ -z "$TRIP" ] && { echo "Needs the demo data with a trip In Transit"; exit 1; }

echo "== Access and defaults"
curl -s -c "$JAR" -o /dev/null -d 'al_username=admin&al_password=12345&submit=SIGN+IN' $BASE/index.php
curl -s -c "$PJAR" -o /dev/null -d 'al_username=priya&al_password=12345&submit=SIGN+IN' $BASE/index.php
has "admin sees settings page" "$(curl -s -b "$JAR" $BASE/admin/m/master/company_settings.php)" "Company time now"
has "menu link for admin" "$(curl -s -b "$JAR" $BASE/admin/home.php)" "Company Settings"
curl -s -b "$PJAR" -o /dev/null -X POST $BASE/admin/m/master/company_settings.php -d save_settings=1 -d timezone=UTC
check "delivery manager cannot change settings" "$(q "SELECT setting_value FROM company_settings WHERE setting_key='timezone'")" "Asia/Kolkata"
p=$(curl -s -b "$JAR" $BASE/admin/m/fleet/trips.php)
has "amounts in rupees" "$p" "₹"
has "timezone shown" "$p" "Asia/Kolkata (UTC+05:30)"

echo "== Locked settings"
save -d timezone=Asia/Kolkata -d currency=USD -d distance_unit=km -d fuel_unit=L > /dev/null
check "currency locked once expenses exist" "$(q "SELECT setting_value FROM company_settings WHERE setting_key='currency'")" "INR"
has "currency lock explained" "$(curl -s -b "$JAR" $BASE/admin/m/master/company_settings.php)" "expenses are already recorded in INR"
save -d timezone=Asia/Kolkata -d currency=INR -d distance_unit=mi -d fuel_unit=gal > /dev/null
check "units locked once readings exist" "$(q "SELECT CONCAT(MAX(IF(setting_key='distance_unit',setting_value,'')),MAX(IF(setting_key='fuel_unit',setting_value,''))) FROM company_settings")" "kmL"
save -d timezone=Mars/Base > /dev/null
check "invalid timezone refused" "$(q "SELECT setting_value FROM company_settings WHERE setting_key='timezone'")" "Asia/Kolkata"

echo "== GPS timing rules"
NOW=$(date +%s)
r=$(api get_driver_current_location -d "driver_id=$DRV&latitude=${MARK}001&longitude=72.9&recorded_at=$(( (NOW-600)*1000 ))&accuracy=8")
has "reading from 10 min ago accepted and tagged" "$r" "\"trip_id\":\"$TRIP\""
check "stored with the time it was taken" "$(q "SELECT add_date_time FROM driver_location WHERE latitude='${MARK}001'")" "$(TZ=$IST date -d @$((NOW-600)) '+%F %T')"
check "receive time kept separately" "$(q "SELECT received_at > add_date_time FROM driver_location WHERE latitude='${MARK}001'")" "1"
has "future reading refused" "$(api get_driver_current_location -d "driver_id=$DRV&latitude=${MARK}002&longitude=72.9&recorded_at=$((NOW+3600))")" "future"
has "2-day-old reading refused" "$(api get_driver_current_location -d "driver_id=$DRV&latitude=${MARK}003&longitude=72.9&recorded_at=$((NOW-172800))")" "older than 24 hours"
has "0,0 reading refused" "$(api get_driver_current_location -d "driver_id=$DRV&latitude=0&longitude=0")" "invalid coordinates"
POS=$(q "SELECT CONCAT(latitude,',',longitude) FROM users WHERE user_id=$DRV")
api get_driver_current_location -d "driver_id=$DRV&latitude=${MARK}004&longitude=72.9&recorded_at=$((NOW-3000))" > /dev/null
check "late offline reading does not move current position" "$(q "SELECT CONCAT(latitude,',',longitude) FROM users WHERE user_id=$DRV")" "$POS"
B="[{\"lat\":${MARK}005,\"lng\":72.9,\"t\":$(( (NOW-120)*1000 )),\"acc\":15},{\"lat\":${MARK}006,\"lng\":72.9,\"t\":$(( (NOW-60)*1000 )),\"acc\":500},{\"lat\":999,\"lng\":72.9,\"t\":$((NOW*1000))}]"
r=$(api get_driver_current_location -d "driver_id=$DRV" --data-urlencode "points=$B")
has "offline batch: 2 accepted" "$r" '"accepted":2'
has "offline batch: 1 refused" "$r" '"rejected":1'
check "accuracy stored" "$(q "SELECT accuracy_m FROM driver_location WHERE latitude='${MARK}006'")" "500"
has "inaccurate point left off the map" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_detail.php?trip_id=$TRIP")" "${MARK}005"
check "inaccurate point not drawn" "$(curl -s -b "$JAR" "$BASE/admin/m/fleet/trip_detail.php?trip_id=$TRIP" | grep -c "${MARK}006")" "0"
U=$(q "SELECT acct_username FROM users WHERE user_id=$DRV")
curl -s -c "$DJAR" -o /dev/null -d action=login -d username=$U -d password=12345 $BASE/driver/api.php
r=$(curl -s -b "$DJAR" -X POST $BASE/driver/api.php -d action=location --data-urlencode "points=[{\"lat\":${MARK}007,\"lng\":72.9,\"t\":$((NOW*1000)),\"acc\":9}]")
has "driver page batch accepted" "$r" '"accepted":1'
has "driver page shows company time" "$r" 'IST'

echo "== Timezone change converts stored times (and back)"
FP=$(times)
START=$(q "SELECT start_time FROM trips WHERE trip_id=$TRIP"); TDATE=$(q "SELECT trip_date FROM trips WHERE trip_id=$TRIP")
check "change to New York" "$(save -d timezone=America/New_York -d convert_times=1)" "302"
check "trip start shifted IST -> EDT (-9:30)" "$(q "SELECT start_time FROM trips WHERE trip_id=$TRIP")" "$(date -u -d @$(( $(date -u -d "$START" +%s) - 34200 )) '+%F %T')"
check "trip date (business date) unchanged" "$(q "SELECT trip_date FROM trips WHERE trip_id=$TRIP")" "$TDATE"
has "pages show New York time" "$(curl -s -b "$JAR" $BASE/admin/m/fleet/trips.php)" "America/New_York (UTC-04:00)"
api get_driver_current_location -d "driver_id=$DRV&latitude=${MARK}008&longitude=72.9" > /dev/null
d=$(( $(date -u -d "$(q "SELECT add_date_time FROM driver_location WHERE latitude='${MARK}008'")" +%s) - $(date -u -d "$(TZ=$NYC date '+%F %T')" +%s) ))
check "new readings stored in New York time" "$([ ${d#-} -lt 120 ] && echo yes || echo "off by ${d}s")" "yes"
q "DELETE FROM driver_location WHERE latitude='${MARK}008'"
check "change back to India" "$(save -d timezone=Asia/Kolkata -d convert_times=1)" "302"
check "every stored time restored exactly" "$(times)" "$FP"
has "change recorded in settings history" "$(curl -s -b "$JAR" $BASE/admin/m/master/company_settings.php)" "timezone = Asia/Kolkata"

echo "== Branding and theme"
"${PHP:-/c/xampp/php/php.exe}" -r '$i=imagecreatetruecolor(120,40); imagefill($i,0,0,imagecolorallocate($i,20,120,200)); imagepng($i, $argv[1]);' "$TMP/logo.png"
printf '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>' > "$TMP/evil.png"
code=$(curl -s -b "$JAR" -o /dev/null -w '%{http_code}' -X POST "$BASE/admin/m/master/company_settings.php" -F save_settings=1 -F tab=branding -F "system_name=Speedy Freight" \
  -F "system_title=Pune Transport Co" -F theme=night_drive -F primary_color=#ff5500 -F accent_color= -F font=Inter -F "logo=@$TMP/logo.png;type=image/png")
check "branding saved" "$code" "302"
LOGO=$(q "SELECT setting_value FROM company_settings WHERE setting_key='logo_file'")
check "logo stored" "$([ -f "$APP/uploads/branding/$LOGO" ] && echo yes)" "yes"
p=$(curl -s -b "$JAR" $BASE/admin/m/fleet/trips.php)
has "system name in top bar" "$p" "Speedy Freight"
has "page title uses system name" "$p" "<title>Speedy Freight | Pune Transport Co</title>"
has "dark theme applied" "$p" 'class="rt-dark"'
has "custom primary colour applied" "$p" "--rt-primary:#ff5500"
has "font applied" "$p" "family=Inter"
has "logo in top bar" "$p" "uploads/branding/$LOGO"
has "login page branded" "$(curl -s $BASE/index.php)" "Speedy Freight"
has "driver page branded" "$(curl -s $BASE/driver/index.php)" "Speedy Freight"
has "footer uses system name" "$p" "Speedy Freight. All rights reserved"
curl -s -b "$JAR" -o /dev/null -X POST "$BASE/admin/m/master/company_settings.php" -F save_settings=1 -F primary_color=orange
check "invalid colour refused" "$(q "SELECT setting_value FROM company_settings WHERE setting_key='primary_color'")" "#ff5500"
r=$(curl -s -b "$JAR" -X POST "$BASE/admin/m/master/company_settings.php" -F save_settings=1 -F "logo=@$TMP/evil.png;type=image/png")
has "disguised SVG logo refused" "$r" "Logo must be a PNG, JPG or WEBP image"
check "logo unchanged after refused upload" "$(q "SELECT setting_value FROM company_settings WHERE setting_key='logo_file'")" "$LOGO"
curl -s -b "$JAR" -o /dev/null -X POST "$BASE/admin/m/master/company_settings.php" -F save_settings=1 -F remove_logo=1
check "remove logo" "$(q "SELECT setting_value FROM company_settings WHERE setting_key='logo_file'")" ""
check "old logo file deleted" "$([ -f "$APP/uploads/branding/$LOGO" ] && echo still-there || echo gone)" "gone"

echo "== Cleanup"
q "DELETE FROM driver_location WHERE latitude LIKE '${MARK}%'"
q "UPDATE users SET latitude='$(echo $POS | cut -d, -f1)', longitude='$(echo $POS | cut -d, -f2)' WHERE user_id=$DRV"
echo "$SETTINGS_BEFORE" | tr ',' '\n' | while IFS= read -r kv; do	# one setting per line (values and times contain spaces)
  k=${kv%%=*}; rest=${kv#*=}; v=${rest%%|*}; rest=${rest#*|}; by=${rest%%|*}; at=${rest#*|}
  q "UPDATE company_settings SET setting_value='$v', updated_by=$by, updated_at=$([ -z "$at" ] && echo NULL || echo "'$at'") WHERE setting_key='$k'"
done
KEYS=$(echo "$SETTINGS_BEFORE" | tr ',' '
' | cut -d= -f1 | sed "s/.*/'&'/" | paste -sd,)
q "DELETE FROM company_settings WHERE setting_key NOT IN ($KEYS)"	# rows the test created
check "settings restored" "$(q "SELECT GROUP_CONCAT(CONCAT(setting_key,'=',setting_value,'|',updated_by,'|',IFNULL(updated_at,'')) ORDER BY setting_key) FROM company_settings")" "$SETTINGS_BEFORE"
check "no PHP warnings from new code" "$(grep -i "company_settings\|fleet.php\|driver/\|get_driver_current" "$APP/logs/php_errors.log" | head -3)" ""
rm -f "$APP/.debug"; rm -rf "$TMP"
echo; echo "Settings test: $PASS passed, $FAIL failed"
exit $FAIL
