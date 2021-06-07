<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/04/25
 * Time: 16:36
 */

namespace Meow\ActivityPub\Activity;

class LikeActivity extends Activity
{
	public static function create ($favId, $actor, $objectId) {
		return [
			'@context' => "https://www.w3.org/ns/activitystreams",
			'id' => \Meow::BASE_URL . "/fav/{$favId}",
			'type' => 'Like',
			'actor' => $actor,
			'object' => $objectId,
			'content' => '★',
		];
	}
}