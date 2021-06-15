<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/02/19
 * Time: 7:03
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

require_once('RemoteUser.php');
require_once('DirectMessage.php');
require_once('MeowLogger.php');
require_once('Signature.php');

require_once('ActivityPub/Activity/Activity.php');
require_once('ActivityPub/Activity/CreateActivity.php');
require_once('ActivityPub/Activity/LikeActivity.php');
require_once('ActivityPub/Activity/UndoActivity.php');
require_once('ActivityPub/Actor/Actor.php');
require_once('ActivityPub/Actor/Service.php');
require_once('ActivityPub/Object/ActivityPubObject.php');
require_once('ActivityPub/Object/Note.php');
require_once('ActivityPub/Collection.php');

use Meow\ActivityPub\Activity\LikeActivity;
use Meow\ActivityPub\Activity\UndoActivity;
use Meow\ActivityPub\Actor\Actor;
use Meow\ActivityPub\Object\ActivityPubObject;
use Meow\ActivityPub\Object\Note;
use Meow\ActivityPub\Collection;

class ActivityPubService extends LibraryBase
{
	public static function init () {

	}

	public static function insertDeliverQueue ($userId, $apActivityId) {

		// 既存チェック
		$sql = " select id from ap_deliver_queue where user_id = ? and ap_activity_id = ? ";
		if ($row = self::db()->query($sql, [$userId, $apActivityId])->row()) {
			return $row->id;
		}

		$values = [
			'user_id' => $userId,
			'ap_activity_id' => $apActivityId,
		];
		self::db()->insert('ap_deliver_queue', $values);
		return self::db()->insert_id();
	}

	public static function requestAsync ($url) {
		$guzzleClient = new GuzzleHttp\Client();
		$promise = $guzzleClient->requestAsync('GET', $url);
		$promise->then(
		// Fullfilled
			function(ResponseInterface $res){},
			// Rejected
			function(RequestException $e) {}
		);
		$promise->wait();
	}

	public static function sendUndoLike ($me, $favId, $meow) {
		$apObject = ActivityPubObject::load($meow->ap_object_id);
		$actorRow = Actor::get($apObject->actor_id);
		$likeActivity = LikeActivity::create($favId, $me->actor->id, $apObject->object_id);
		$undoActivity = UndoActivity::create($likeActivity);
		self::safe_remote_post($actorRow->content->inbox, $undoActivity, $me->mid);
	}

	public static function sendLike ($me, $favId, $meow) {
		$apObject = ActivityPubObject::load($meow->ap_object_id);
		$actorRow = Actor::get($apObject->actor_id);
		$activity = LikeActivity::create($favId, $me->actor->id, $apObject->object_id);
		self::safe_remote_post($actorRow->content->inbox, $activity, $me->mid);
	}

	public static function queryActor (string $host, string $preferredUsername) {

		$webfingerCacheDir = Meow::BASE_DIR . "/application/cache/webfinger/";
		FileManager::checkExistsDir($webfingerCacheDir);

		$webfingerCacheFilePath = $webfingerCacheDir . "/" . $preferredUsername . "@" . $host;
		if (is_file($webfingerCacheFilePath)) {
			$ftime = filemtime($webfingerCacheFilePath);
			// キャッシュの期限は1時間
			$expire_time = time() - 3600;
			$wf = ($ftime > $expire_time) ? file_get_contents($webfingerCacheFilePath) : false;
		}

		if (empty($wf)) {
			$webfingerUrl = "https://{$host}/.well-known/webfinger?resource=acct:{$preferredUsername}@{$host}";
			$wf = file_get_contents($webfingerUrl);
			if (!$wf) {
				return false;
			}
			file_put_contents($webfingerCacheFilePath, $wf);
		}
		$wf = json_decode($wf);

		$profUrl = $wf->links[0]->href;
		$response = self::safe_remote_request('GET', $profUrl, '', 0, false);
		$body = $response->getBody()->getContents();
		$actor = json_decode($body);
		if ($profUrl && $body) {
			// キャッシュしとく
			Actor::insert($actor);
			if ($actor->type == 'Person') {
				RemoteUser::insert($actor);
			}
		}
		return Actor::get($preferredUsername . '@' . $host);
	}

	public static function getMeowIdByObjectId ($objectId) {
		if (parse_url($objectId, PHP_URL_HOST) != Meow::FQDN) {
			return 0;
		}
		$segments = explode('/', $objectId);
		$replyAt = array_pop($segments);
		return is_numeric($replyAt) ? $replyAt : 0;
	}

	public static function getActivityPubObjectRowId (string $objectId) {
		if ($row = ActivityPubObject::load($objectId)) {
			return $row->id;
		}
		$response = ActivityPubService::safe_remote_get($objectId);
		$json = $response->getBody()->getContents();
		$object = json_decode($json);
		return ActivityPubObject::create($object);
	}

