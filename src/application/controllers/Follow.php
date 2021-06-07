<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Follow extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    // 自分がフォローしてる人
    public function ee () {

	    $me = $this->getMeOrJumpTop();

	    $sql = "
	        select
	        	u.*,
	        	f2.id as is_reciprocal
	        from
	        	follow f
	        	inner join user u
	        		on u.id = f.follow_user_id
	        	left outer join follow f2
	        		on f2.user_id = u.id
	        		and f2.follow_user_id = f.user_id
	        where f.user_id = ?
	        order by f.follow_at desc
	        limit 100
	    ";
	    $followees = $this->db->query($sql, [$me->id])->result();
    	foreach ($followees as $i => $followee) {
    		if ($followee->actor) {
			    $followee->icon_path = $followee->icon ? $followee->icon : '/assets/icons/cat_footprint.png';
		    } else {
			    $followee->prof_path = MeowManager::getProfPath($followee->id);
			    $followee->icon_path = $followee->icon ? "{$followee->prof_path}/{$followee->icon}" : '/assets/icons/cat_footprint.png';
			    $followee->note = str_replace('<', '&lt;', $followee->note);
		    }
		    $followees[$i] = $followee;
	    }

	    $this->display('follow/followee.twig', [
		    'followees' => $followees,
		    'enableSearch' => 0,
		    'enableMeowStartButton' => 0,
	    ]);
    }

    // 自分をフォローしてる人
    public function er () {

	    $me = $this->getMeOrJumpTop();

	    $sql = "
	        select
	        	u.*,
	        	f2.id as is_reciprocal
	        from
	        	follow f
	        	inner join user u
	        		on u.id = f.user_id
	        	left outer join follow f2
	        		on f2.follow_user_id = u.id
	        		and f2.user_id = f.follow_user_id
	        where f.follow_user_id = ?
	        order by f.follow_at desc
	        limit 100
	    ";
	    $followers = $this->db->query($sql, [$me->id])->result();
	    foreach ($followers as $i => $follower) {
		    if ($follower->actor) {
			    $follower->icon_path = $follower->icon ? $follower->icon : '/assets/icons/cat_footprint.png';
		    } else {
			    $follower->prof_path = MeowManager::getProfPath($follower->id);
			    $follower->icon_path = $follower->icon ? "{$follower->prof_path}/{$follower->icon}" : '/assets/icons/cat_footprint.png';
			    $follower->note = str_replace('<', '&lt;', $follower->note);
		    }

		    $followers[$i] = $follower;
	    }

	    $this->display('follow/follower.twig', [
		    'followers' => $followers,
		    'enableSearch' => 0,
		    'enableMeowStartButton' => 0,
	    ]);
    }

    public function on () {

    	header('Expires: Tue, 1 Jan 2019 00:00:00 GMT');
	    header('Last-Modified:' . gmdate( 'D, d M Y H:i:s' ) . 'GMT');
	    header('Cache-Control:no-cache,no-store,must-revalidate,max-age=0');
	    header('Cache-Control:pre-check=0,post-check=0',false);
	    header('Pragma:no-cache');

    	$id = $_POST['id'] ?? 0;
    	if (!$id) {
		    die(0);
	    }

	    $me = $this->getMe();
    	if (!$me) {
    		die(0);
	    }

    	$sql = " select id from follow where user_id = ? and follow_user_id = ? ";
    	if ($this->db->query($sql, [$me->id, $id])->row()) {
		    die(0);
	    }

    	$values = [
    		'user_id' => $me->id,
		    'follow_user_id' => $id,
		    'is_accepted' => 1,
	    ];
    	$this->db->insert('follow', $values);

	    // フォロー数静的化
	    $this->calcFollowers($me->id, $id);

    	print 1;
    }

    public function off () {

    	header('Expires: Tue, 1 Jan 2019 00:00:00 GMT');
	    header('Last-Modified:' . gmdate( 'D, d M Y H:i:s' ) . 'GMT');
	    header('Cache-Control:no-cache,no-store,must-revalidate,max-age=0');
	    header('Cache-Control:pre-check=0,post-check=0',false);
	    header('Pragma:no-cache');

	    $id = $_POST['id'] ?? 0;
	    if (!$id) {
		    die(0);
	    }

	    $me = $this->getMe();
	    if (!$me) {
	    	die(0);
	    }

	    $sql = " delete from follow where user_id = ? and follow_user_id = ? ";
	    $this->db->query($sql, [$me->id, $id]);

	    // フォロー数静的化
	    $this->calcFollowers($me->id, $id);

	    print 1;
    }

    public function mute () {

	    $id = $_POST['id'] ?? 0;
	    if (!$id) {
		    die(0);
	    }

	    $me = $this->getMe();
	    if (!$me) {
		    die(0);
	    }

	    $values = [
	    	'user_id' => $me->id,
		    'mute_user_id' => $id,
	    ];
	    $this->db->insert('mute', $values);

	    $time = time();
	    $sql = " update user set mute_edit_at = {$time} where id = {$me->id} ";
	    $this->db->query($sql);

	    print 1;
    }

	// フォロー数静的化
    private function calcFollowers ($user1, $user2) {
	    $user1 = intval($user1);
	    $user2 = intval($user2);
    	$sql = "
	        update user set
	        	follows = (select count(id) from follow where user_id = user.id),
	        	followers = (select count(id) from follow where follow_user_id = user.id)
	        where user.id in ({$user1}, {$user2})
	    ";
	    $this->db->query($sql);
    }


}
