<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Page extends MY_Controller {

    public function __construct () {
        parent::__construct();

    }

    private function _edit (string $mid, $id = false) {

	    $me = $this->getMeOrJumpTop();

	    if ($mid) {
		    ($me->mid != $mid) && $this->toTop();
		    $userId = $me->id;
	    } else {
		    $me->is_admin || $this->toTop();
		    $userId = 0;
	    }

	    $page = false;
	    if ($id) {
		    if (is_numeric($id)) {
			    $sql = " select * from page where user_id = ? and id = ? ";
			    $page = $this->db->query($sql, [$userId, $id])->row();
		    } else {
			    $sql = " select * from page where user_id = ? and name = ? ";
			    $page = $this->db->query($sql, [$userId, $id])->row();
		    }
	    }

	    if (!$page) {
	    	if ($id == 'about') {
	    		$title = 'about ' . Meow::FQDN;
		    } else {
	    		$title = $id;
		    }
	    	$page = [
	    		'title' => $title,
		    ];
	    }

	    $this->display('/page/edit.twig', [
		    'userId' => $userId,
		    'id' => $id,
		    'page' => $page,
	    ]);
    }

    public function editSpecial (string $dirName = '', string $pageName = '') {
    	if (!$dirName) {
    		$dirName = $this->input->post('dirName');
	    }
	    if (!$pageName) {
	    	$pageName = $this->input->post('pageName');
	    }
    	return $this->_edit('', $dirName . '/' . $pageName);
    }

    public function edit ($id = false) {
	    $me = $this->getMeOrJumpTop();
    	return $this->_edit($me->mid, $id);
    }

    public function update () {

	    $me = $this->getMeOrJumpTop();

	    $mid = $this->input->post('mid');
	    $id = $this->input->post('id');

	    if ($mid) {
		    ($me->mid != $mid) && $this->toTop();
		    $userId = $me->id;
	    } else {
		    $me->is_admin || $this->toTop();
		    $userId = 0;
	    }

	    $page = false;
	    if ($id) {
		    if (is_numeric($id)) {
			    $sql = " select * from page where user_id = ? and id = ? ";
			    $page = $this->db->query($sql, [$userId, $id])->row();
		    } else {
			    $sql = " select * from page where user_id = ? and name = ? ";
			    $page = $this->db->query($sql, [$userId, $id])->row();
		    }
	    }

	    if ($page) {
	    	// update
		    $values = [
			    'title' => $this->input->post('title'),
			    'html' => $this->input->post('html'),
			    'update_at' => date('Y-m-d H:i:s'),
		    ];
		    $where = " user_id = {$userId} and id = {$page->id} ";
		    $this->db->update('page', $values, $where);

	    } else {
	    	// insert
		    $values = [
		    	'user_id' => $userId,
			    'name' => $id,
			    'title' => $this->input->post('title'),
			    'html' => $this->input->post('html'),
		    ];
		    $this->db->insert('page', $values);
		    if (!$id) {
		    	$id = $this->db->insert();
		    }
	    }

	    if ($userId) {
		    header("Location: /page/edit/{$me->mid}/{$id}");
	    } else {
		    header("Location: /page/editSpecial/{$id}");
	    }
    }
}
