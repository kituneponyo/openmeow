<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dm extends MY_Controller {

    public function __construct () {
        parent::__construct();

        $this->load->library('DirectMessage');
    }

    public function index () {

    	$me = $this->getMeOrJumpTop();

	    $sql = "
			select
				u.id as user_id,
				u.mid,
				u.name,
				u.icon,
				u.actor,
				d.text,
				d.file,
				d.create_at
			from
				(
					select
						summary.total_user_id as user_id,
						summary.dm_id
					from
						(
					        select
					            case when d.user_id = {$me->id}
					                then d.to_user_id
					                else d.user_id
					                end as total_user_id,
					            max(d.id) as dm_id
					        from
					            dm d 
					        where
					            (d.user_id = {$me->id} or d.to_user_id = {$me->id})
					            and d.user_id not in (select mute_user_id from mute where user_id = {$me->id})
					            and d.is_deleted = 0
					        group by total_user_id
					    ) as summary
	        	) a
				    inner join user u 
				        on u.id = a.user_id
			        inner join dm d
			        	on d.id = a.dm_id
			order by
				a.dm_id desc
			limit 100
	    ";
	    $dms = $this->db->query($sql)->result();
	    foreach ($dms as $i => $dm) {
	    	$dm->display_time = MeowManager::getDisplayTime($dm->create_at);

		    if ($dm->actor) {
			    $dm->icon_path = $dm->icon ? $dm->icon : '/assets/icons/cat_footprint.png';
		    } else {
			    $dm->prof_path = MeowManager::getProfPath($dm->user_id);
			    $dm->icon_path = $dm->icon ? "{$dm->prof_path}/{$dm->icon}" : '/assets/icons/cat_footprint.png';
			    $dm->text = str_replace('<', '&lt;', $dm->text);
		    }

		    $dms[$i] = $dm;
	    }

	    if ($me->unread_dm_count) {
		    $sql = " update user set unread_dm_count = 0 where id = {$me->id} ";
		    $this->db->query($sql);
		    $me->unread_dm_count = 0;
	    }

	    $this->display('dm/index.twig', [
		    'dms' => $dms,
		    'enableSearch' => 0,
		    'enableMeowStartButton' => 0,
	    ]);
	    return true;
    }

    public function u ($mid) {

	    $me = $this->getMeOrJumpTop();

	    $user = MeowManager::getUser($mid);
	    if (!$user) {
	    	header('Location: /');
	    	exit;
	    }


	    if ($user->actor && strpos($user->actor, Meow::BASE_URL) === false) {
		    $user->icon_path = $user->icon ? $user->icon : '/assets/icons/cat_footprint.png';
	    } else {
		    $user->prof_path = MeowManager::getProfPath($user->id);
		    $user->icon_path = $user->icon ? "{$user->prof_path}/{$user->icon}" : '/assets/icons/cat_footprint.png';
		    $user->note = str_replace('<', '&lt;', $user->note);
	    }

	    $sql = "
	        select
	        	d.*,
	        	u.mid,
	        	u.name
	        from
	        	dm d
	        	inner join user u 
	        		on u.id = d.user_id
	        where
	        	(
	        		(d.user_id = {$me->id} and d.to_user_id = {$user->id})
		        	or (d.user_id = {$user->id} and d.to_user_id = {$me->id})
	            )
	        	and d.is_deleted = 0
	        order by create_at desc
	        limit 100
	    ";

	    $dms = $this->db->query($sql)->result();
	    foreach ($dms as $i => $dm) {
	    	$dm->display_time = MeowManager::getDisplayTime($dm->create_at);

	    	if ($user->actor) {

		    } else {
			    $dm->text = str_replace('<', '&lt;', $dm->text);
			    $dm->text = str_replace('>', '&gt;', $dm->text);
		    }

		    $dms[$i] = $dm;
	    }

	    $this->display('dm/user.twig', [
		    'user' => $user,
		    'dms' => $dms,
		    'enableSearch' => 0,
		    'enableMeowStartButton' => 0,
	    ]);
    }

    public function p ($mid, $id) {

	    $me = $this->getMeOrJumpTop();

	    ($user = MeowManager::getUser($mid)) || $this->toTop();

	    $sql = "
	        select
	        	d.*,
	        	u.mid,
	        	u.name
	        from
	        	dm d
	        	inner join user u 
	        		on u.id = d.user_id
	        where
	        	d.id = ?
	        	and (
	        		(d.user_id = {$me->id} and d.to_user_id = {$user->id})
		        	or (d.user_id = {$user->id} and d.to_user_id = {$me->id})
	            )
	        	and d.is_deleted = 0
	        order by create_at desc
	    ";
	    $dm = $this->db->query($sql, [$id])->row();
	    if (!$dm) {
		    header('Location: /');
		    exit;
	    }

	    $dm->display_time = MeowManager::getDisplayTime($dm->create_at);

	    $dm->text = str_replace('<', '&lt;', $dm->text);
	    $dm->text = str_replace('>', '&gt;', $dm->text);

	    $this->display('dm/user.twig', [
		    'user' => $user,
		    'dms' => [$dm],
		    'enableSearch' => 0,
		    'enableMeowStartButton' => 0,
	    ]);

    }

    public function post () {

	    $me = $this->getMeOrJumpTop();

	    $text = $this->input->post('text');
	    if (!$text && empty($_FILES['img']['name'])) {
		    header('location: /');
		    return false;
	    }

	    $to_user_id = $_POST['to_user_id'] ?? 0;
	    if (!$to_user_id) {
		    header('location: /');
		    return false;
	    }

	    $mid = $_POST['mid'] ?? '';
	    if (!$mid) {
		    header('location: /');
		    return false;
	    }

	    $toUser = MeowManager::getUser($mid);
	    if ($toUser->id != $to_user_id) {
		    header('location: /');
		    return false;
	    }

	    if (!empty($_FILES) && $ext = FileManager::mimeTypeToExt($_FILES['img']['type'])) {

		    $today = date('Y/m/d');

		    $mtime = str_replace('.', '', microtime(true));
		    $filename = "{$me->id}_{$mtime}.{$ext}";
		    $path = $_SERVER['DOCUMENT_ROOT'] . "/up/" . $today;
		    $fullpath = "{$path}/{$filename}";
		    if (!file_exists($path)) {
			    mkdir($path, 0777, true);
		    }

		    if (filesize($_FILES['img']['tmp_name']) > 1024 * 1024) {
			    $this->load->library('ComicManager');
			    ComicManager::resize($_FILES['img']['tmp_name'], $fullpath, 1024, 1024);
		    } else {
			    move_uploaded_file($_FILES['img']['tmp_name'], $fullpath);
		    }

	    } else {
		    $filename = '';
	    }

	    $dmId = DirectMessage::insert($me->id, $toUser, $text, $filename);

	    $sql = " select * from dm where id = ? ";

	    // remote user なら 連携
	    if ($toUser->actor && strpos($toUser->actor, Meow::BASE_URL) === false) {

	    	$this->load->library('ActivityPubService');

		    $dm = $this->db->query($sql, [$dmId])->row();

		    $published = ActivityPubService::toZuluTime($dm->create_at);

		    $actor = \Meow\ActivityPub\Actor\Actor::get($toUser->actor);
		    if (!$actor->content->inbox) {
		    	header('Location: /');
		    	exit;
		    }

	    	$activity = [
			    "@context" => [
				    "https://www.w3.org/ns/activitystreams",
				    "https://w3id.org/security/v1"
			    ],
			    "id" => Meow::BASE_URL . "/dm/p/{$me->mid}/{$dmId}/activity",
			    "type" => "Create",
			    "actor" => Meow::BASE_URL . "/u/" . $me->mid,
			    "published" => $published,
			    "to" => [
				    $toUser->actor
			    ],
			    "cc" => [],
			    "object" => [
				    "id" => Meow::BASE_URL . "/dm/p/{$me->mid}/{$dmId}",
				    "type" => "Note",
				    "summary" => null,
				    "inReplyTo" => null,
				    "published" => $published,
				    "url" => Meow::BASE_URL . "/dm/p/{$me->mid}/{$dmId}",
				    "attributedTo" => Meow::BASE_URL . "/u/" . $me->mid,
				    "to" => [
					    $toUser->actor
				    ],
				    "cc" => [],
				    "sensitive" => false,
				    "content" => $dm->text,
				    "attachment" => [],
				    "tag" => [
					    [
						    "type" => "Mention",
						    "href" => Meow::BASE_URL . '/u/' . $me->mid,
						    "name" => '@' . $me->mid . '@' . Meow::FQDN,
					    ]
				    ],
			    ]
		    ];

	    	$response = ActivityPubService::safe_remote_post($actor->content->inbox, $activity, $me->mid);
	    }

	    header("Location: /dm/u/{$mid}");
	    return true;
    }

    public function deleteSelfDm ($id = false) {
    	$me = $this->getMeOrJumpTop();
    	$id = intval($id);
    	if (!$id) {
    		header('Location: /');
    		return false;
	    }

	    // 差出人、宛先、どちらも自分なら削除できる
    	$sql = " update dm set is_deleted = 1 where user_id = ? and to_user_id = ? and id = ? ";
    	$this->db->query($sql, [$me->id, $me->id, $id]);

    	// meowも削除
	    $sql = " update meow set is_deleted = 1 where user_id = ? and dm_id = ? ";
	    $this->db->query($sql, [$me->id, $id]);

	    header("Location: /dm/u/{$me->mid}");
	    return false;
    }
}
