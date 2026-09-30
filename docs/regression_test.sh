#!/usr/bin/env bash
# RouteTrac end-to-end regression (Git Bash / Linux).
# Requires: app at $BASE, MariaDB client at $MYSQL, admin login admin/12345, PHP CLI at $PHP.
# It WRITES test data (areas, routes, customers, manifests, deliveries, uploads): run it on a local/test copy only.
# Usage: bash docs/regression_test.sh      (exit code = number of failed checks)

BASE=${BASE:-http://localhost/projects/routetrac}
MYSQL=${MYSQL:-/c/xampp/mysql/bin/mysql}
PHP=${PHP:-/c/xampp/php/php.exe}
DB=${DB:-RouteTrac_template_repository}
APP=$(cd "$(dirname "$0")/.." && pwd)
TMP=$(mktemp -d); command -v cygpath >/dev/null && TMP=$(cygpath -m "$TMP")   # native curl.exe needs a Windows path for -F @file
JAR=$TMP/cookies.txt
TODAY=$(date +%F)
TAG=RT$(date +%H%M%S)
FAIL=0; PASS=0

q()     { "$MYSQL" -uroot -N "$DB" -e "$1"; }
ok()    { PASS=$((PASS+1)); printf "  PASS  %s\n" "$1"; }
bad()   { FAIL=$((FAIL+1)); printf "  FAIL  %s  -- %s\n" "$1" "$2"; }
check() { if [ "$2" = "$3" ]; then ok "$1"; else bad "$1" "expected [$3] got [$2]"; fi; }
has()   { if printf '%s' "$2" | grep -q -- "$3"; then ok "$1"; else bad "$1" "missing [$3] in [$(printf '%s' "$2" | head -c 160)]"; fi; }
post()  { curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/$1" "${@:2}"; }
code()  { curl -s -b "$JAR" -o /dev/null -w "%{http_code}" "$BASE/$1"; }
api()   { curl -s -X POST "$BASE/webservices/$1.php" -d "app_key=123" "${@:2}"; }

touch "$APP/.debug"; mkdir -p "$APP/logs"; : > "$APP/logs/php_errors.log"

echo "== Security"
check "admin page redirects when logged out" "$(curl -s -o /dev/null -w '%{http_code}' $BASE/admin/m/master/ajax/users.php)" "302"
check "admin SQL-injection login rejected" "$(curl -s -o /dev/null -w '%{http_code}' -d 'al_password=x&submit=1' --data-urlencode "al_username=admin'#" $BASE/index.php)" "200"
has   "mobile SQL-injection login rejected" "$(api login_user -d 'acct_password=x' --data-urlencode "acct_username=admin') -- ")" '"msg":"Failed"'
has   "office API key injection rejected" "$(curl -s -X POST $BASE/webservice-dipali/search_area.php --data-urlencode "api_key=' OR '1'='1" -d area_name=a)" 'invalid'
check "docs folder hidden" "$(curl -s -o /dev/null -w '%{http_code}' $BASE/docs/)" "404"

echo "== Login"
check "admin login" "$(curl -s -c "$JAR" -o /dev/null -w '%{http_code}' -d 'al_username=admin&al_password=12345&submit=SIGN+IN' $BASE/index.php)" "302"

echo "== Every admin page / endpoint loads"
for f in $(cd "$APP" && find admin -name "*.php" | grep -vE "_BK_|templates|logout|authorized|_2017|_19_3_19|skills|points_consumption|manage_master|index1|admin/test.php|includes/" | sort); do
  c=$(code "$f"); [ "$c" = "200" ] && ok "$f" || bad "$f" "HTTP $c"
done

echo "== Master data CRUD"
has "area create"     "$(post admin/m/master/ajax/area_new.php -d "submit=1&is_default=0" --data-urlencode "area_name=$TAG O'Area")" '"success":1'
AID=$(q "select max(area_id) from areas")
has "area duplicate check" "$(post "admin/ajax/admin_ajax_action.php?action=check_area" --data-urlencode "area_name=$TAG O'Area")" 'false'
has "route create"    "$(post admin/m/master/ajax/routes_new.php -d "submit=1&name=$TAG Route&area_id=$AID")" '"success":1'
RID=$(q "select max(route_id) from routes")
has "package create"  "$(post admin/m/master/ajax/packages-new.php -d "submit=1&name=$TAG Box")" '"success":1'
PID=$(q "select max(package_type_id) from package_types")
has "customer create" "$(post admin/m/master/ajax/customer_new.php -d "submit=1&cust_name=$TAG Cust&cust_act_no=$TAG")" '"success":1'
CID=$(q "select max(customer_id) from customers")
has "address create"  "$(post admin/m/master/ajax/customer_address_new.php -d "submit=1&cust_name=$CID&ship_to_name=&address_line_1=1 Main&city=Austin&state=TX&zip=73301&phone=5125550100")" '"success":1'
ADID=$(q "select max(address_id) from customer_addresses")
has "address needs customer" "$(post admin/m/master/ajax/customer_address_new.php -d "submit=1&cust_name=0&address_line_1=x")" 'Please select customer'
has "user create (API access)" "$(post admin/m/master/ajax/users_new.php -d "submit=1&first_name=QA&last_name=Driver&email_addr=$TAG@example.com&status=1&access_level=2&is_driver=1&default_route_id=$RID&acct_username=$TAG&acct_password=pass123&is_api_access=1")" '"success":1'
UID_=$(q "select max(user_id) from users")
check "user got an API key" "$(q "select length(api_key) from users where user_id=$UID_")" "32"
for s in area customer users manifests routes packages customer_address; do
  c=$(curl -s -b "$JAR" -o /dev/null -w '%{http_code}' --data-urlencode "txtSearch=O'Brien" "$BASE/admin/m/master/ajax/$s.php"); check "search with apostrophe: $s" "$c" "200"
done

echo "== Manifest create / edit / delete"
R=$(post admin/m/master/ajax/manifests_new.php -d "manifest_type=Delivery&hidden_delivery_id=0&hidden_manifest_id=0&route_id=$RID&driver_id=$UID_&customer_id=$CID&address_id=$ADID&stop_number=1&order_number=$TAG-1&scheduled_delivery=$TODAY&package_id[]=$PID&package_qty[]=2&sku[]=$TAG-SKU&qty[]=3&product_name[]=Widget&description[]=&uom[]=EA&submit=x")
has "manifest created" "$R" '"success":1'
MID=$(printf '%s' "$R" | sed -E 's/.*"manifest_id":([0-9]+).*/\1/')
DID=$(q "select delivery_id from deliveries where manifest_id=$MID")
check "manifest totals" "$(q "select concat(num_deliveries,'/',num_items,'/',num_packages) from manifests where manifest_id=$MID")" "1/3/2"
has "delivery without packages added" "$(post admin/m/master/ajax/manifests_new.php -d "manifest_type=Delivery&hidden_delivery_id=0&hidden_manifest_id=$MID&route_id=$RID&driver_id=$UID_&customer_id=$CID&address_id=$ADID&order_number=$TAG-2&scheduled_delivery=$TODAY&sku[]=X&qty[]=1&product_name[]=X&description[]=&uom[]=EA&submit=x")" '"success":1'
DID2=$(q "select delivery_id from deliveries where manifest_id=$MID and order_number='$TAG-2'")
has "edit: remove all items" "$(post admin/m/master/ajax/manifests_new.php -d "temp_delivery_id=$DID2&manifest_type=Delivery&hidden_delivery_id=$DID2&hidden_manifest_id=$MID&delivery_id=$DID2&route_id=$RID&driver_id=$UID_&customer_id=$CID&address_id=$ADID&order_number=$TAG-2&scheduled_delivery=$TODAY&submit=x")" '"success":1'
check "edit removed items" "$(q "select count(*) from delivery_items where delivery_id=$DID2")" "0"
code "admin/m/master/ajax/manifests_new.php?delete_id=$DID2&manifest_id=$MID" >/dev/null
check "totals after delete" "$(q "select concat(num_deliveries,'/',num_items) from manifests where manifest_id=$MID")" "1/3"

echo "== Manifest CSV import"
printf 'Order_No,Account_No,Customer,Address_Line_1,Address_Line_2,Address_Line_3,City,State,ZIP,Phone,Package_Type,Package_Qty,SKU,Qty,Item_Name,Desc,UOM\n%s-3,%s,x,1 Main,,,Austin,TX,73301,1,%s Box,1,S1,2,A,,EA\n%s-3,%s,x,1 Main,,,Austin,TX,73301,1,%s Box,1,S2,1,B,,EA\n' $TAG $TAG $TAG $TAG $TAG $TAG > $TMP/m.csv
code "admin/m/master/ajax/manifests_new.php?manifest_id=$MID" >/dev/null
curl -s -b "$JAR" -o /dev/null -F submit1=1 -F "import_file_name=@$TMP/m.csv;filename=m.csv" -F import_route_id=$RID -F import_driver_id=$UID_ -F import_scheduled_delivery=$TODAY -F import_manifest_type=Delivery "$BASE/admin/m/master/create_manifest.php"
check "import grouped rows into one delivery" "$(q "select count(*) from deliveries where manifest_id=$MID and order_number='$TAG-3' and address_id=$ADID and route_id=$RID")" "1"
check "import totals" "$(q "select concat(num_deliveries,'/',num_items) from manifests where manifest_id=$MID")" "2/6"

echo "== CSV exports"
for e in area route package customer users customer_address; do
  has "export $e" "$(curl -s -b "$JAR" "$BASE/admin/m/master/export.php?value=export_$e" | head -1)" "Name"
done

echo "== Reports"
for r in "delivery_status.php" "orders_report.php" "route_status.php"; do
  check "report $r" "$(curl -s -b "$JAR" -o /dev/null -w '%{http_code}' --data-urlencode "customer_name=O'Brien" "$BASE/admin/m/reports/ajax/$r")" "200"
done
check "dashboard route status" "$(code "admin/ajax/home_route_status.php?route_id=$RID")" "200"
check "detail report" "$(code "admin/m/master/ajax/detail_report.php?manifest_id=$MID")" "200"

echo "== Mobile API"
has "login"            "$(api login_user -d "acct_username=$TAG&acct_password=pass123&imei_no=QA")" '"msg":"Success"'
has "route_list"       "$(api route_list -d "driver_id=$UID_")" "$TAG Route"
has "route_accept"     "$(api route_accept -d "driver_id=$UID_&route_id=$RID&abendon_status=NO")" 'accepted'
has "customer_list"    "$(api customer_list -d "driver_id=$UID_&route_id=$RID")" "$TAG Cust"
has "order_details"    "$(api order_details -d "delivery_id=$DID")" "$TAG-SKU"
has "check_upc_code"   "$(api check_upc_code -d "upc_code=$TAG-SKU&delivery_id=$DID")" '"upc_status":"YES"'
has "driver location"  "$(api get_driver_current_location -d "driver_id=$UID_&latitude=30.1&longitude=-97.7")" 'Success'
$PHP -r '$i=imagecreatetruecolor(8,8); ob_start(); imagejpeg($i); echo base64_encode(ob_get_clean());' > $TMP/img.b64
has "upload_signature" "$(api upload_signature -d "delivery_id=$DID&signature_text=QA" --data-urlencode "server_filename=$(cat $TMP/img.b64)" --data-urlencode "image1=$(cat $TMP/img.b64)")" 'uploaded successfully'
check "delivery marked Delivered" "$(q "select status from deliveries where delivery_id=$DID")" "Delivered"
check "items marked delivered"    "$(q "select qty_delivered from delivery_items where delivery_id=$DID")" "3"
F=$(q "select server_filename from signature_pics order by signature_pic_id desc limit 1")
check "signature file served" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/uploads/signature/${F// /%20}")" "200"
has "order_summary"    "$(api order_summary -d "customer_id=$CID&driver_id=$UID_")" "$TAG-1"

echo "== Office API (webservice-dipali)"
K=$(q "select api_key from users where user_id=$UID_")
has "search_customer"  "$(curl -s -X POST $BASE/webservice-dipali/search_customer.php -d "api_key=$K&customer_name=$TAG")" '"status":1'
has "create area"      "$(curl -s -X POST $BASE/webservice-dipali/create_new_area.php -d "api_key=$K&area_id=0&area_name=$TAG API&is_default=0")" '"status":1'
AID2=$(q "select area_id from areas where name='$TAG API'")
has "delete area"      "$(curl -s -X POST $BASE/webservice-dipali/delete_area.php -d "api_key=$K&area_id=$AID2")" '"status":1'

echo "== Cleanup deletes"
for d in "customer_address.php?delete_id=$ADID" "users.php?delete_id=$UID_" "customer.php?delete_id=$CID" "packages.php?delete_id=$PID" "routes.php?delete_id=$RID" "area.php?delete_id=$AID"; do code "admin/m/master/ajax/$d" >/dev/null; done
check "deletes removed rows" "$(q "select (select count(*) from areas where area_id=$AID)+(select count(*) from customers where customer_id=$CID)+(select count(*) from users where user_id=$UID_)")" "0"

echo "== PHP fatal errors during run"
N=$(grep -cE "Fatal|Uncaught" "$APP/logs/php_errors.log"); check "no PHP fatals" "$N" "0"
[ "$N" != "0" ] && grep -E "Fatal|Uncaught" "$APP/logs/php_errors.log" | head -5
rm -f "$APP/.debug"; rm -rf "$TMP"

echo; echo "PASSED $PASS   FAILED $FAIL"
exit $FAIL
