<?php
defined('BASEPATH') OR exit('No direct script access allowed');


use KubAT\PhpSimple\HtmlDomParser;

class Api extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function v1 () {
    	print "/api/v1/instance";
    }

    public function ip () {
    	print $_SERVER['REMOTE_ADDR'];
    }

	public function createUserJson ($id) {
		if ($path = MeowManager::createUserInfoJson($id)) {
			readfile($path);
		}
	}

    public function deliverActivity (int $userId, int $id) {

	    $this->load->library('ActivityPubService');

    	// 自前限定
    	if ($_SERVER['REMOTE_ADDR'] != file_get_contents(Meow::BASE_URL . '/api/ip')) {
    		http_response_code(403);
    		exit;
	    }

    	$meow = Meow::load($id);

    	// 本人限定
    	if ($meow->user_id != $userId) {
    		http_response_code(403);
    		exit;
	    }

	    $activity = $meow->createActivity();
    	$activityJson = json_encode($activity, JSON_UNESCAPED_SLASHES);

    	// 配信先一覧
	    $sql = "
			select
				s.shared_inbox
			from
				follow f
				inner join user ru
					on ru.id = f.user_id
					and ru.actor != ''
				inner join ap_actor actor
					on actor.actor = ru.actor
				inner join ap_service s
					on s.host = actor.host
					and s.enable_deliver = 1
		  	where
		  		f.follow_user_id = ?
		  	group by s.shared_inbox
    	";
	    $rows = $this->db->query($sql, [$meow->user_id])->result();
	    foreach ($rows as $row) {
		    // 全体向けなら shared_inbox にぶちこむ
		    if (isset($activity->object->to)
			    && $row->shared_inbox
			    && in_array('https://www.w3.org/ns/activitystreams#Public', $activity->object->to)
		    ) {
			    ActivityPubService::safe_remote_post($row->shared_inbox, $activityJson, $meow->mid);
		    }
	    }

    	print "ok";
    }


    public function postMeow () {

    }

    public function checkLatest () {
	    $me = $this->getMe();

	    $lastUpdate = date('Y-m-d H:i:s', $_POST['last_update'] ?? 0);

	    $q = $this->input->post('q');

	    $id = false;
	    $tl = $_POST['tl'] ?? 'p';
	    if ($tl == 'h') {
	    	$id = MeowManager::checkHomeTimeline($me, $lastUpdate, $q);
	    } elseif ($tl == 'e') {
		    $id = MeowManager::checkEachFollowTimeLine($me, $lastUpdate, $q);
	    } elseif ($tl == 'r') {

	    } else {
		    $id = MeowManager::checkMyPublicTimeLine($me, $lastUpdate, $q);
	    };

	    print $id;
    }

    public function getMeowReplies () {
	    $me = $this->getMeOrApiError();
	    $id = $this->input->post('id');
	    $replies = MeowManager::getMeowReplies($me, $id);
	    print json_encode($replies);
    }

    public function getLatest () {
    	$me = $this->getMe();

    	$lastUpdate = date('Y-m-d H:i:s', $this->input->post_get('last_update'));

	    $q = $this->input->post('q');

	    $max = $this->input->post('max');

	    $mid = $this->input->post('u');
	    $user = MeowManager::getUser($mid);

    	$tl = $this->input->post_get('tl') ?? 'p';
    	if ($tl == 'h') {
		    $meows = MeowManager::getHomeTimeline($me, $lastUpdate, $q, $max);
	    } elseif ($tl == 'e') {
		    $meows = MeowManager::getPrivateTimeline($me, $lastUpdate, $q, $max);
	    } elseif ($tl == 'r') {
		    $meows = MeowManager::getNotices($me, $lastUpdate, $q, $max);
		    MeowManager::checkNotice($me);
	    } elseif ($tl == 'u') {
		    $meows = MeowManager::getUserTimeLine($me, $user, '', $lastUpdate, $q, $max);
	    } elseif ($tl == 'um') {
    		$meows = MeowManager::getUserTimeLine($me, $user, 'media', $lastUpdate, $q, $max);
	    } else {
    		$meows = MeowManager::getMyPublicTimeLine($me, $lastUpdate, $q, $max);
	    }

	    print json_encode($meows);
    }

    private function updateMeow ($set) {
	    $me = $this->getMeOrApiError();
	    $id = $this->input->post_get('id');
	    if (!$id) {
		    return $this->apiError();
	    }
	    $sql = " update meow set {$set} where user_id = ? and id = ? ";
	    $this->db->query($sql, [$me->id, $id]);

	    // 新着変更
	    MeowManager::createLatestJson();

	    $this->apiOk();
    }
    public function changeMeowToSensitive () {
    	$v = $this->input->post('v') ? 1 : 0;
	    $this->updateMeow(" is_sensitive = {$v} ");
    }
    public function changeMeowToPublic () {
	    $this->updateMeow(" is_private = 0 ");
    }
    public function changeMeowToPrivate () {
	    $this->updateMeow(" is_private = 3 ");
    }

    public function getAlbums () {
	    $me = $this->getMeOrApiError();
	    $sql = " select * from album where user_id = {$me->id} ";
	    $albums = [];
	    if ($result = $this->db->query($sql)->result()) {
		    foreach ($result as $row) {
			    $albums[] = [
			    	'id' => $row->id,
				    'name' => $row->name,
			    ];
		    }
	    }
	    print json_encode($albums);
    }

    public function getMuteWords () {
	    $me = $this->getMeOrApiError();
	    $sql = " select * from mute_word where user_id = {$me->id} ";
	    $words = [];
	    if ($result = $this->db->query($sql)->result()) {
	    	foreach ($result as $row) {
			    $words[] = $row->word;
		    }
	    }
	    print json_encode($words);
    }

	public function getMuteUsers () {
		$me = $this->getMeOrApiError();
		$muteIds = [];
		$sql = "
    	    select mute_user_id
    	    from mute
    	    where user_id = {$me->id}
    	";
		if ($result = $this->db->query($sql)->result()) {
			foreach ($result as $row) {
				$muteIds[] = intval($row->mute_user_id);
			}
		}
		print json_encode($muteIds);
	}

	public function getOgp () {
		$this->load->library('OgpManager');
		ini_set('display_errors', "On");
		$url = $this->input->post('url');
		print OgpManager::_loadOgp($url);
	}
}
