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

        return true;
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

	public function static_terms_of_service_html () {
    	exit;
	}

	public function nodeinfo_dummy () {
    	print $_SERVER['REQUEST_URI'];
	}

	public function nodeinfo_2_0 () {
    	$values = [
    		'version' => '2.0',
		    'software' => [
		    	'name' => MEOW_CONFIG_SITE_NAME,
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
    	print json_encode($values);
	}

	public function statistics () {
    	$dir = MEOW_CONFIG_BASE_DIR . "/assets/json";
    	if (!is_dir($dir)) {
    		mkdir($dir, 0777, true);
	    }

    	$path = $dir . "/statistics.json";
    	if (!is_file($path)) {
    		$this->createStatisticsJson($path);
	    }

	    $today = date('Y-m-d');

	    $filetime = date('Y-m-d', filemtime($path));

	    if ($today > $filetime) {
		    $this->createStatisticsJson($path);
	    }

	    readfile($path);
	}

	private function getTotalUsers () {
		$sql = " select count(id) as count from user where actor = '' ";
		$row = $this->db->query($sql)->row();
		return $row->count;
	}

	private function getLocalPosts () {
		$sql = "
			select
				count(m.id) as count
			from
				meow m
				inner join user u
					on u.id = m.user_id
					and u.actor = ''
			where
				m.is_deleted = 0
		";
		$row = $this->db->query($sql)->row();
		return $row->count;
	}

	private function activeUsersMonthly () {
		return $this->activeUsers(date('Y-m-d', strtotime(' - 1 month ')));
    }

    private function activeUsersHalfyear () {
    	return $this->activeUsers(date('Y-m-d', strtotime(' - 6 month ')));
    }

	private function activeUsers ($from) {
		$sql = "
			select count(a.id) as count
			from (
				select
					u.id
				from
					user u
					inner join meow m
						on m.user_id = u.id
						and m.create_at >= '{$from}'
						and m.is_deleted = 0
				where
					u.actor = ''
				group by
					u.id
			) a
		";
		$row = $this->db->query($sql)->row();
		return $row->count;
	}

	private function createStatisticsJson ($path) {

		$totalUsers = $this->getTotalUsers();
		$localPosts = $this->getLocalPosts();
		$activeUsersMonthly = $this->activeUsersMonthly();
		$activeUsersHalfyear = $this->activeUsersHalfyear();

		$values = [
			"name" => MEOW_CONFIG_SITE_NAME,
			"network" => "Meow",
			"version" => MEOW_VERSION,
			"registrations_open" => false,
			"total_users" => $totalUsers,
			"active_users_halfyear" => $activeUsersHalfyear,
			"active_users_monthly" => $activeUsersMonthly,
			"local_posts" => $localPosts,
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
		$json = json_encode($values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$json = str_replace(",", ",\n", $json);
		file_put_contents($path, $json);
	}


}
