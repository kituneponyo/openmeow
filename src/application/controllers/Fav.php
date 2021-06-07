<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fav extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function index () {
    }

    public function on () {

    	$id = intval($_POST['id'] ?? 0);
    	if (!$id) {
    		exit;
	    }

	    $me = $this->getMeOrJumpTop();

    	// meowの実在確認、自分に閲覧権限あるか確認
	    $meow = MeowManager::getMeow($id, $me);
	    if (!$meow) {
	    	print 1;
	    	exit;
	    }

	    $sql = " select id from fav where user_id = ? and meow_id = ? ";
    	if ($this->db->query($sql, [$me->id, $id])->row()) {
    		print 1;
    		exit;
	    }

    	$values = [
    		'user_id' => $me->id,
		    'meow_id' => $id,
	    ];
    	$this->db->insert('fav', $values);
    	$favId = $this->db->insert_id();

	    // ふぁぼ数静的化
    	MeowManager::incrementFavCount($id);

    	// remote note の場合、Like を送る
	    if ($meow->ap_note_id) {
		    $this->load->library('ActivityPubService');
	    	ActivityPubService::sendLike($me, $favId, $meow);
	    }

    	print 1;
    }

    public function off () {
	    $id = $_POST['id'] ?? 0;
	    if (!$id) {
		    exit;
	    }

	    $me = $this->getMeOrJumpTop();

	    $meow = MeowManager::getMeow($id, $me);
	    if (!$meow) {
		    print 1;
		    exit;
	    }

	    $sql = " select * from fav where user_id = ? and meow_id = ? limit 1 ";
	    $fav = $this->db->query($sql, [$me->id, $id])->row();
	    if (!$fav) {
	    	print 1;
	    	exit;
	    }

	    $sql = " delete from fav where user_id = ? and meow_id = ? ";
	    $this->db->query($sql, [$me->id, $id]);

	    // ふぁぼ数静的化
	    MeowManager::decrementFavCount($id);

	    // remote note の場合、Undo を送る
	    if ($meow->ap_note_id) {
		    $this->load->library('ActivityPubService');
		    ActivityPubService::sendUndoLike($me, $fav->id, $meow);
	    }

	    print 1;
    }

	public function q () {
		$me = $this->getMeOrApiError();
		$ids = $this->input->post('ids');
		if ($ids) {
			$sql = "
				select group_concat(f.meow_id) as ids
				from fav f
				where
					f.user_id = {$me->id}
					and f.meow_id in ?
			";
			if ($row = $this->db->query($sql, [$ids])->row()) {
				print json_encode(explode(',', $row->ids));
			}
		}
	}


}
