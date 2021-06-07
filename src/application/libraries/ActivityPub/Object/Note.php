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

	public static function create ($actor, $content) {

		$values = [
			'object_id' => $content->object->id,
			'actor_id' => $actor->id,
			'acct' => $actor->preferred_username . '@' . $actor->host,
			'content' => $content->object->content,
			'object' => '' . json_encode($content->object, JSON_UNESCAPED_SLASHES),
			'create_at' => $content->published,
		];
		self::db()->insert('ap_note', $values);
		$apNoteId = self::db()->insert_id();

		$remoteUser = \RemoteUser::get($actor->content->id);

		// check attachment
		$files = [];
		$text = $content->object->content;
		if (!empty($content->object->attachment)) {
			foreach ($content->object->attachment as $attach) {
//				$text = $text . "\n" . '<a href="' . $attach->url . '">' . $attach->url . '</a>';
				$files[] = $attach->url;
			}
		}
		$files = $files ? implode(',', $files) : '';

//		// check emoji tag
//		if (!empty($content->object->tag)) {
//			foreach ($content->object->tag as $tag) {
//				if ($tag->type == 'Emoji')
//			}
//		}


		$replyTo = !empty($content->object->inReplyTo)
			? \ActivityPubService::getMeowIdByObjectId($content->object->inReplyTo)
			: 0;

		if (!empty($content->to) && is_array($content->to)) {

			// public 向けなら普通に meow に入れる
			if (in_array('https://www.w3.org/ns/activitystreams#Public', $content->to)) {
				$values = [
					'user_id' => $remoteUser->id,
					'reply_to' => $replyTo,
					'text' => $text,
					'is_sensitive' => ($content->object->sensitive ?? 0),
					'ap_note_id' => $apNoteId,
					'files' => $files,
				];
				self::db()->insert('meow', $values);
			}

			foreach ($content->to as $to) {
				if (strpos($to, \Meow::BASE_URL . "/u/") === 0) {
					$mid = str_replace(\Meow::BASE_URL . "/u/", '', $to);
					if ($toUser = \MeowUser::getByMid($mid)) {
						$filename = '';
						\DirectMessage::insert($remoteUser->id, $toUser, $text, $filename);
					}
				}
			}
		}

		// この actor の、最新100件より古い発言のうち、local user から reply も fav もないものを削除
		$sql = "
			select *
			from meow m
			where m.user_id = ?
			order by m.create_at desc
			limit 1
			offset 100
		";
		if ($row = self::db()->query($sql, [$remoteUser->id])->row()) {
			$sql = "
				delete from meow
				where id in (
					select
						m.id
					from
						meow m
						left outer join meow r
							on r.id = m.reply_to
						left outer join fav f
							on f.meow_id = m.id
					where
						m.create_at < ?
						and m.user_id = ?
						and (r.id is null and f.id is null)
				)
			";
			self::db()->query($sql, [$row->create_at, $remoteUser->id]);
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