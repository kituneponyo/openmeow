<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Album extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function index () {
	    $this->display('my/album/index.twig',[
	    ]);
    }

    public function add () {
    	$me = $this->getMeOrJumpTop();

    	$name = $this->input->post('name');
    	$isPublic = $this->input->post('isPublic');

    	if (!$name) {
		    $this->display('my/album/index.twig',[
		    ]);
		    return true;
	    }

	    $values = [
	    	'user_id' => $me->id,
		    'name' => $name,
		    'is_public' => $isPublic ?? 0,
	    ];
    	$this->db->insert('album', $values);

	    $this->display('my/album/index.twig',[
	    ]);
    }



}
