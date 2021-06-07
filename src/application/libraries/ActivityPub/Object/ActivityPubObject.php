<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/05/13
 * Time: 4:54
 */

namespace Meow\ActivityPub\Object;

use Meow\ActivityPub\Actor\Actor;

class ActivityPubObject extends \LibraryBase
{
	public static function create ($actor, $content) {

		if (!$actor) {
			return false;
		}
		if (empty($content->object->id)) {
			return false;
		}
		if (empty($content->object->type)) {

		}

		$values = [
			'object_id' => $content->object->id,
			'actor_id' => $actor->id,
			'type' => $content->object->type,
			'object' => json_encode($content->object, JSON_UNESCAPED_SLASHES),
		];

		self::db()->insert('ap_object', $values);

		if ($content->object->type == 'Note') {
			Note::create($actor, $content);
		}
	}

	public static function delete ($content) {

		// object が string で、https:// で始まる actor == object なら delete person
		if (is_string($content->object)
			&& strpos($content->object, 'https://') === 0
			&& $content->actor == $content->object
		) {
			Actor::delete($content->object);
			return true;
		}

		// ap_note にある？
		$sql = " select * from ap_note where object_id = ? limit 1 ";
		if ($apNote = self::db()->query($sql, [$content->object->id])->row()) {
			Note::delete($content, $apNote);
			return true;
		}

		return false;
	}

	public static function update (\stdClass $content) {
		if ($content->object->type == 'Person') {
			return Actor::update($content->object);
		}
	}
}