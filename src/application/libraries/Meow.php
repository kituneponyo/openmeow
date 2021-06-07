<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/04/25
 * Time: 12:56
 */

/**
 * Class Meow
 *
 * @property string BASE_DIR
 */
class Meow extends LibraryBase
{
	const FQDN = MEOW_CONFIG_FQDN;
	const BASE_URL = 'https://' . MEOW_CONFIG_FQDN;
	const BASE_DIR = MEOW_CONFIG_BASE_DIR;
	const CONFIG_FILE_PATH = MEOW_CONFIG_FILE_PATH;
	const SALT = MEOW_CONFIG_SALT;
	const ADMIN_PASS_HASH = MEOW_CONFIG_ADMIN_PASS_HASH;

	public static $isDebug = false;

	private static $config = null;
	public static function config () {
		if (!self::$config) {
			global $meowConfig;
//			self::$config = $meowConfig;
			self::$config = new \stdClass();
			self::$config->host = MEOW_CONFIG_FQDN;
			self::$config->fqdn = MEOW_CONFIG_FQDN;
			self::$config->baseUrl = self::BASE_URL;
			self::$config->salt = MEOW_CONFIG_SALT;
		}
		return self::$config;
	}

	public static function load ($id, $me = false) {
		if (is_numeric($id)) {
			$_meow = MeowManager::getMeow($id, $me);
		} elseif ($id instanceof \stdClass) {
			$_meow = $id;
		}
		if (!$_meow) {
			return false;
		}
		$_meow = (array)$_meow;

		$meow = new self();
		foreach ($_meow as $k => $v) {
			$meow->$k = $v;
		}
		return $meow;
	}

	public $id;
	public $mid;
	public $reply_to;
	public $create_at;
	public $is_sensitive;
	public $summary;
	public $text;
	public $files;
	public $orgfiles;

	public function __construct () {
	}

	private $createActivityCache = null;
	private function _createActivity () {
		if (!$this->createActivityCache) {
			$inReplyTo = null;
			if ($this->reply_to) {
				$sql = " 
 				select n.object_id
 				from
 					meow m 
 					inner join ap_note n
 						on n.id = m.ap_note_id
 				where
 					m.id = ?
 			";
				if ($row = self::db()->query($sql, [$this->reply_to])->row()) {
					$inReplyTo = $row->object_id;
				}
			}
			$zulutime = ActivityPubService::toZuluTime($this->create_at);
			$activity = [
				'@context' => [
					'https://www.w3.org/ns/activitystreams',
					"https://w3id.org/security/v1",
				],
				'id' => Meow::BASE_URL . "/p/{$this->mid}/{$this->id}/activity",
				'type' => 'Create',
				'actor' => Meow::BASE_URL . "/u/{$this->mid}",
				'published' => $zulutime,
				'to' => [
					'https://www.w3.org/ns/activitystreams#Public',
				],
				'cc' => [
					Meow::BASE_URL . "/u/{$this->mid}/followers",
				],
				'object' => [
					'id' => Meow::BASE_URL . "/p/{$this->mid}/{$this->id}",
					'type' => 'Note',
					'summary' => $this->summary,
					'inReplyTo' => $inReplyTo,
					'published' => $zulutime,
					'url' => Meow::BASE_URL . "/p/{$this->mid}/{$this->id}",
					'attributedTo' => Meow::BASE_URL . "/u/{$this->mid}",
					'to' => [
						'https://www.w3.org/ns/activitystreams#Public',
					],
					'cc' => [
						Meow::BASE_URL . "/u/{$this->mid}/followers",
					],
					'sensitive' => ($this->is_sensitive ? true : false),
					'content' => $this->text,
					'attachment' => [],
					'tag' => [],
				]
			];

			// 添付ファイルがあれば
			if ($this->files) {

				$dir = "up/" . date('Y/m/d');
				$files = is_array($this->files) ? $this->files : explode(',', $this->files);
				foreach ($files as $file) {
					$path = $dir . "/" . $file;
					$fullpath = $_SERVER['DOCUMENT_ROOT'] . "/" . $path;
					$attach = [
						'type' => 'Document',
						'mediaType' => FileManager::getMimeType($fullpath),
						'url' => Meow::BASE_URL . "/" . $path
					];
					$activity['object']['attachment'][] = $attach;
				}
			}

			$activity = json_encode($activity, JSON_UNESCAPED_SLASHES);
			$this->createActivityCache = json_decode($activity);
		}
		return $this->createActivityCache;
	}
	public function createActivity () {
		return $this->_createActivity();
	}

	public function deleteActivity () {
		$activity = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => Meow::BASE_URL . "/p/k/{$this->id}/delete",
			'type' => 'Delete',
			'actor' => Meow::BASE_URL . "/u/{$this->mid}",
			'object' => [
				'id' => Meow::BASE_URL . "/p/k/{$this->id}",
				'to' => [
					"https://www.w3.org/ns/activitystreams#Public"
				],
				'type' => 'Tombstone',
			],
		];
		$activity = json_encode($activity, JSON_UNESCAPED_SLASHES);
		return json_decode($activity);
	}
}