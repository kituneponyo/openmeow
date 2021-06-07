<?php

$filePath = $_SERVER['DOCUMENT_ROOT'] . "/latest.json";
readfile($filePath);

//print date('Y-m-d H:i:s');