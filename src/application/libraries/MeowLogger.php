<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/05/16
 * Time: 0:25
 */

class MeowLogger extends LibraryBase
{
	const LOG_BASE_PATH = MEOW_CONFIG_BASE_DIR . "/application/logs";

	public static function logDeliver ($inboxUrl, $activityId, $activity, $response) {
		$dir = self::LOG_BASE_PATH . "/deliver/" . date('Y/m/d');
//		list($sec, $msec) = explode(".", microtime(true));
		$segments = explode(".", microtime(true));
		$msec = str_pad($segments[1] ?? '0', 3, '0', STR_PAD_LEFT);
		$path = $dir . "/" . date('YmdHis') . substr($msec, 0, 3) . "_{$activity->type}_{$activityId}.log";

		self::log($path, $inboxUrl . "\n\n" . print_r($activity, true) . "\n\n" . print_r($response, true));
	}

	public static function logInbox ($content, $mid = 0) {
		$dir = self::LOG_BASE_PATH . "/inbox/" . date('Y/m/d');
//		list($sec, $msec) = explode(".", microtime(true));
		$segments = explode(".", microtime(true));
		$msec = str_pad($segments[1] ?? '0', 3, '0', STR_PAD_LEFT);
		$path = $dir . "/" . date('YmdHis') . substr($msec, 0, 3) . ($mid ? '_u' : '') . ".log";

		$headers = getallheaders();

		self::log($path, $_SERVER['REQUEST_URI'] . "\n\n" . print_r($headers, true) . "\n\n" . print_r($content, true));
	}

	public static function log ($path, $log) {
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($path, $log);
	}
}