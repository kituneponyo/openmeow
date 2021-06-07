<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My_album extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function index () {
	    $me = $this->getMeOrJumpTop();

	    $sql = " select * from album where user_id = {$me->id} ";
	    $albums = $this->db->query($sql)->result();
	    $this->display('my/album/index.twig',[
		    'albums' => $albums,
	    ]);
    }

    public function detail (int $id) {
	    $me = $this->getMeOrJumpTop();

	    $sql = " select * from album where user_id = {$me->id} and id = ? ";
	    $album = $this->db->query($sql, [$id])->row();

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
            	and b.album_id = {$id}
            order by m.id desc
            limit 100
    	";
	    $meows = $this->db->query($sql)->result();
	    $meows = MeowManager::decorateMeows($meows);

	    $this->display('my/album/detail.twig',[
		    'id' => $id,
		    'album' => $album,
		    'meows' => $meows,
	    ]);
    }

    public function add () {
	    $me = $this->getMeOrJumpTop();

    	$name = $this->input->post('name');
    	$isPublic = intval($_POST['isPublic'] ?? 0);

    	if (!$name) {
		    redirect('/bookmark');
	    }

	    $values = [
	    	'user_id' => $me->id,
		    'name' => $name,
		    'is_public' => $isPublic,
	    ];
    	$this->db->insert('album', $values);

    	$time = time();
    	$sql = " update user set album_edit_at = {$time} where id = {$me->id} ";
    	$this->db->query($sql);

	    redirect('/bookmark');
    }

    public function delete ($id = false) {
	    $me = $this->getMeOrJumpTop();

	    $id = intval($id);
	    if (!$id) {
		    redirect('/bookmark');
	    }

	    $sql = " delete from album where user_id = {$me->id} and id = {$id} ";
	    $this->db->query($sql);

	    $sql = " update bookmark set album_id = 0 where user_id = {$me->id} and album_id = {$id} ";
	    $this->db->query($sql);

	    $time = time();
	    $sql = " update user set album_edit_at = {$time} where id = {$me->id} ";
	    $this->db->query($sql);

	    redirect('/bookmark');
    }



}
