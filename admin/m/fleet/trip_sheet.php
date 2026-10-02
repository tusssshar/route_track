<?php
/*
 * Trip Sheet (trip closing / settlement document) of a COMPLETED trip:
 *   trip header, proof of delivery per stop (ordered / accepted / short, receiver, signature), goods reconciliation
 *   (loaded -> delivered -> returned to depot), every toll / tax / fuel / other expense, driver cash settlement, sign-offs.
 *   trip_sheet.php?trip_id=N                 printable page (Print / Save as PDF)
 *   trip_sheet.php?trip_id=N&export=csv      the same content as CSV
 *   trip_sheet.php?trip_id=N&delivery_id=M   delivery receipt (POD) of one stop, to hand to the customer
 */
include_once(__DIR__ . "/../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");
if (!fleet::has_access()) {
	exit("You do not have access to Fleet");
}

$trip_id = (int) (isset($_GET['trip_id']) ? $_GET['trip_id'] : 0);
$delivery_id = (int) (isset($_GET['delivery_id']) ? $_GET['delivery_id'] : 0);
$trip = fleet::trip($trip_id);
$money = function ($v) { return fleet::money($v); };

function sheet_error($text) {
	echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Trip Sheet</title></head><body style='font-family:Arial;padding:30px'>"
		. "<p>" . fleet::h($text) . "</p><p><a href='javascript:history.back()'>Back</a></p></body></html>";
	exit;
}
if (!$trip) {
	sheet_error("Trip not found.");
}

