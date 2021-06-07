<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class About extends MY_Controller {

    public function __construct () {
        parent::__construct();

        $this->load->library('RemoteUser');
    }

    public function index (string $page = '') {

	    if ($page == 'meow') {
		    return $this->meow();
	    }

    	$pageName = "about/{$page}";
    	$sql = " select * from page where user_id = 0 and name = ? ";
    	$page = $this->db->query($sql, [$pageName])->row();

        $this->display('about/index.twig', [
        	'page' => $page,
	        'enableMeowStartButton' => false,
        ]);
    }

	public function meow () {
		$this->display('about/meow.twig', [
			'enableMeowStartButton' => false,
		]);
	}

	public function blank () {
    	print "";
	}
	public function blankJson () {
    	print "{}";
	}
	public function blankRss () {
    	print "<?xml version='1.0' encoding='UTF-8'?><rss version='2.0'></rss>";
	}

	public function api_statusnet_config_json () {
    	exit;
	}
	public function static_terms_of_service_html () {
    	exit;
	}

	public function api_v1_instance () {
		print "/api/v1/instance";
	}

	public function nodeinfo_2_0 () {
    	$values = [
    		'version' => MEOW_VERSION,
		    'software' => [
		    	'name' => 'meow',
			    'version' => MEOW_VERSION,
		    ],
		    'protocols' => [
		    	'activitypub'
		    ],
		    'usage' => [
		    	'users' => [
		    		'total' => 0,
				    'activeMonth' => 0,
				    'activeHalfyear' => 0
			    ],
			    'localPosts' => 0
		    ]
	    ];
		print json_encode($values, JSON_UNESCAPED_SLASHES);
	}

	public function manifest () {
    	$values = [
			"short_name" => MEOW_CONFIG_SITE_NAME,
			"name" => MEOW_CONFIG_SITE_NAME,
			"start_url" => "https://" . MEOW_CONFIG_DB_HOST . "/",
			"display" => "standalone",
			"description" => "",
			"icons" => [[
				"src" => "/assets/icons/icon_144_144.png",
				"sizes" => "144x144",
				"type" => "image/png"
			]]
		];
    	print json_encode($values, JSON_UNESCAPED_SLASHES);
	}

	public function statistics () {
    	$values = [
		    "name" => MEOW_CONFIG_SITE_NAME,
		    "network" => "meow",
		    "version" => MEOW_VERSION,
		    "registrations_open" => true,
		    "total_users" => "0",
		    "active_users_halfyear" => "0",
		    "active_users_monthly" => "0",
		    "local_posts" => "0",
		    "services" => [
			    "appnet" => false,
			    "buffer" => false,
			    "dreamwidth" => false,
			    "gnusocial" => false,
			    "libertree" => false,
			    "livejournal" => false,
			    "pumpio" => false,
			    "twitter" => false,
			    "tumblr" => false,
			    "wordpress" => false
		    ],
		    "appnet" => false,
		    "buffer" => false,
		    "dreamwidth" => false,
		    "gnusocial" => false,
		    "libertree" => false,
		    "livejournal" => false,
		    "pumpio" => false,
		    "twitter" => false,
		    "tumblr" => false,
		    "wordpress" => false
	    ];
		print json_encode($values, JSON_UNESCAPED_SLASHES);
	}



}
