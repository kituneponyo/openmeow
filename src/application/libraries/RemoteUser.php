<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/04/26
 * Time: 8:40
 */

class RemoteUser extends LibraryBase
{
	public static function get (string $actor) {
		$host = parse_url($actor, PHP_URL_HOST);
		if ($host == Meow::FQDN) {
			return false;
		}
		$sql = " select * from user where actor = ? limit 1 ";
		return self::db()->query($sql, [$actor])->row();
	}

	public static function insert (\stdClass $actor) {
		if (!$actor->preferredUsername) {
			return false;
		}
		$host = parse_url($actor->url, PHP_URL_HOST);
		if ($host == Meow::FQDN) {
			return false;
		}
		$mid = $actor->preferredUsername . '@' . $host;
		$sql = " select id from user where mid = ? limit 1 ";
		if ($user = self::db()->query($sql, [$mid])->row()) {

		} else {
			$values = [
				'mid' => $mid,
				'name' => ($actor->name ?? $actor->preferredUsername),
				'note' => $actor->summary ?? '',
				'icon' => ($actor->icon->url ?? ''),
				'header_img' => ($actor->image->url ?? ''),
				'actor' => $actor->id,
			];
			self::db()->insert('user', $values);
			return self::db()->insert_id();
		}

		return false;
	}

	public static function update (\stdClass $content) {
		$values = [
			'name' => $content->name,
			'icon' => ($content->icon->url ?? ''),
			'header_img' => ($content->image->url ?? ''),
			'note' => $content->summary,
		];
		$actor = self::db()->escape($content->id);
		self::db()->update('user', $values, " actor = {$actor} ");
		return true;
	}
}