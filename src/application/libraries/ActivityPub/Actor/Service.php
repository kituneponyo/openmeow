<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/05/16
 * Time: 1:17
 */

namespace Meow\ActivityPub\Actor;


class Service extends \LibraryBase
{
	public static function create ($host, $sharedInbox) {
		if (!$host || !$sharedInbox) {
			return false;
		}
		$values = [
			'host' => $host,
			'shared_inbox' => $sharedInbox,
		];
		self::db()->insert('ap_service', $values);
		return true;
	}

	public static function load (string $host) {
		$sql = " select * from ap_service where host = ? limit 1 ";
		return self::db()->query($sql, [$host])->row();
	}
}