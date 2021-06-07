<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/04/25
 * Time: 16:52
 */

namespace Meow\ActivityPub\Activity;

class UndoActivity extends Activity
{
	public static function create ($activity) {
		unset($activity['@context']);
		return [
			'@context' => [
				'https://www.w3.org/ns/activitystreams',
				'https://w3id.org/security/v1',
			],
			'id' => $activity['id'] . "#Undo",
			'type' => 'Undo',
			'actor' => $activity['actor'],
			'object' => $activity
		];
	}
}