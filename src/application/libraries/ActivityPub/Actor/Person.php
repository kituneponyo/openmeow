<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/05/13
 * Time: 5:53
 */

namespace Meow\ActivityPub\Actor;


class Person extends \LibraryBase
{
	public static function update (\stdClass $content) {
		$actor = self::db()->escape($content->id);
		$values = [
			'content' => json_encode($content, JSON_UNESCAPED_SLASHES),
			'update_at' => date('Y-m-d H:i:s'),
		];
		self::db()->update('ap_actor', $values, " actor = {$actor} ");

		$values = [
			'name' => (!empty($content->name) ? $content->name : $content->preferredUsername),
			'note' => ($content->summary ?? ''),
			'icon' => ($content->icon->url ?? ''),
			'header_img' => ($content->image->url ?? ''),
		];
		self::db()->update('user', $values, " actor = {$actor} ");
	}
}