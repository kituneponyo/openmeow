<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/04/25
 * Time: 15:56
 */

class MeowUser extends LibraryBase
{
	public static function get($id) {
		$sql = " select * from user where id = ? limit 1 ";
		return self::db()->query($sql, [$id])->row();
	}

	public static function getByMid ($mid) {
		$sql = " select * from user where mid = ? limit 1 ";
		return self::db()->query($sql, [$mid])->row();
	}

	/**
	 * 指定したユーザがリモートフォローされているか
	 * @param int $userId
	 * @return bool
	 */
	public static function isRemoteFollowed ($userId) {
		$sql = "
	        select u.id
	        from
	            follow f
	            inner join user u 
	                on u.id = f.user_id
	                and u.actor != ''
	        where
	            f.follow_user_id = ?
	        limit 1
	    ";
		$remoteUser = self::db()->query($sql, [$userId])->row();
		return ($remoteUser != false);
	}

	public static function content ($user) {
		$user->prof_path = MeowManager::getProfPath($user->id);
		$user->icon_path = $user->icon ? "{$user->prof_path}/{$user->icon}" : '/assets/icons/cat_footprint.png';
		$icon_ext = pathinfo($user->icon_path, PATHINFO_EXTENSION);
		$icon_mime_type = FileManager::extToMimeType($icon_ext);
		$user->note = str_replace(['<', '>'], ['&lt;', '&gt;'], $user->note);
		if (mb_strlen($user->note, 'UTF-8') > 128) {
			$user->note = mb_substr($user->note, 0, 128, 'UTF-8') . '...';
		}

		$values = [
			"@context" => [
				"https://www.w3.org/ns/activitystreams",
				"https://w3id.org/security/v1",
			],
			"id" => Meow::BASE_URL . "/u/{$user->mid}",
			"type" => "Person",
			"following" => Meow::BASE_URL . "/u/{$user->mid}/following",
			"followers" => Meow::BASE_URL . "/u/{$user->mid}/followers",
			"name" => $user->name,
			"preferredUsername" => $user->mid,
			"summary" => $user->note,
			"inbox" => Meow::BASE_URL . "/u/{$user->mid}/inbox",
			"outbox" => Meow::BASE_URL . "/u/{$user->mid}/outbox",
			"url" => Meow::BASE_URL . "/u/{$user->mid}",
//			"manuallyApprovesFollowers" => true,
			'publicKey' => [
				'@context' => 'https://www.w3.org/ns/activitystreams',
				'type' => 'Key',
				'id' => Meow::BASE_URL . "/u/{$user->mid}#main-key",
				'owner' => Meow::BASE_URL . "/u/{$user->mid}",
				'publicKeyPem' => $user->pubkey,
			],
			"endpoints" => [
				"sharedInbox" => Meow::BASE_URL . "/inbox"
			],
			"icon" => [
				'type' => 'Image',
				'mediaType' => $icon_mime_type,
				'url' => Meow::BASE_URL . $user->icon_path,
			],
		];
		return $values;
	}

	public static function updateActivity ($user) {

		$activity = [
			"@context" => [
				"https://www.w3.org/ns/activitystreams",
				"https://w3id.org/security/v1",
				[
					"manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
					"toot" => "http://joinmastodon.org/ns#",
					"featured" => [
						"@id" => "toot:featured",
						"@type" => "@id"
					],
					"alsoKnownAs" => [
						"@id" => "as:alsoKnownAs",
						"@type" => "@id"
					],
					"movedTo" => [
						"@id" => "as:movedTo",
						"@type" => "@id"
					],
					"schema" => "http://schema.org#",
					"PropertyValue" => "schema:PropertyValue",
					"value" => "schema:value",
					"IdentityProof" => "toot:IdentityProof",
					"discoverable" => "toot:discoverable",
					"focalPoint" => [
						"@container" => "@list",
						"@id" => "toot:focalPoint"
					]
				]
			],
			"id" => Meow::BASE_URL . "/u/{$user->mid}#updates/{$user->update_at}",
			"type" => "Update",
			"actor" => Meow::BASE_URL . "/u/{$user->mid}",
			"to" => [
				"https://www.w3.org/ns/activitystreams#Public"
			],
			"object" => self::content($user),
		];

		$activity = json_encode($activity, JSON_UNESCAPED_SLASHES);
		return json_decode($activity);
	}
}