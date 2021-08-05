<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bookmark extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function index () {
    	$me = $this->getMeOrJumpTop();
    	$columns = MeowManager::getBasicColumns();
    	$sql = "
    	    select
    	    	{$columns},
				ru.mid as reply_mid
    	    from
    	    	bookmark b
            	inner join meow m on m.id = b.meow_id
            	inner join user u on u.id = m.user_id
            	left outer join meow r
            		on r.id = m.reply_to
				left outer join user ru
					on ru.id = r.user_id
            where
            	b.user_id = {$me->id}
            	and b.album_id = 0
            order by m.id desc
            limit 100
    	";
	    $meows = $this->db->query($sql)->result();
	    $meows = MeowManager::decorateMeows($meows);

	    $sql = " select * from album where user_id = {$me->id} ";
	    $albums = $this->db->query($sql)->result();

	    setcookie('tl', 'b', time()+60*60*24*30*12, '/');

    	$this->display('bookmark/index.twig',[
    		'albums' => $albums,
    		'meows' => $meows,
		    'enableSearch' => 0,
		    'enableMeowStartButton' => 0,
		    'tl' => 'b',
	    ]);
    }

    public function list (int $album_id) {
	    $me = $this->getMeOrJumpTop();

	    $sql = " select * from album where user_id = {$me->id} and id = {$album_id} ";
	    $album = $this->db->query($sql)->row();

	    $columns = MeowManager::getBasicColumns();
	    $sql = "
    	    select
    	    	{$columns},
				ru.mid as reply_mid
    	    from
    	    	bookmark b
            	inner join meow m on m.id = b.meow_id
            	inner join user u on u.id = m.user_id
            	left outer join meow r
            		on r.id = m.reply_to
				left outer join user ru
					on ru.id = r.user_id
            where
            	b.user_id = {$me->id}
            	and b.album_id = {$album_id}
            order by m.id desc
            limit 100
    	";
	    $meows = $this->db->query($sql)->result();
	    $meows = MeowManager::decorateMeows($meows);

	    $sql = " select * from album where user_id = {$me->id} ";
	    $albums = $this->db->query($sql)->result();

	    setcookie('tl', 'b', time()+60*60*24*30*12, '/');

	    $this->display('bookmark/index.twig',[
		    'albums' => $albums,
		    'meows' => $meows,
		    'album_id' => $album_id,
		    'album' => $album,
		    'enableSearch' => 0,
		    'enableMeowStartButton' => 0,
		    'tl' => 'b',
	    ]);
    }

    public function album () {
	    $this->display('bookmark/album.twig',[
	    ]);
    }

    public function changeIsPublic (int $albumId, int $status) {
	    $me = $this->getMeOrApiError();
	    $sql = " update album set is_public = {$status}
 			where user_id = {$me->id} and id = {$albumId}
	    ";
	    $this->db->query($sql);
	    return $this->apiOk();
    }

	public function on () {
		$me = $this->getMeOrApiError();
	    $meow_id = intval($this->input->post('meow_id'));
    	if (!$meow_id) {
		    $this->apiError();
	    }
	    if ($album_id = intval($this->input->post('album_id'))) {
	    	$sql = " select id from album where user_id = {$me->id} and id = {$album_id} ";
	    	if ($row = $this->db->query($sql)->row()) {
		    } else {
	    		$album_id = 0;
		    }
	    }

	    // 存在チェック
		$sql = " select * from bookmark where user_id = {$me->id} and meow_id = {$meow_id} ";
    	if ($row = $this->db->query($sql)->row()){
    		if ($row->album_id != $album_id) {
    			$sql = " 
 					update bookmark 
 					set album_id = {$album_id} 
 					where 
 						meow_id = {$meow_id}
 						and user_id = {$me->id}
                ";
    			$this->db->query($sql);
		    }
		    $this->apiOk();
	    }

	    $values = [
	    	'user_id' => $me->id,
		    'meow_id' => $meow_id,
		    'album_id' => $album_id,
	    ];
    	$this->db->insert('bookmark', $values);
    	print 1;
	}

	public function off () {
		$me = $this->getMeOrApiError();
		$meow_id = intval($this->input->post('meow_id'));
		if (!$meow_id) {
			$this->apiError();
		}
		$sql = " 
 			delete from bookmark 
 			where 
 				user_id = {$me->id} 
 				and meow_id = ?
        ";
		$this->db->query($sql, [$meow_id]);
		print 1;
	}

	public function q () {
		$me = $this->getMeOrApiError();
		$ids = $this->input->post('ids');
		if ($ids) {
			$sql = "
				select group_concat(b.meow_id) as ids
				from bookmark b
				where
					b.user_id = {$me->id}
					and b.meow_id in ?
			";
			if ($row = $this->db->query($sql, [$ids])->row()) {
				print json_encode(explode(',', $row->ids));
			}
		}
	}


}
