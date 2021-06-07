<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/05/18
 * Time: 1:08
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		($me = $this->getMeOrJumpTop()) || $this->toTop();
		$me->is_admin || $this->toTop();
	}

	private function getConfig () {
		return is_file(Meow::CONFIG_FILE_PATH) ? json_decode(file_get_contents(Meow::CONFIG_FILE_PATH), true) : [];
	}

	public function index () {

		$this->display('/admin/index.twig');
	}

	public function edit () {

		$config = $this->getConfig();

		$names = [
			'siteName',
			'FQDN',
			'bgcolor',
		];

		foreach ($names as $name) {
			$config[$name] = $this->input->post_get($name);
		}

		file_put_contents(Meow::CONFIG_FILE_PATH, json_encode($config, JSON_UNESCAPED_SLASHES));

		header('Location: /admin/');
		return true;
	}

	public function updateAdminPass () {
		$adminPass = $this->input->post('adminPass');
		if (strlen($adminPass) < 8) {
			header('Location: /admin/');
			return true;
		}

		$adminPassHash = password_hash($adminPass, PASSWORD_BCRYPT);

		$config = $this->getConfig();

		$config['adminPassHash'] = $adminPassHash;

		file_put_contents(Meow::CONFIG_FILE_PATH, json_encode($config, JSON_UNESCAPED_SLASHES));

		header('Location: /admin/');
		return true;
	}

	public function about () {

		$sql = " select * from page where user_id = 0 and name = 'about/' ";
		$aboutPage = $this->db->query($sql)->result();

		$sql = " select * from page where user_id = 0 and name like 'about/%' and name != 'about/' ";
		$pages = $this->db->query($sql)->result();

		$this->display('/admin/about.twig', [
			'aboutPage' => $aboutPage,
			'pages' => $pages,
		]);
	}

	public function editAbout ($name = false) {

	}

}