	public static function processInbox (\stdClass $content) {

		if ($content->type == 'Add') {
			Collection::add($content->target, $content->object);
		}

		if ($content->type == 'Remove') {
			Collection::remove($content->target, $content->object);
		}

		if ($content->type == 'Create') {
			ActivityPubObject::create($content->object);
		}

		if ($content->type == 'Delete') {
			ActivityPubObject::delete($content);
		}

		if ($content->type == 'Update') {
			ActivityPubObject::update($content);
		}
	}

	public static function sharedInbox () {

		$jsonString = trim(file_get_contents('php://input'));
		if (!$jsonString) {
			return 401;
		}

		$content = json_decode($jsonString);

		$object = $content->object ?? '';
		if (is_object($object)) {
			$object = json_encode($object, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// activity自身にIDがあれば
		if (!empty($content->id)) {
			// object_id で存在チェック
			$sql = " select id from inbox where object_id = ? limit 1 ";
			if (self::db()->query($sql, [$content->id])->row()) {
				return 202; // 重複あるなら終わる
			}
		}

		MeowLogger::logInbox($content);

		// delete person だけ先にチェックしちゃう
		if ($content->type == 'Delete' && $content->actor == $content->object) {
			ActivityPubObject::delete($content);
			return 202;
		}

		// activity に actor が設定されていれば
		if ($content->actor) {
			// actor 取得
			$actor = Actor::get($content->actor);
			if (!empty($actor->content)) {
				$actorContent = is_string($actor->content) ? json_decode($actor->content) : $actor->content;
				$pem = $actorContent->publicKey->publicKeyPem ?? '';
				if ($pem) {
					if (!Signature::verify(getallheaders(), $pem, $_SERVER['REQUEST_URI'])) {
						return 401;
					}
				}
			}
		} else {
			$actor = null;
		}

		// get remote user
		$remoteUser = $actor ? RemoteUser::get($content->actor) : false;

		if (!empty($content->id)) {
			$values = [
				'object_id' => $content->id,
				'user_id' => ($remoteUser ? $remoteUser->id : 0),
				'type' => $content->type ?? '',
				'actor' => $content->actor ?? '',
				'object' => $object,
				'content' => $jsonString,
			];
			self::db()->insert('inbox', $values);
		}

		self::processInbox($content);

		return 202;
	}

	public static function processUserInbox (\stdClass $content, $user = false) {

		$actor = Actor::get($content->actor);

		$remoteUser = RemoteUser::get($content->actor);
		if (!$remoteUser) {
			RemoteUser::insert($actor->content);
			$remoteUser = RemoteUser::get($content->actor);
		}

		// actor とれないなら　無視してよい
		if (!$actor) {
			return http_response_code(202);
		}

		// Create
		if ($content->type == 'Create') {
			ActivityPubObject::create($content->object);
		}

		// Follow
		if ($content->type == 'Follow' && $user) {
			$sql = " select * from follow where user_id = ? and follow_user_id = ? ";
			$follow = self::db()->query($sql, [$remoteUser->id, $user->id])->row();
			if (!$follow) {
				$values = [
					'user_id' => $remoteUser->id,
					'follow_user_id' => $user->id,
					'is_accepted' => 0,
				];
				self::db()->insert('follow', $values);
			}
		}

		// Undo
		if ($content->type == 'Undo') {
			$sql = " update inbox set apply = 0 where object_id = ? ";
			self::db()->query($sql, [$content->object->id]);

			// フォローの Undo ならリムーブ処理
			if ($content->object->type == 'Follow' && $user) {
				self::acceptUndoFollow($user, $content);
			}

			if ($content->object->type == 'Like') {
				if (self::acceptUndoRemoteLike($content)) {
					\Meow\ActivityPub\Activity\Activity::delete($content->object->id);
				}
			}
		}

		// Accept
		if ($content->type == 'Accept') {
			if ($content->object->type == 'Follow') {
				self::acceptFollow($content);
			}
		}

		// Like
		if ($content->type == 'Like') {
			self::acceptRemoteLike($content);
		}

		// Update
		if ($content->type == 'Update') {
			ActivityPubObject::update($content);
		}
	}

	public static function userInbox ($mid) {

		($user = MeowManager::getUser($mid)) || exit;

		($jsonString = trim(file_get_contents('php://input'))) || exit;

		$content = json_decode($jsonString);

		MeowLogger::logInbox($content, $mid);

		$object = $content->object ?? '';
		if (is_object($object)) {
			$object = json_encode($object, JSON_UNESCAPED_SLASHES);
		}

		// object_id で存在チェック
		$sql = " select id from inbox where object_id = ? limit 1 ";
		if (self::db()->query($sql, [$content->id])->row()) {
			return http_response_code(202);
		}

		$values = [
			'object_id' => $content->id,
			'user_id' => $user->id ?? 0,
			'type' => $content->type ?? '',
			'actor' => $content->actor ?? '',
			'object' => $object,
			'content' => $jsonString,
		];
		self::db()->insert('inbox', $values);

		self::processUserInbox($content, $user);

		return http_response_code(202);
	}

	public static function acceptUndoFollow ($user, $content) {
		$remoteUser = RemoteUser::get($content->actor);
		$sql = "
			delete from follow
			where
				user_id = ?
				and follow_user_id = ?
        ";
		self::db()->query($sql, [$remoteUser->id, $user->id]);
	}

	public static function acceptFollow ($content) {
		$segments = explode('/', $content->object->id);
		$followId = array_pop($segments);
		$sql = " select * from follow where id = ? ";
		$follow = self::db()->query($sql, [$followId])->row();
		if ($follow) {
			// accepted
			$sql = " update follow set is_accepted = 1 where id = ? ";
			self::db()->query($sql, [$followId]);
		}
		return true;
	}

	public static function acceptUndoRemoteLike ($content) {
		$remoteUser = RemoteUser::get($content->actor);
		if (!$remoteUser) {
			return false;
		}
		$host = parse_url($content->object->object, PHP_URL_HOST);
		if ($host != Meow::FQDN) {
			// とりあえず自前発言しか対応しない
			return false;
		}
		$segments = explode('/', $content->object->object);
		$meowId = array_pop($segments);

		$sql = " delete from fav where user_id = ? and meow_id = ? ";
		self::db()->query($sql, [$remoteUser->id, $meowId]);

		MeowManager::calcFavCount($meowId);

		return true;
	}

	public static function acceptRemoteLike ($content) {
		$remoteUser = RemoteUser::get($content->actor);
		if (!$remoteUser) {
			return false;
		}
		$host = parse_url($content->object, PHP_URL_HOST);
		if ($host != Meow::FQDN) {
			// とりあえず自前発言しか対応しない
			return false;
		}
		$segments = explode('/', $content->object);
		$meowId = array_pop($segments);

		// check exists
		$sql = " select id from fav where meow_id = ? and user_id = ? ";
		if (self::db()->query($sql, [$meowId, $remoteUser->id])->row()) {
			return true;
		}

		$values = [
			'meow_id' => $meowId,
			'user_id' => $remoteUser->id
		];
		self::db()->insert('fav', $values);

		MeowManager::calcFavCount($meowId);

		return true;
	}

	public static function responseJson ($array) {
		header('Content-Type: application/activity+json; charset=UTF-8');
		print json_encode($array, JSON_UNESCAPED_SLASHES);
		return true;
	}

	public static function toZuluTime ($YmdHis = false) {
		if (!$YmdHis) {
			$YmdHis = date('Y-m-d H:i:s');
		}
		$t = new DateTime($YmdHis);
		$t->setTimeZone(new DateTimeZone('UTC'));
		return $t->format("Y-m-d\TH:i:s\Z");
	}

	public static function safe_remote_get ($url) {
		return self::safe_remote_request('GET', $url, '', '');
	}

	public static function safe_remote_post($url, $body, $userMid) {
		return self::safe_remote_request('POST', $url, $body, $userMid);
	}

	public static function safe_remote_request ($method, $url, $body = '', string $userMid = '') {

		if (!is_string($body)) {
			$body = json_encode($body, JSON_UNESCAPED_SLASHES);
		}

		if ($userMid) {
			$user = MeowUser::getByMid($userMid);
			$privkey = $user->privkey ? $user->privkey : false;
		}
		if (empty($privkey)) {
			$privkey = Signature::loadServerPrivateKey();
		}


		$url_params = parse_url($url);
		$path = $url_params['path'];

		$date = \gmdate( 'D, d M Y H:i:s T' );
		$digest = $body ? Signature::generate_digest($body) : '';
		$signature = Signature::generate_signature($userMid, $url, $date, $digest, $privkey);

		$header = [
			'Accept' => 'application/activity+json',
			'Content-Type' => 'application/activity+json',
			'Signature' => $signature,
			'Date' => $date,
			'user-agent' => 'meow/' . Meow::FQDN,
		];
		if ($digest) {
			$header['Digest'] = "SHA-256={$digest}";
		}
		if ($body) {
			$header['Content-Length'] = strlen($body);
		}

		$client = new Client([
			'base_uri' => "https://" . $url_params['host'],
			'headers' => $header
		]);

		$params = [
			'timtout' => 100,
			'allow_redirects' => [
				'max' => 3,
			],
		];

		if ($body) {
			$params['body'] = $body;
		}

		if ($method == 'GET' && !empty($url_params['query'])) {
			$keyVals = explode('&', $url_params['query']);
			$query = [];
			foreach ($keyVals as $keyVal) {
				list($key, $val) = explode('=', $keyVal);
				$query[$key] = $val;
			}
			$params['query'] = $query;
		}
		$response = $client->request($method, $path, $params);
		return $response;
	}

}