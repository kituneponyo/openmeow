<?php

// これ以降に更新があればほしい
$lastUpdate = $_GET['lastUpdate'] ?? '';
if (!$lastUpdate) {
	print "";
	exit;
}

$filePath = $_SERVER['DOCUMENT_ROOT'] . "/latestSummary.json";
$fileTime = filemtime($filePath);

if ($lastUpdate >= $fileTime) {
	print "";
	exit;
}

readfile($filePath);
