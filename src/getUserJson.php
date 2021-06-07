<?php

$id = $_GET['id'] ?? 0;
if (!$id) {
	print "{}";
	exit;
}

$paddedId = str_pad($user_id, 4, '0', STR_PAD_LEFT);
$id0 = substr($paddedId, -1, 1);
$id1 = substr($paddedId, -2, 1);
$id2 = substr($paddedId, -3, 1);

$filePath = $_SERVER['DOCUMENT_ROOT'] . "/json/user/{$id0}/{$id1}/{$id2}/{$id}/user.json";

if (file_exists($filePath)) {
	readfile($filePath);
} else {
	print "{}";
}
