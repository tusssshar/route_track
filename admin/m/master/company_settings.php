<?php
	include_once("../../../bootstrap.php");
	include(ROOTPATH . "/admin/authorized.php");

	// Administrator only: these settings change how the whole system looks and how every date, amount and distance is read
	$is_admin = (int) $_SESSION["routetrac-admin"]["access_level"] === 1;
	$errors = array();
	$messages = array();
	$tab = isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], array("branding", "company", "regional")) ? $_REQUEST['tab'] : "branding";
	if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
		unset($_POST['__logo_file']);	// only set from a real upload below
		list($logo, $logo_error) = company_settings::save_logo("logo");
		if ($logo_error != "") {
			$errors[] = $logo_error;
		} else {
			if ($logo != "") {
				$_POST['__logo_file'] = $logo;
			}
			list($errors, $messages) = company_settings::save($_POST, $_SESSION["routetrac-admin"]["al_id"], isset($_POST['convert_times']));
			if (count($errors) > 0 && $logo != "") {
				@unlink(ROOTPATH . "/uploads/branding/" . $logo);	// not saved: drop the new upload
			}
		}
		if (count($errors) == 0) {
			$_SESSION["routetrac-admin"]["settings_msg"] = count($messages) > 0 ? implode(". ", $messages) : "No changes";
			header("Location: company_settings.php?tab=" . $tab);
			exit;
		}
	}

	include_once(ROOTPATH . "/admin/templates/admin_header_start.php");
	include_once(ROOTPATH . "/admin/templates/admin_header_end.php");
	if (!$is_admin) {
		echo "<script>window.location.assign('" . SITEROOT . "/admin/home.php')</script>";
		exit;
	}
	$objGlobalConnection1 = new dbSqlConnection();
	$oGlobalConnection = $objGlobalConnection1->Connect(HST, USR, PWD, DBN);

	$s = company_settings::load(true);
	if (count($errors) > 0) {	// keep what was typed
		foreach ($s as $key => $value) {
			if (isset($_POST[$key]) && !is_array($_POST[$key]) && $key != "logo_file") {
				$s[$key] = $_POST[$key];
			}
		}
	}
	$flash = isset($_SESSION["routetrac-admin"]["settings_msg"]) ? $_SESSION["routetrac-admin"]["settings_msg"] : "";
	unset($_SESSION["routetrac-admin"]["settings_msg"]);
	$zones = array();
	foreach (DateTimeZone::listIdentifiers() as $zone) {
		$region = strpos($zone, "/") !== false ? substr($zone, 0, strpos($zone, "/")) : "Other";
		$zones[$region][] = $zone;
	}
	$history = fleet::rows("SELECT S.setting_key, S.setting_value, S.updated_at, U.first_name, U.last_name FROM company_settings S
		LEFT JOIN users U ON U.user_id=S.updated_by WHERE S.updated_at IS NOT NULL ORDER BY S.updated_at DESC LIMIT 12");
	$logo_url = company_settings::logo_url();
	// Theme colours for the live preview (with readable text colours worked out)
	$themes_js = array();
	foreach (company_settings::$THEMES as $key => $t) {
		$t['font'] = $s['font'];
		$themes_js[$key] = array("vars" => company_settings::css_vars($t), "dark" => !empty($t['dark']), "primary" => $t['primary'], "accent" => $t['accent']);
	}
	$current = company_settings::theme();
	$h = function ($v) { return fleet::h($v); };
?>
<div class="container-fluid detail-page">
  <div class="page-header"><h4><span class="section-heading">Company Settings</span></h4></div>
  <?php if ($flash != "") { ?><div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= $h($flash) ?></div><?php } ?>
  <?php if (count($errors) > 0) { ?><div class="alert alert-danger"><?= implode("<br>", array_map(array('fleet', 'h'), $errors)) ?></div><?php } ?>

  <form method="post" enctype="multipart/form-data" class="form-horizontal" id="settingsForm" onsubmit="return confirmChange();">
    <input type="hidden" name="tab" id="tabField" value="<?= $h($tab) ?>">
    <ul class="nav nav-tabs" style="margin-bottom:18px">
      <li class="<?= $tab == "branding" ? "active" : "" ?>"><a data-toggle="tab" href="#branding" data-tab="branding"><i class="fa fa-paint-brush"></i> Branding &amp; Theme</a></li>
      <li class="<?= $tab == "company" ? "active" : "" ?>"><a data-toggle="tab" href="#company" data-tab="company"><i class="fa fa-building-o"></i> Company</a></li>
      <li class="<?= $tab == "regional" ? "active" : "" ?>"><a data-toggle="tab" href="#regional" data-tab="regional"><i class="fa fa-globe"></i> Regional</a></li>
    </ul>

    <div class="tab-content">
      <!-- ============================================================ branding & theme -->
      <div id="branding" class="tab-pane fade <?= $tab == "branding" ? "in active" : "" ?>">
        <div class="row">
          <div class="col-md-7">
            <div class="panel panel-info">
              <div class="panel-heading">System identity</div>
              <div class="panel-body">
                <div class="form-group">
                  <label for="system_name" class="col-sm-3 control-label">System name</label>
                  <div class="col-sm-9"><input type="text" name="system_name" id="system_name" class="form-control" maxlength="255" value="<?= $h($s['system_name']) ?>" oninput="$('.rt-brand-name').text(this.value)"></div>
                </div>
                <div class="form-group">
                  <label for="system_title" class="col-sm-3 control-label">System title</label>
                  <div class="col-sm-9"><input type="text" name="system_title" id="system_title" class="form-control" maxlength="255" value="<?= $h($s['system_title']) ?>" oninput="$('.rt-brand-title').text(this.value)">
                    <p class="help-block">Shown under the name and in the browser tab, e.g. "Fleet &amp; Delivery Management".</p></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label">Logo</label>
                  <div class="col-sm-9">
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                      <?php if ($logo_url != "") { ?><img src="<?= $h($logo_url) ?>" class="rt-logo-preview" id="logoPreview" alt="Logo">
                      <?php } else { ?><span class="rt-logo rt-logo-mark" style="width:56px;height:56px;font-size:24px;border-radius:14px" id="logoMark"><i class="fa fa-truck"></i></span><img class="rt-logo-preview" id="logoPreview" alt="" style="display:none"><?php } ?>
                      <div>
                        <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/webp" class="form-control" style="padding:5px;height:auto" onchange="previewLogo(this)">
                        <?php if ($logo_url != "") { ?><div class="checkbox"><label><input type="checkbox" name="remove_logo" value="1"> Remove logo (use the truck icon)</label></div><?php } ?>
                      </div>
                    </div>
                    <p class="help-block">PNG, JPG or WEBP up to 1 MB; a wide logo on a transparent background looks best. Also used as the browser tab icon and on trip sheets.</p>
                  </div>
                </div>
                <div class="form-group">
                  <label for="footer_text" class="col-sm-3 control-label">Footer</label>
                  <div class="col-sm-9"><input type="text" name="footer_text" id="footer_text" class="form-control" maxlength="255" value="<?= $h($s['footer_text']) ?>" placeholder="<?= $h("© {year} " . $s['system_name'] . ". All rights reserved.") ?>">
                    <p class="help-block">Leave empty for the default. <code>{year}</code> becomes the current year.</p></div>
                </div>
                <div class="form-group">
                  <label for="support_phone" class="col-sm-3 control-label">Support</label>
                  <div class="col-sm-4"><input type="text" name="support_phone" id="support_phone" class="form-control" maxlength="255" value="<?= $h($s['support_phone']) ?>" placeholder="Phone"></div>
                  <div class="col-sm-5"><input type="email" name="support_email" id="support_email" class="form-control" maxlength="255" value="<?= $h($s['support_email']) ?>" placeholder="Email"></div>
                </div>
              </div>
            </div>

            <div class="panel panel-info">
              <div class="panel-heading">Theme</div>
              <div class="panel-body">
                <div class="rt-swatches">
                  <?php foreach (company_settings::$THEMES as $key => $t) { ?>
                  <label class="rt-swatch <?= $key == $s['theme'] ? "selected" : "" ?>" data-theme="<?= $h($key) ?>">
                    <input type="radio" name="theme" value="<?= $h($key) ?>" <?= $key == $s['theme'] ? "checked" : "" ?>>
                    <div class="sw-nav" style="background:<?= $h($t['nav']) ?>;--sw-stripe:<?= $h($t['stripe']) ?>"></div>
                    <div class="sw-body" style="background:<?= $h($t['bg']) ?>">
                      <i style="background:<?= $h($t['primary']) ?>"></i><i style="background:<?= $h($t['accent']) ?>"></i><i style="background:<?= $h($t['surface']) ?>;border:1px solid <?= $h($t['border']) ?>"></i>
                    </div>
                    <div class="sw-name"><?= $h($t['name']) ?><?= !empty($t['dark']) ? " <small class='text-muted'>(dark)</small>" : "" ?></div>
                  </label>
                  <?php } ?>
                </div>
                <hr>
                <div class="form-group">
                  <label class="col-sm-3 control-label">Custom colours</label>
                  <div class="col-sm-9" style="display:flex;gap:18px;flex-wrap:wrap;align-items:center">
                    <span><input type="color" id="primary_pick" value="<?= $h($current['primary']) ?>" oninput="setColor('primary', this.value)"> Primary</span>
                    <span><input type="color" id="accent_pick" value="<?= $h($current['accent']) ?>" oninput="setColor('accent', this.value)"> Accent</span>
                    <input type="hidden" name="primary_color" id="primary_color" value="<?= $h($s['primary_color']) ?>">
                    <input type="hidden" name="accent_color" id="accent_color" value="<?= $h($s['accent_color']) ?>">
                    <a href="javascript:void(0)" onclick="resetColors()"><i class="fa fa-undo"></i> Use the theme's colours</a>
                  </div>
                </div>
                <div class="form-group">
                  <label for="font" class="col-sm-3 control-label">Font</label>
                  <div class="col-sm-5"><select name="font" id="font" class="form-control" onchange="setFont(this.value)">
                    <?php foreach (company_settings::$FONTS as $font) { ?><option value="<?= $h($font) ?>" <?= $font == $s['font'] ? "selected" : "" ?> style="font-family:'<?= $h($font) ?>'"><?= $h($font) ?></option><?php } ?>
                  </select></div>
                </div>
                <p class="text-muted small"><i class="fa fa-eye"></i> Changes preview live on this page. Click <strong>Save Settings</strong> to apply them for everyone.</p>
              </div>
            </div>
          </div>

          <div class="col-md-5">
            <div class="panel panel-default">
              <div class="panel-heading">Preview</div>
              <div class="panel-body">
                <div class="rt-preview-bar">
                  <div style="background:var(--rt-nav);color:var(--rt-nav-text);padding:10px 14px;display:flex;align-items:center;gap:10px;position:relative">
                    <span id="previewBrand" style="display:flex;align-items:center;gap:10px"><?= company_settings::brand_html(true) ?></span>
                    <span style="margin-left:auto;opacity:.8;font-size:12px">Fleet &nbsp; Reports &nbsp; Map</span>
                    <span style="position:absolute;left:0;right:0;bottom:-4px;height:4px;background:repeating-linear-gradient(90deg,var(--rt-stripe) 0 20px,transparent 20px 34px),color-mix(in srgb,var(--rt-nav) 85%,#000)"></span>
                  </div>
                  <div style="background:var(--rt-bg);padding:18px 14px 14px">
                    <div class="fleet-kpi"><div class="v">12 / 14</div><div class="l">Stops delivered today</div></div>
                    <button type="button" class="btn btn-primary btn-sm">Start Trip</button>
                    <button type="button" class="btn btn-info btn-sm">Trip Sheet</button>
                    <button type="button" class="btn btn-default btn-sm">Cancel</button>
                    <span class="fleet-badge" style="background:var(--rt-accent);color:var(--rt-on-accent);margin-left:6px">In Transit</span>
                    <p style="margin:12px 0 0">Normal text with a <a href="javascript:void(0)">link</a> and <span class="text-muted">muted notes</span>.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================ company -->
      <div id="company" class="tab-pane fade <?= $tab == "company" ? "in active" : "" ?>">
        <div class="panel panel-info" style="max-width:860px">
          <div class="panel-heading">Company details <small class="text-muted">- printed on trip sheets and delivery receipts</small></div>
          <div class="panel-body">
            <?php foreach (company_settings::$TEXT_SETTINGS as $key => $label) { ?>
            <div class="form-group">
              <label for="<?= $key ?>" class="col-sm-3 control-label"><?= $h($label) ?></label>
              <div class="col-sm-9">
                <?php if ($key == "company_address") { ?>
                  <textarea name="<?= $key ?>" id="<?= $key ?>" class="form-control" rows="3" maxlength="255"><?= $h($s[$key]) ?></textarea>
                <?php } else { ?>
                  <input type="text" name="<?= $key ?>" id="<?= $key ?>" class="form-control" maxlength="255" value="<?= $h($s[$key]) ?>">
                <?php } ?>
              </div>
            </div>
            <?php } ?>
          </div>
        </div>
      </div>

      <!-- ============================================================ regional -->
      <div id="regional" class="tab-pane fade <?= $tab == "regional" ? "in active" : "" ?>">
        <div class="row">
          <div class="col-md-7">
            <div class="panel panel-info">
              <div class="panel-heading">Time, money and units</div>
              <div class="panel-body">
                <div class="form-group">
                  <label for="timezone" class="col-sm-3 control-label">Timezone</label>
                  <div class="col-sm-9">
                    <select name="timezone" id="timezone" class="form-control">
                      <?php foreach ($zones as $region => $list) { ?>
                        <optgroup label="<?= $h($region) ?>">
                        <?php foreach ($list as $zone) { ?><option value="<?= $h($zone) ?>" <?= $zone == $s['timezone'] ? "selected" : "" ?>><?= $h(str_replace("_", " ", $zone)) ?></option><?php } ?>
                        </optgroup>
                      <?php } ?>
                    </select>
                    <p class="help-block">Decides "today" for trips, orders and reports, and every time shown to staff and drivers.
                      Company time now: <strong><?= date("d M Y, H:i") ?> <?= $h(company_settings::tz_abbr()) ?></strong> <span id="tzPreview"></span></p>
                    <div class="checkbox"><label><input type="checkbox" name="convert_times" value="1" checked>
                      When the timezone changes, convert all recorded times (trip start/end, GPS, expenses, deliveries) so they keep meaning the same moment</label></div>
                  </div>
                </div>
                <?php
                  $units = array(
                    "currency" => array("Currency", company_settings::$CURRENCIES, function ($code, $c) { return $code . " - " . $c[1] . " (" . trim($c[0]) . ")"; }),
                    "distance_unit" => array("Distance", company_settings::$DISTANCE_UNITS, function ($code, $label) { return $label; }),
                    "fuel_unit" => array("Fuel volume", company_settings::$FUEL_UNITS, function ($code, $label) { return $label; }),
                  );
                  foreach ($units as $key => $def) {
                    $blocker = company_settings::change_blocker($key);
                ?>
                <div class="form-group">
                  <label for="<?= $key ?>" class="col-sm-3 control-label"><?= $def[0] ?></label>
                  <div class="col-sm-9">
                    <select name="<?= $key ?>" id="<?= $key ?>" class="form-control" <?= $blocker != "" ? "disabled" : "" ?>>
                      <?php foreach ($def[1] as $value => $item) { ?><option value="<?= $h($value) ?>" <?= $value == $s[$key] ? "selected" : "" ?>><?= $h($def[2]($value, $item)) ?></option><?php } ?>
                    </select>
                    <?php if ($blocker != "") { ?><p class="help-block"><i class="fa fa-lock"></i> Locked: <?= $h($blocker) ?>.</p><?php } ?>
                  </div>
                </div>
                <?php } ?>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div class="panel panel-default">
              <div class="panel-heading">How these settings work</div>
              <div class="panel-body small">
                <p><strong>GPS and maps</strong> do not depend on the timezone: phones send coordinates with the moment they were recorded, and the server stores that moment in company time.</p>
                <p><strong>Timezone</strong> can be changed at any time. With conversion on, every recorded time is shifted (daylight saving included) so history stays correct. Scheduled dates (trip date, delivery date, document expiry) are business dates and are not shifted.</p>
                <p><strong>Currency, distance and fuel units</strong> are labels for what staff and drivers enter. They lock once data exists in them, because recorded amounts and readings are not converted.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;align-items:center;margin:6px 0 20px">
      <button type="submit" name="save_settings" value="1" class="btn btn-primary btn-lg"><i class="fa fa-floppy-o"></i> Save Settings</button>
      <a href="company_settings.php?tab=<?= $h($tab) ?>" class="btn btn-default btn-lg">Discard changes</a>
    </div>
  </form>

  <?php if (count($history) > 0) { ?>
  <div class="panel panel-default" style="max-width:860px">
    <div class="panel-heading">Recent changes</div>
    <table class="table table-condensed small" style="margin:0">
      <thead><tr><th>When</th><th>Setting</th><th>By</th></tr></thead>
      <?php foreach ($history as $row) { ?><tr><td><?= $h($row['updated_at']) ?></td><td><?= $h($row['setting_key'] . " = " . $row['setting_value']) ?></td><td><?= $h(trim($row['first_name'] . " " . $row['last_name'])) ?></td></tr><?php } ?>
    </table>
  </div>
  <?php } ?>
</div>
<?php include ROOTPATH . '/admin/templates/admin_footer_start.php'; ?>
<script>
	var CURRENT_TZ = <?= json_encode($s['timezone']) ?>;
	var THEMES = <?= json_encode($themes_js) ?>;

	// ---- live preview: apply a theme's CSS variables to this page
	function applyVars(vars){
		vars.split(";").forEach(function(pair){
			var i = pair.indexOf(":");
			if(i > 0){ document.documentElement.style.setProperty(pair.substr(0, i).trim(), pair.substr(i + 1).trim()); }
		});
	}
	function readable(hex){
		var c = [1, 3, 5].map(function(i){ var v = parseInt(hex.substr(i, 2), 16) / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
		return (0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]) > 0.4 ? "#111827" : "#ffffff";
	}
	function setColor(slot, value){
		$("#" + slot + "_color").val(value);
		document.documentElement.style.setProperty("--rt-" + slot, value);
		document.documentElement.style.setProperty("--rt-on-" + slot, readable(value));
	}
	function resetColors(){
		var t = THEMES[$("input[name=theme]:checked").val()];
		$("#primary_color, #accent_color").val("");
		$("#primary_pick").val(t.primary); $("#accent_pick").val(t.accent);
		applyVars(t.vars);
		setFont($("#font").val());
	}
	function setFont(font){
		var id = "font-" + font.replace(/ /g, "-");
		if(!document.getElementById(id)){
			$("head").append("<link id='" + id + "' rel='stylesheet' href='https://fonts.googleapis.com/css2?family=" + font.replace(/ /g, "+") + ":wght@400;500;600;700&display=swap'>");
		}
		document.documentElement.style.setProperty("--rt-font", "'" + font + "',system-ui,-apple-system,'Segoe UI',sans-serif");
	}
	$(".rt-swatch").on("click", function(){
		$(".rt-swatch").removeClass("selected");
		$(this).addClass("selected").find("input").prop("checked", true);
		var t = THEMES[$(this).data("theme")];
		$("body").toggleClass("rt-dark", t.dark);
		resetColors();
	});
	function previewLogo(input){
		if(input.files && input.files[0]){
			var url = URL.createObjectURL(input.files[0]);
			$("#logoMark").hide();
			$("#logoPreview").attr("src", url).show();
			$(".rt-logo").not("#logoPreview").replaceWith("<img src='" + url + "' class='rt-logo' alt=''>");
		}
	}

	// ---- tabs remember where you were
	$("a[data-toggle=tab]").on("shown.bs.tab", function(e){ $("#tabField").val($(e.target).data("tab")); });

	// ---- timezone change warning
	$("#timezone").change(function(){
		var tz = $(this).val();
		if(tz == CURRENT_TZ){ $("#tzPreview").text(""); return; }
		try {
			var t = new Intl.DateTimeFormat("en-GB", { timeZone: tz, day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit", timeZoneName: "short" }).format(new Date());
			$("#tzPreview").html(" &rarr; after change: <strong>" + t + "</strong>");
		} catch(e) { $("#tzPreview").text(""); }
	});
	function confirmChange(){
		if($("#timezone").val() != CURRENT_TZ){
			return confirm("Change the company timezone to " + $("#timezone").val() + "?\n\nThis changes what 'today' means for trips and orders" +
				($("input[name=convert_times]").is(":checked") ? " and converts all recorded times." : ". Recorded times will NOT be converted."));
		}
		return true;
	}
</script>
<?php include ROOTPATH . '/admin/templates/admin_footer_end.php'; ?>
