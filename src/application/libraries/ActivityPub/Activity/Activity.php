<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/02/20
 * Time: 15:16
 */

namespace Meow\ActivityPub\Activity;


class Activity extends \LibraryBase
{
	protected $values = [
		'@context' => [
			'https://www.w3.org/ns/activitystreams',
		],
	];

	public function __construct ($values) {
		$this->values += $values;
	}

	public function values () {
		return $this->values;
	}
	public function json () {
		return json_encode($this->values);
	}

	/**
	 * @param int $userId
	 * @param \stdClass $activity
	 * @return int
	 */
	public static function insert (int $userId, \stdClass $activity) {

		// 既存チェック
		$sql = " select id from ap_activity where object_id = ? ";
		if ($row = self::db()->query($sql, [$activity->id])->row()) {
			return $row->id;
		}

		$values = [
			'object_id' => $activity->id,
			'user_id' => $userId,
			'type' => $activity->type,
			'actor' => $activity->actor,
			'object' => json_encode($activity, JSON_UNESCAPED_SLASHES),
		];
		self::db()->insert('ap_activity', $values);
		return self::db()->insert_id();
	}

	public static function delete (string $objectId) {
		$sql = " delete from ap_activity where object_id = ? ";
		self::db()->query($sql, [$objectId]);
		$sql = " delete from inbox where object_id = ? ";
		self::db()->query($sql, [$objectId]);
		return true;
	}

//	public static function create ($type, $object) {
//
//	}
}