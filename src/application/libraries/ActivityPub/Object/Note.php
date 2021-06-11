<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/02/20
 * Time: 12:36
 */

Namespace Meow\ActivityPub\Object;

use Meow\ActivityPub\Activity\Activity;

class Note extends \LibraryBase
{
	var $id = '';
	const type = 'Note';
	var $summary = '';
	var $inReplyTo = '';
	var $published = '';
	var $url = '';
	var $attributedTo = '';
	var $to = [];
	var $cc = [];
	var $sensitive = 0;
	var $atomUri = '';
	var $inReplyToAtomUri = '';
	var $conversation = '';
	var $content = '';
	var $contentMap = '';
	var $attachment = [];
	var $tag = [];
	var $replies = [];

	public static function fromMeow ($meow) {
		$note = new Note();
		$note->id = \Meow::BASE_URL . "/p/{$meow->mid}/{$meow->id}";
		$note->published = \ActivityPubService::toZuluTime($meow->create_at);
		$note->url = \Meow::BASE_URL . "/p/{$meow->mid}/{$meow->id}";
		$note->content = $meow->text;
		$note->contentMap = [
			'ja' => $meow->text,
		];
		return $note;
	}

	public static function createFromObject ($actor, $object)
	{
		$remoteUser = \RemoteUser::get($actor->content->id);

		// check attachment
		$files = [];
		$text = $object->content;
		if (!empty($object->attachment)) {
			foreach ($object->attachment as $attach) {
				$files[] = $attach->url;
			}
		}
		$files = $files ? implode(',', $files) : '';

		$replyTo = !empty($object->inReplyTo)
			? \ActivityPubService::getMeowIdByObjectId($object->inReplyTo)
			: 0;

		if (!empty($object->to) && is_array($object->to)) {

			$apObject = ActivityPubObject::load($object->id);

			$sql = " 
 					select * 
 					from meow 
 					where
 						user_id = ? 
 						and ap_object_id = ?
				";
			$row = self::db()->query($sql, [$remoteUser->id, $apObject->id])->row();
			if ($row) {
			} else {
				// public 向けなら普通に meow に入れる
				if (in_array('https://www.w3.org/ns/activitystreams#Public', $object->to)) {
					$values = [
						'user_id' => $remoteUser->id,
						'reply_to' => $replyTo,
						'text' => $text,
						'is_sensitive' => ($object->sensitive ?? 0),
						'ap_object_id' => $apObject->id,
						'files' => $files,
					];
					self::db()->insert('meow', $values);
				}

				// 特定の人間宛だった場合、DMとなる
				foreach ($object->to as $to) {
					if (strpos($to, \Meow::BASE_URL . "/u/") === 0) {
						$mid = str_replace(\Meow::BASE_URL . "/u/", '', $to);
						if ($toUser = \MeowUser::getByMid($mid)) {
							$filename = '';
							\DirectMessage::insert($remoteUser->id, $toUser, $text, $filename);
						}
					}
				}
			}

		}
	}

	public static function delete ($content, $apNote) {

		// 対応するmeow取得
		$sql = " select * from meow where ap_note_id = ? limit 1 ";
		$meow = self::db()->query($sql, [$apNote->id])->row();
		if (!$meow) {
			return false;
		}

		// meowについたふぁぼ削除
		$sql = " delete from fav where meow_id = ? ";
		self::db()->query($sql, [$meow->id]);

		// meow 削除
		$sql = " delete from meow where id = ? ";
		self::db()->query($sql, [$meow->id]);

		// ap_note 削除
		$sql = " delete from ap_note where id = ? ";
		self::db()->query($sql, [$apNote->id]);

		// inbox 削除
		$sql = " delete from inbox where object_id = ? ";
		self::db()->query($sql, [$content->object->id]);

		return true;
	}
}