<?php
/*
 * Shows a driver's scanned licence / Aadhaar copy to office staff with access to Users.
 * The files live in uploads/driver_docs, which the web server refuses to serve directly (.htaccess).
 *   driver_doc.php?user_id=N&type=license|aadhaar
 */
include_once("../../../bootstrap.php");
include(ROOTPATH . "/admin/authorized.php");

if (!fleet::has_access(6)) {	// Users menu
	header('HTTP/1.1 403 Forbidden');
	exit("You do not have access to driver documents");
}
$user_id = (int) (isset($_GET['user_id']) ? $_GET['user_id'] : 0);
$column = (isset($_GET['type']) && $_GET['type'] == "aadhaar") ? "aadhaar_doc" : "license_doc";
$row = fleet::row("SELECT " . $column . " AS f FROM users WHERE user_id=" . $user_id);
$path = $row && $row['f'] != "" ? ROOTPATH . "/uploads/driver_docs/" . basename($row['f']) : "";
if ($path == "" || !is_file($path)) {
	header('HTTP/1.1 404 Not Found');
	exit("Document not found");
}
$types = array("jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png", "webp" => "image/webp", "pdf" => "application/pdf");
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header("Content-Type: " . (isset($types[$ext]) ? $types[$ext] : "application/octet-stream"));
header("Content-Disposition: inline; filename=\"" . $column . "_" . $user_id . "." . $ext . "\"");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");
readfile($path);
