<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/06/16
 * Time: 16:10
 */

namespace Meow;

class Follow extends \LibraryBase
{
	public static function add (int $followUserId, int $followedUserId, int $isAccepted = 1) {
		// check exists
		if ($follow = self::load($followUserId, $followedUserId)) {
			return $follow->id;
		} else {
			$values = [
				'user_id' => $followUserId,
				'follow_user_id' => $followedUserId,
				'is_accepted' => $isAccepted,
			];
			self::db()->insert('follow', $values);
			return self::db()->insert_id();
		}
	}

	public static function addRequest (int $followUserId, int $followedUserId) {
		return self::add($followUserId, $followedUserId, 0);
	}

	public static function remove (int $rowId, int $followUserId, int $followedUserId) {
		$sql = " delete from follow where id = ? and user_id = ? and follow_user_id = ? ";
		return self::db()->query($sql, [$rowId, $followUserId, $followedUserId]);
	}

	public static function load (int $followUserId, int $followedUserId) {
		$sql = " select * from follow where user_id = ? and follow_user_id = ? ";
		return self::db()->query($sql, [$followUserId, $followedUserId])->row();
	}
}