// Stops with their items, packages, receiver and signature
$stops = fleet::rows("SELECT D.*, M.manifest_type, C.customer_name, C.account_number, A.ship_to_name, A.address_line_1, A.address_line_2, A.city, A.state, A.zip, A.phone,
		S.server_filename AS signature_file
	FROM deliveries D
	LEFT JOIN manifests M ON M.manifest_id=D.manifest_id
	LEFT JOIN customers C ON C.customer_id=D.customer_id
	LEFT JOIN customer_addresses A ON A.address_id=D.address_id
	LEFT JOIN signature_pics S ON S.signature_pic_id=D.signature_pic_id AND D.signature_pic_id>0
	WHERE D.trip_id=" . $trip_id . ($delivery_id > 0 ? " AND D.delivery_id=" . $delivery_id : "") . "
	ORDER BY D.stop_number, D.delivery_id");
foreach ($stops as $i => $s) {
	$stops[$i]['items'] = fleet::rows("SELECT sku, product_name, uom, qty, qty_delivered FROM delivery_items WHERE delivery_id=" . (int) $s['delivery_id'] . " ORDER BY item_id");
	$stops[$i]['packages'] = fleet::rows("SELECT PT.name, DP.qty, DP.qty_delivered FROM delivery_packages DP LEFT JOIN package_types PT ON PT.package_type_id=DP.package_type_id
		WHERE DP.delivery_id=" . (int) $s['delivery_id']);
}
$closed = function ($s) { return in_array($s['status'], fleet::$CLOSED_ORDER_STATUSES); };

if ($delivery_id > 0) {
	if (count($stops) == 0) {
		sheet_error("This order is not on trip " . $trip['trip_number'] . ".");
	}
	if (!$closed($stops[0])) {
		sheet_error("The delivery receipt is available once the stop is delivered, refused or closed with an exception.");
	}
} elseif ($trip['status'] != "Completed") {
	sheet_error("The trip sheet is available after the trip is completed (trip " . $trip['trip_number'] . " is " . $trip['status'] . ").");
}

$company = array("name" => company_settings::get("company_name"), "address" => company_settings::get("company_address"), "tax_id" => company_settings::get("company_tax_id"));
$vehicle = fleet::vehicle($trip['vehicle_id']);
$driver = fleet::driver($trip['driver_id']);
$people = array();
foreach (fleet::rows("SELECT user_id, first_name, last_name FROM users WHERE user_id IN (" . (int) $trip['created_by'] . "," . (int) $trip['settled_by'] . ")") as $u) {
	$people[$u['user_id']] = fleet::driver_name($u);
}
$km = $trip['end_odometer'] !== null ? $trip['end_odometer'] - $trip['start_odometer'] : 0;
$progress = fleet::progress($trip_id);
$load = fleet::load_totals($trip_id);
$skus = fleet::load_summary($trip_id);
$packages = fleet::package_summary($trip_id);
$expenses = fleet::rows("SELECT * FROM trip_expenses WHERE trip_id=" . $trip_id . " ORDER BY expense_time, expense_id");
$exp = fleet::expense_summary($trip_id);
$settle = fleet::settlement($trip_id);
$duration = "";
if ($trip['start_time'] && $trip['end_time']) {
	$sec = strtotime($trip['end_time']) - strtotime($trip['start_time']);
	$duration = floor($sec / 3600) . "h " . floor(($sec % 3600) / 60) . "m";
}
$du = fleet::du();
$fu = fleet::fu();

//---------------------------------------------------------------------------------------------------------- CSV
if (isset($_GET['export']) && $_GET['export'] == "csv" && $delivery_id == 0) {
	header("Content-Type: text/csv; charset=utf-8");
	header("Content-Disposition: attachment; filename=trip_sheet_" . $trip['trip_number'] . ".csv");
	$out = fopen("php://output", "w");
	$row = function ($cells) use ($out) { fputcsv($out, $cells); };
	$num = function ($v) { return number_format((float) $v, 2, ".", ""); };
	$row(array("TRIP SHEET", $trip['trip_number'], $company['name'], "Settlement: " . $trip['settlement_status']));
	$row(array("Date", $trip['trip_date'], "Route", $trip['route_name']));
	$row(array("Vehicle", $trip['registration_no'] . " " . $trip['vehicle_type'], "Driver", fleet::driver_name($trip) . " " . $driver['license_no']));
	$row(array("Start", $trip['start_time'], "End", $trip['end_time'], "Duration", $duration));
	$row(array("Odometer start (" . $du . ")", $trip['start_odometer'], "Odometer end (" . $du . ")", $trip['end_odometer'], "Distance (" . $du . ")", $km));
	$row(array("Timezone", company_settings::get("timezone"), "Currency", company_settings::get("currency")));
	$row(array());
	$row(array("PROOF OF DELIVERY"));
	$row(array("Stop", "Type", "Order No", "Customer", "Status", "Received By", "Time", "SKU", "Product", "UOM", "Ordered", "Accepted", "Short / Refused", "Exception / Notes"));
	foreach ($stops as $s) {
		foreach ($s['items'] as $it) {
			$row(array($s['stop_number'], $s['manifest_type'], $s['order_number'], $s['customer_name'], $s['status'], $s['signature_text'],
				$closed($s) ? $s['delivery_time'] : "", $it['sku'], $it['product_name'], $it['uom'], $it['qty'], $it['qty_delivered'], $it['qty'] - $it['qty_delivered'],
				trim($s['exception_reason'] . " " . $s['driver_notes'])));
		}
	}
	$row(array());
	$row(array("GOODS RECONCILIATION"));
	$row(array("SKU", "Product", "UOM", "Loaded", "Delivered", "Picked Up", "Returned To Depot"));
	foreach ($skus as $s) {
		$row(array($s['sku'], $s['product_name'], $s['uom'], $s['loaded'], $s['delivered'], $s['picked_up'], $s['loaded'] - $s['delivered'] + $s['picked_up']));
	}
	$row(array("Total", "", "", $load['loaded_units'], $load['delivered_units'], $load['picked_up_units'], $load['on_board_units']));
	$row(array());
	$row(array("EXPENSES (" . company_settings::get("currency") . ")"));
	$row(array("Time", "Type", "Location", "Paid By", "Reference", "Fuel (" . $fu . ")", "Amount", "Receipt", "Entered"));
	foreach ($expenses as $e) {
		$row(array($e['expense_time'], $e['expense_type'], $e['location'], $e['payment_mode'], $e['reference_no'], $e['fuel_litres'] > 0 ? $e['fuel_litres'] + 0 : "",
			$num($e['amount']), $e['receipt_file'] != "" ? "Yes" : "No", $e['entered_by']));
	}
	$row(array("Toll", $num($exp['toll']), "Tax", $num($exp['tax']), "Fuel", $num($exp['fuel']), "Other", $num($exp['other']), "Total", $num($exp['total'])));
	$row(array());
	$row(array("DRIVER SETTLEMENT"));
	$row(array("Cash advance", $num($settle['advance'])));
	$row(array("Paid by driver (Cash/UPI)", $num($settle['driver_paid'])));
	$row(array("Paid by company (FASTag/Card/Account)", $num($settle['company_paid'])));
	$row(array("Balance", $num($settle['balance']), $settle['outcome']));
	$row(array("Status", $settle['status'], $settle['settled_at'], isset($people[$settle['settled_by']]) ? $people[$settle['settled_by']] : "", $settle['note']));
	fclose($out);
	exit;
}

//---------------------------------------------------------------------------------------------------------- HTML
$title = $delivery_id > 0 ? "Delivery Receipt " . $stops[0]['order_number'] : "Trip Sheet " . $trip['trip_number'];
$sig_url = function ($file) { return SITEROOT . "/uploads/signature/" . rawurlencode($file); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= fleet::h($title) ?></title>
<style>
	@page { size: A4; margin: 12mm; }
	* { box-sizing: border-box; }
	body { font-family: Arial, Helvetica, sans-serif; font-size: 11.5px; color: #222; margin: 0; background: #eee; }
	.sheet { background: #fff; max-width: 210mm; margin: 12px auto; padding: 14mm 12mm; position: relative; }
	.toolbar { max-width: 210mm; margin: 12px auto 0; text-align: right; }
	.toolbar a, .toolbar button { font-size: 13px; padding: 6px 12px; margin-left: 6px; border: 1px solid #337ab7; background: #337ab7; color: #fff; border-radius: 3px; text-decoration: none; cursor: pointer; }
	.toolbar a.plain { background: #fff; color: #337ab7; }
	.head { display: flex; justify-content: space-between; border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 10px; }
	.head h1 { font-size: 18px; margin: 0 0 2px; }
	.head .doc { text-align: right; }
	.head .doc h2 { font-size: 16px; margin: 0; letter-spacing: 1px; }
	.stamp { display: inline-block; border: 2px solid; padding: 2px 8px; font-weight: bold; margin-top: 4px; transform: rotate(-3deg); }
	.stamp.ok { color: #2e7d32; } .stamp.pending { color: #c77700; }
	h3 { font-size: 12.5px; background: #f0f0f0; padding: 4px 6px; margin: 14px 0 6px; text-transform: uppercase; letter-spacing: .5px; }
	table { width: 100%; border-collapse: collapse; }
	th, td { border: 1px solid #bbb; padding: 3px 5px; vertical-align: top; text-align: left; }
	th { background: #fafafa; font-weight: bold; }
	td.n, th.n { text-align: right; white-space: nowrap; }
	.grid td { border: none; padding: 2px 6px 2px 0; }
	.grid td.k { color: #666; width: 110px; }
	.stop { border: 1px solid #bbb; margin-bottom: 8px; page-break-inside: avoid; }
	.stop .top { display: flex; justify-content: space-between; background: #fafafa; padding: 4px 6px; border-bottom: 1px solid #bbb; }
	.stop table th, .stop table td { border-left: none; border-right: none; }
	.stop .ack { display: flex; justify-content: space-between; padding: 4px 6px; }
	.sig img { height: 38px; border-bottom: 1px solid #999; }
	.bad { color: #c62828; font-weight: bold; }
	.tot td { font-weight: bold; background: #fafafa; }
	.signoff { display: flex; justify-content: space-between; margin-top: 34px; }
	.signoff div { width: 30%; border-top: 1px solid #222; padding-top: 4px; text-align: center; }
	.small { font-size: 10px; color: #666; }
	@media print { body { background: #fff; } .sheet { margin: 0; padding: 0; max-width: none; } .toolbar { display: none; } }
</style>
</head>
<body>
<div class="toolbar">
  <a class="plain" href="trip_detail.php?trip_id=<?= $trip_id ?>">Back to trip</a>
  <?php if ($delivery_id == 0) { ?><a class="plain" href="trip_sheet.php?trip_id=<?= $trip_id ?>&amp;export=csv">Export CSV</a><?php } ?>
  <button onclick="window.print()">Print / Save as PDF</button>
</div>
<div class="sheet">
  <div class="head">
    <div>
      <h1><?php $logo = company_settings::logo_url(); if ($logo != "") { ?><img src="<?= fleet::h($logo) ?>" alt="" style="height:34px;vertical-align:middle;margin-right:8px"><?php } ?><?= fleet::h($company['name']) ?></h1>
      <?php if ($company['address'] != "") { ?><div><?= nl2br(fleet::h($company['address'])) ?></div><?php } ?>
      <?php if ($company['tax_id'] != "") { ?><div>Tax ID: <?= fleet::h($company['tax_id']) ?></div><?php } ?>
    </div>
    <div class="doc">
      <?php if ($delivery_id > 0) { ?>
        <h2>DELIVERY RECEIPT</h2>
        <div>Proof of Delivery &middot; Order <strong><?= fleet::h($stops[0]['order_number']) ?></strong></div>
        <div>Trip <?= fleet::h($trip['trip_number']) ?></div>
      <?php } else { ?>
        <h2>TRIP SHEET</h2>
        <div><strong><?= fleet::h($trip['trip_number']) ?></strong> &middot; <?= fleet::h($trip['trip_date']) ?></div>
        <div class="stamp <?= $settle['status'] == "Settled" ? "ok" : "pending" ?>"><?= $settle['status'] == "Settled" ? "SETTLED" : "PENDING SETTLEMENT" ?></div>
      <?php } ?>
    </div>
  </div>

  <table class="grid">
    <tr><td class="k">Route</td><td><?= fleet::h($trip['route_name']) ?></td><td class="k">Vehicle</td><td><?= fleet::h($trip['registration_no'] . " (" . $trip['vehicle_type'] . ", " . $vehicle['make_model'] . ")") ?></td></tr>
    <tr><td class="k">Driver</td><td><?= fleet::h(fleet::driver_name($trip)) ?><?= $driver['phone'] != "" ? " &middot; " . fleet::h($driver['phone']) : "" ?></td>
        <td class="k">Licence</td><td><?= fleet::h($driver['license_no']) ?><?= $driver['license_expiry'] ? " (valid till " . fleet::h($driver['license_expiry']) . ")" : "" ?></td></tr>
    <?php if ($delivery_id == 0) { ?>
    <tr><td class="k">Helper</td><td><?= fleet::h($trip['helper_name'] != "" ? $trip['helper_name'] : "-") ?></td><td class="k">Planned by</td><td><?= fleet::h(isset($people[$trip['created_by']]) ? $people[$trip['created_by']] : "-") ?></td></tr>
    <tr><td class="k">Departed</td><td><?= fleet::h($trip['start_time']) ?></td><td class="k">Returned</td><td><?= fleet::h($trip['end_time']) ?> (<?= $duration ?>)</td></tr>
    <tr><td class="k">Odometer</td><td><?= number_format((int) $trip['start_odometer']) ?> &rarr; <?= number_format((int) $trip['end_odometer']) ?> <?= fleet::h($du) ?></td>
        <td class="k">Distance</td><td><strong><?= number_format($km) ?> <?= fleet::h($du) ?></strong><?= $exp['fuel_litres'] > 0 ? " &middot; " . round($km / $exp['fuel_litres'], 2) . " " . fleet::h($du . "/" . $fu) : "" ?><?= $km > 0 ? " &middot; " . $money($exp['total'] / $km) . " per " . fleet::h($du) : "" ?></td></tr>
    <tr><td class="k">Stops</td><td><?= $progress['delivered'] ?> delivered<?= $progress['exceptions'] > 0 ? ", <span class='bad'>" . $progress['exceptions'] . " refused/exception</span>" : "" ?> of <?= $progress['total'] ?></td>
        <td class="k">Times in</td><td><?= fleet::h(company_settings::tz_label()) ?></td></tr>
    <?php if ($trip['close_note'] != "") { ?><tr><td class="k">Close note</td><td colspan="3" class="bad"><?= fleet::h($trip['close_note']) ?></td></tr><?php } ?>
    <?php } ?>
  </table>

  <h3><?= $delivery_id > 0 ? "Goods" : "Proof of delivery (stop-wise)" ?></h3>
  <?php foreach ($stops as $s) {
	$pickup = $s['manifest_type'] == "Pickup";
	$address = trim(implode(", ", array_filter(array($s['address_line_1'], $s['address_line_2'], $s['city'], $s['state'], $s['zip']))), ", ");
  ?>
  <div class="stop">
    <div class="top">
      <div><strong>Stop <?= (int) $s['stop_number'] ?> &middot; <?= fleet::h($s['customer_name']) ?></strong> (<?= fleet::h($s['account_number']) ?>)<br><span class="small"><?= fleet::h($address) ?><?= $s['phone'] != "" ? " &middot; " . fleet::h($s['phone']) : "" ?></span></div>
      <div style="text-align:right"><?= fleet::h($s['manifest_type']) ?> &middot; Order <strong><?= fleet::h($s['order_number']) ?></strong><?= $s['cust_po_num'] != "" ? "<br>PO " . fleet::h($s['cust_po_num']) : "" ?><br>
        <span class="<?= $s['status'] == "Delivered" ? "" : "bad" ?>"><?= fleet::h($s['status']) ?></span><?= $closed($s) ? " &middot; " . fleet::h($s['delivery_time']) : "" ?></div>
    </div>
    <table>
      <tr><th>SKU</th><th>Product</th><th>UOM</th><th class="n"><?= $pickup ? "To collect" : "Ordered" ?></th><th class="n"><?= $pickup ? "Collected" : "Accepted" ?></th><th class="n"><?= $pickup ? "Not collected" : "Short / Refused" ?></th></tr>
      <?php foreach ($s['items'] as $it) { $short = $it['qty'] - $it['qty_delivered']; ?>
      <tr><td><?= fleet::h($it['sku']) ?></td><td><?= fleet::h($it['product_name']) ?></td><td><?= fleet::h($it['uom']) ?></td>
        <td class="n"><?= (int) $it['qty'] ?></td><td class="n"><?= (int) $it['qty_delivered'] ?></td><td class="n <?= $short > 0 ? "bad" : "" ?>"><?= $short ?></td></tr>
      <?php } ?>
      <?php if (count($s['packages']) > 0) { ?>
      <tr><td colspan="6" class="small">Packages: <?= fleet::h(implode(", ", array_map(function ($p) { return $p['name'] . " " . (int) $p['qty_delivered'] . "/" . (int) $p['qty']; }, $s['packages']))) ?></td></tr>
      <?php } ?>
    </table>
    <div class="ack">
      <div>
        <?php if ($s['exception_reason'] != "") { ?><div class="bad">Exception: <?= fleet::h($s['exception_reason']) ?><?= $s['exception_note'] != "" ? " - " . fleet::h($s['exception_note']) : "" ?></div><?php } ?>
        <?php if ($s['driver_notes'] != "") { ?><div>Driver note: <?= fleet::h($s['driver_notes']) ?></div><?php } ?>
        <?php if ($s['delivery_notes'] != "") { ?><div class="small">Instructions: <?= fleet::h($s['delivery_notes']) ?></div><?php } ?>
        <?php if (is_numeric($s['latitude']) && is_numeric($s['longitude'])) { ?><div class="small">Closed at GPS <?= fleet::h(round($s['latitude'], 5) . ", " . round($s['longitude'], 5)) ?></div><?php } ?>
      </div>
      <div class="sig" style="text-align:right">
        <?php if ($s['status'] == "Delivered") { ?>
          <?= $pickup ? "Handed over by" : "Received in good condition by" ?>: <strong><?= fleet::h($s['signature_text'] != "" ? $s['signature_text'] : "-") ?></strong><br>
          <?php if ($s['signature_file'] && is_file(ROOTPATH . "/uploads/signature/" . $s['signature_file'])) { ?><img src="<?= fleet::h($sig_url($s['signature_file'])) ?>" alt="Signature"><?php } else { ?><span class="small">no signature captured</span><?php } ?>
        <?php } else { ?>
          <span class="small">Not accepted - goods returned to depot</span>
        <?php } ?>
      </div>
    </div>
  </div>
  <?php } ?>

  <?php if ($delivery_id > 0) { ?>
    <p class="small">Delivered by <?= fleet::h(fleet::driver_name($trip)) ?>, vehicle <?= fleet::h($trip['registration_no']) ?>. Times in <?= fleet::h(company_settings::tz_label()) ?>.
      Quantities accepted as above; shortages and refusals are returned to the depot.</p>
  <?php } else { ?>

  <h3>Goods reconciliation (depot check-in)</h3>
  <table>
    <tr><th>SKU</th><th>Product</th><th>UOM</th><th class="n">Loaded</th><th class="n">Delivered</th><th class="n">Picked up</th><th class="n">Returned to depot</th></tr>
    <?php foreach ($skus as $s) { $back = $s['loaded'] - $s['delivered'] + $s['picked_up']; ?>
    <tr><td><?= fleet::h($s['sku']) ?></td><td><?= fleet::h($s['product_name']) ?></td><td><?= fleet::h($s['uom']) ?></td><td class="n"><?= (int) $s['loaded'] ?></td>
      <td class="n"><?= (int) $s['delivered'] ?></td><td class="n"><?= (int) $s['picked_up'] ?></td><td class="n"><strong><?= $back ?></strong></td></tr>
    <?php } ?>
    <tr class="tot"><td colspan="3">Total units (<?= $load['sku_count'] ?> SKUs)</td><td class="n"><?= $load['loaded_units'] ?></td><td class="n"><?= $load['delivered_units'] ?></td><td class="n"><?= $load['picked_up_units'] ?></td><td class="n"><?= $load['on_board_units'] ?></td></tr>
    <?php if (count($packages) > 0) { ?>
    <tr><td colspan="7" class="small">Packages: <?= fleet::h(implode(", ", array_map(function ($p) { return $p['package_type'] . " loaded " . (int) $p['loaded'] . ", delivered " . (int) $p['delivered'] . ", returned " . ($p['loaded'] - $p['delivered'] + $p['picked_up']); }, $packages))) ?></td></tr>
    <?php } ?>
  </table>

  <h3>Toll, tax &amp; trip expenses (<?= fleet::h(company_settings::get("currency")) ?>)</h3>
  <table>
    <tr><th>Time</th><th>Type</th><th>Location / plaza</th><th>Paid by</th><th>Reference</th><th class="n">Amount</th><th>Receipt</th></tr>
    <?php if (count($expenses) == 0) { ?><tr><td colspan="7">No expenses recorded</td></tr><?php } ?>
    <?php foreach ($expenses as $e) { ?>
    <tr><td><?= fleet::h(substr($e['expense_time'], 5, 11)) ?></td><td><?= fleet::h($e['expense_type']) ?><?= $e['fuel_litres'] > 0 ? " (" . ($e['fuel_litres'] + 0) . " " . fleet::h($fu) . ")" : "" ?></td>
      <td><?= fleet::h($e['location']) ?></td><td><?= fleet::h($e['payment_mode']) ?></td><td><?= fleet::h($e['reference_no']) ?></td><td class="n"><?= $money($e['amount']) ?></td>
      <td><?= $e['receipt_file'] != "" ? "Attached" : (in_array($e['payment_mode'], fleet::$DRIVER_PAID_MODES) ? "<span class='bad'>Missing</span>" : "-") ?></td></tr>
    <?php } ?>
    <tr class="tot"><td colspan="5">Toll <?= $money($exp['toll']) ?> (<?= (int) $exp['toll_count'] ?> plazas) &middot; Tax <?= $money($exp['tax']) ?> &middot; Fuel <?= $money($exp['fuel']) ?> &middot; Other <?= $money($exp['other']) ?></td><td class="n"><?= $money($exp['total']) ?></td><td></td></tr>
  </table>

  <h3>Driver cash settlement</h3>
  <table style="width:60%">
    <tr><td>Cash advance given to driver</td><td class="n"><?= $money($settle['advance']) ?></td></tr>
    <tr><td>Less: paid by driver (<?= fleet::h(implode(" / ", fleet::$DRIVER_PAID_MODES)) ?>)</td><td class="n"><?= $money($settle['driver_paid']) ?></td></tr>
    <tr class="tot"><td><?= fleet::h($settle['outcome']) ?></td><td class="n"><?= $money(abs($settle['balance'])) ?></td></tr>
    <tr><td class="small">Paid directly by company (FASTag / Card / Account)</td><td class="n small"><?= $money($settle['company_paid']) ?></td></tr>
  </table>
  <?php if ($settle['status'] == "Settled") { ?>
    <p>Approved by <strong><?= fleet::h(isset($people[$settle['settled_by']]) ? $people[$settle['settled_by']] : "-") ?></strong> on <?= fleet::h($settle['settled_at']) ?><?= $settle['note'] != "" ? " &middot; Note: " . fleet::h($settle['note']) : "" ?></p>
  <?php } elseif ($settle['missing_receipts'] > 0) { ?>
    <p class="bad"><?= (int) $settle['missing_receipts'] ?> driver-paid expense(s) without receipt.</p>
  <?php } ?>

  <div class="signoff">
    <div>Driver<br><span class="small"><?= fleet::h(fleet::driver_name($trip)) ?></span></div>
    <div>Fleet / Dispatch Manager<br><span class="small"><?= fleet::h(isset($people[$settle['settled_by']]) ? $people[$settle['settled_by']] : "") ?></span></div>
    <div>Accounts</div>
  </div>
  <?php } ?>
  <p class="small" style="margin-top:14px">Generated <?= date("d M Y H:i") ?> <?= fleet::h(company_settings::tz_abbr()) ?> &middot; RouteTrac</p>
</div>
</body>
</html>
