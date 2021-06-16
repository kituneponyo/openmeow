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
	public static function addRequest  (int $followUserId, int $followedUserId) {
		// check exists
		$sql = " select id from follow where user_id = ? and follow_user_id = ? ";
		$follow = self::db()->query($sql, [$followUserId, $followedUserId])->row();
		if (!$follow) {
			$values = [
				'user_id' => $followUserId,
				'follow_user_id' => $followedUserId,
				'is_accepted' => 0,
			];
			self::db()->insert('follow', $values);
		}
		return true;
	}

	public static function remove (int $rowId, int $followUserId, int $followedUserId) {
		$sql = " delete from follow where id = ? and user_id = ? and follow_user_id = ? ";
		return self::db()->query($sql, [$rowId, $followUserId, $followedUserId]);
	}
}