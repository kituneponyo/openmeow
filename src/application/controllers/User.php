<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Meow\ActivityPub\Actor\Actor;

class User extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function albums ($mid) {
	    $me = $this->getMe();

	    ($user = MeowManager::getUser($mid)) || $this->toTop();

	    $user->prof_path = MeowManager::getProfPath($user->id);
	    $user->icon_path = $user->icon ? "{$user->prof_path}/{$user->icon}" : '/assets/icons/cat_footprint.png';
	    $user->icon_media_type = mime_content_type($_SERVER['DOCUMENT_ROOT'] . $user->icon_path);
	    $user->note = str_replace(['<', '>'], ['&lt;', '&gt;'], $user->note);

	    // フォローしてる？
	    if ($me) {
		    $sql = " select * from follow where user_id = ? and follow_user_id = ? ";
		    $follow = $this->db->query($sql, [$me->id, $user->id])->row();
		    $followed = $this->db->query($sql, [$user->id, $me->id])->row();
	    } else {
		    $follow = [];
		    $followed = [];
	    }

	    $sql = " select * from album where user_id = {$user->id} and is_public = 1 ";
	    $albums = $this->db->query($sql)->result();

	    $this->display('user/index.twig', [
		    'mode' => 'albums',
		    'user' => $user,
		    'bgcolor' => $user->bgcolor,
		    'follow' => $follow,
		    'followed' => $followed,
		    'albums' => $albums,
		    'hasPublicAlbum' => 1,
		    'enableMeowStartButton' => 0,
	    ]);
	    return true;
    }

    public function index ($mid, $mode = false) {

    	if ($mode == 'albums') {
    		return $this->albums($mid);
	    }

    	$me = $this->getMe();

    	// actor の url に .json を付けるとjsonを返してほしい
    	if (strpos($mid, '.json') !== false) {
    		list($mid, $ext) = explode('.', $mid);
    		if ($ext != 'json') {
    			exit;
		    }
		    $mode = 'ap';
	    }

	    ($user = MeowManager::getUser($mid)) || $this->toTop();

	    $headers = getallheaders();
	    if (isset($headers['Accept'])
		    && strpos($headers['Accept'], 'application/activity+json') !== false
		    && !$mode
	    ) {
		    $this->load->library('ActivityPubService');
		    $this->load->library('MeowUser');
		    $person = MeowUser::content($user);
		    ActivityPubService::responseJson($person);
		    exit;
	    }
	    // テスト用
	    if ($mode == 'ap') {
		    $this->load->library('ActivityPubService');
		    $this->load->library('MeowUser');
		    $person = MeowUser::content($user);
		    ActivityPubService::responseJson($person);
		    exit;
	    }

	    switch ($mode) {
		    case 'following': return $this->following();
		    case 'followers': return $this->followers($user);
	    }

	    if ($mode == 'inbox') {
		    $this->load->library('ActivityPubService');
		    return ActivityPubService::userInbox($mid);
	    }

	    if ($mode == 'outbox') {
		    $this->load->library('ActivityPubService');
	    	$values = [];
		    return ActivityPubService::responseJson($values);
	    }

    	if ($user->is_remote_user) {
		    $user->icon_path = $user->icon ? $user->icon : '/assets/icons/cat_footprint.png';

	    } else {
		    $user->note = str_replace(['<', '>'], ['&lt;', '&gt;'], $user->note);

		    $user->prof_path = MeowManager::getProfPath($user->id);
		    $user->icon_path = $user->icon ? "{$user->prof_path}/{$user->icon}" : '/assets/icons/cat_footprint.png';
		    $user->icon_media_type = mime_content_type($_SERVER['DOCUMENT_ROOT'] . $user->icon_path);
	    }

    	// ミュートしてる？
	    if ($me) {
		    $sql = " select * from mute where user_id = {$me->id} and mute_user_id = {$user->id} ";
		    $this->db->query($sql)->row() && $this->toTop();
	    }

	    $q = $this->input->post_get('q');

	    $meows = MeowManager::getUserTimeLine($me, $user, $mode, 0, $q, 0);

	    $last_create_at = $meows ? $meows[0]->create_at : 0;

	    // フォローしてる？
	    if ($me) {
		    $sql = " select * from follow where user_id = ? and follow_user_id = ? ";
		    $follow = $this->db->query($sql, [$me->id, $user->id])->row();
		    $followed = $this->db->query($sql, [$user->id, $me->id])->row();
	    } else {
	    	$follow = [];
		    $followed = [];
	    }

	    if ($mode == 'atom') {
		    $this->display('activitypub/user_atom.twig', [
			    'user' => $user,
			    'meows' => $meows,
		    ]);
		    return true;
	    }

	    // 公開アルバムある？
	    $sql = " select id from album where user_id = {$user->id} and is_public = 1 limit 1 ";
	    $hasPublicAlbum = $this->db->query($sql)->row();

	    // remote user なら actor取得
	    if ($user->actor) {
		    $this->load->library('ActivityPubService');
//	    	$actor = ActivityPubService::getActor($user->actor);
	    	$actor = Actor::get($user->actor);
	    } else {
	    	$actor = false;
	    }

	    if (!$mode) {
	    	$tl = 'u';
	    } else if ($mode == 'media') {
	    	$tl = 'um';
	    } else {
	    	$tl = '';
	    }

        $this->display('user/index.twig', [
        	'mode' => $mode,
        	'tl' => $tl,
        	'user' => $user,
	        'bgcolor' => $user->bgcolor,
	        'meows' => $meows,
	        'follow' => $follow,
	        'followed' => $followed,
	        'hasPublicAlbum' => $hasPublicAlbum,
	        'q' => $q,
	        'enableMeowForm' => 1,
	        'enableMeowStartButton' => 0,
        ]);
	    return true;
    }

    public function followers ($user) {
    	if ($page = $_GET['page'] ?? false) {
    		$values = [
				"@context" => "https://www.w3.org/ns/activitystreams",
			    "id" => Meow::BASE_URL . "/u/{$user->mid}/followers?page=1",
			    "type" => "OrderedCollectionPage",
			    "totalItems" => 0,
			    "next" => Meow::BASE_URL . "/u/{$user->mid}/followers?page=1",
			    "partOf" => Meow::BASE_URL . "/u/{$user->mid}/followers",
			    "orderedItems" => []
		    ];
	    } else {
    		$values = [
    			"@context" => "https://www.w3.org/ns/activitystreams",
			    "id" => Meow::BASE_URL . "/u/{$user->mid}/followers",
			    "type" => "OrderedCollection",
			    "totalItems" => 0,
			    "first" => Meow::BASE_URL . "/u/{$user->mid}/followers?page=1"
		    ];
	    }
	    ActivityPubService::responseJson($values);
    	exit;
    }

    public function following () {

    }

    private function outbox ($user) {
    	$page = $_GET['page'] ?? false;
    	if ($page) {
		    $values = [
			    '@context' => 'https://www.w3.org/ns/activitystreams',
			    'id' => Meow::BASE_URL . "/u/{$user->mid}/outbox?page=true",
			    'type' => 'OrderedCollectionPage',
			    'totalItems' => 0,
			    'first' => Meow::BASE_URL . "/u/{$user->mid}/outbox?page=true",
			    'last' => Meow::BASE_URL . "/u/{$user->mid}/outbox?page=true",
			    'orderedItems' => []
		    ];
		    // 最新100件
		    $sql = " 
 				select * 
 				from meow 
 				where user_id = {$user->id} and is_deleted = false
 				order by id desc
 				limit 100
            ";
		    $meows = $this->db->query($sql)->result();
		    foreach ($meows as $meow) {
		    	$values['orderedItems'][] = [
		    		//'id' => "https://meow.fan/"
			    ];
		    }
	    } else {
		    $values = [
			    '@context' => 'https://www.w3.org/ns/activitystreams',
			    'id' => Meow::BASE_URL . "/u/{$user->mid}/outbox",
			    'type' => 'OrderedCollection',
			    'totalItems' => 0,
			    'first' => Meow::BASE_URL . "/u/{$user->mid}/outbox?page=true",
			    'last' => Meow::BASE_URL . "/u/{$user->mid}/outbox?page=true",
		    ];
	    }
    }

    private function atom ($mid) {

    }



}
