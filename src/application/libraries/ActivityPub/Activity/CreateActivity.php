<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/02/20
 * Time: 15:28
 */

namespace Meow\ActivityPub\Activity;

use Meow\ActivityPub\Object\Note;

class CreateActivity extends Activity
{
	public function __construct($values)
	{
		$values['type'] = 'Create';
		parent::__construct($values);
	}

	public static function note ($note) {

	}

	public static function noteFromMeow ($meow) {
		$note = Note::fromMeow($meow);
		$activity = new self([
			'id' => \Meow::BASE_URL . "/p/{$meow->mid}/{$meow->id}/activity",
			'actor' => \Meow::BASE_URL . "/u/{$meow->mid}",
			'published' => \ActivityPubService::toZuluTime($meow->create_at),
			'to' => [
				"https://www.w3.org/ns/activitystreams#Public"
			],
			'cc' => [
				\Meow::BASE_URL . "/p/{$meow->mid}/followers"
			],
			"object" => [$note]
		]);
		return $activity;
	}
}