<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/04/26
 * Time: 14:21
 */

class DirectMessage extends LibraryBase
{
	public static function insert ($userId, $toUser, $text, $filename) {

		$create_at = date('Y-m-d H:i:s');

		// ミュートされてない？
		$sql = " select id from mute where user_id = ? and mute_user_id = ? limit 1 ";
		$isMuted = self::db()->query($sql, [$toUser->id, $userId])->row();

		$ip = $_SERVER['REMOTE_ADDR'] ?? '';

		$values = [
			'user_id' => $userId,
			'to_user_id' => $toUser->id,
			'text' => $text,
			'ipint' => ip2long($ip),
			'host' => gethostbyaddr($ip),
			'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
			'file' => $filename,
			'create_at' => $create_at,
		];
		self::db()->insert('dm', $values);

		$dm_id = self::db()->insert_id();

		// DM送信先のDM未読件数++（相手にミュートされてない場合のみ）
		if ($toUser->unread_dm_count < 255 && !$isMuted) {
			$sql = " update user set unread_dm_count = unread_dm_count + 1 where id = ? ";
			self::db()->query($sql, [$toUser->id]);
		}

		// meowにも便宜的にID取得
		$values = [
			'is_private' => 5,
			'user_id' => $userId,
			'dm_id' => $dm_id,
		];
		self::db()->insert('meow', $values);

		$meow_id = self::db()->insert_id();


		// update dm.meow_id
		$values = ['meow_id' => $meow_id];
		self::db()->update('dm', $values, " id = {$dm_id} ");


		// 自分以外へのDM、かつリモートユーザーでなければ通知
		if ($userId != $toUser->id
			&& !$toUser->is_remote_user
		) {
			$values = [
				'from_user_id' => $userId,
				'to_user_id' => $toUser->id,
				'type' => 2,
				'object_id' => $dm_id,
				'create_at' => $create_at,
				'meow_id' => $meow_id,
				'dm_id' => $dm_id,
			];
			self::db()->insert('notice', $values);
		}

		// 最新情報の静的化
		if (!$toUser->actor) {
			MeowManager::createUserInfoJson($toUser->id);
		}

		return $dm_id;
	}
}