<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Init extends MY_Controller {

	public function __construct () {
		parent::__construct();
	}

	public function index () {
		$this->twig->display('init/index.twig');
	}

	public function do () {

		$siteName = $this->input->post('siteName');
		$host = $this->input->post('host');
		$adminPass = $this->input->post('adminPass');

		$dbHost = $this->input->post('dbHost');
		$dbUser = $this->input->post('dbUser');
		$dbPassword = $this->input->post('dbPassword');
		$dbName = $this->input->post('dbName');

		$errors = [];

		if (!$siteName) { $errors[] = 'サイト名を入力してください。'; }
		if (!$host) { $errors[] = 'ホストを入力してください。'; }
		if (!$adminPass) { $errors[] = '管理者パスワードを入力してください。'; }

		if (!$dbHost) { $errors[] = 'データベース接続先ホストを入力してください。'; }
		if (!$dbUser) { $errors[] = 'データベース接続ユーザ名を入力してください。'; }
		if (!$dbPassword) { $errors[] = 'データベース接続パスワードを入力してください。'; }
		if (!$dbName) { $errors[] = 'データベース名を入力してください。'; }

		if ($dbHost && $dbUser && $dbPassword && $dbName) {
			if (!$this->initDbConnect($dbHost, $dbUser, $dbPassword, $dbName)) {
				$errors[] = 'データベースに接続できませんでした。';
			}
		}

		if ($errors) {
			$this->twig->display('init/index.twig', [
				'siteName' => $siteName,
				'host' => $host,

				'dbHost' => $dbHost,
				'dbUser' => $dbUser,
				'dbPassword' => $dbPassword,
				'dbName' => $dbName,

				'errors' => $errors,
			]);
			return;
		}

		ini_set('display_errors', "on");

		$adminPassHash = password_hash($adminPass, PASSWORD_BCRYPT);

		global $meowConfig;
		$configKeyValues = [
			'siteName' => $siteName,
			'FQDN' => $host,
			'adminPassHash' => $adminPassHash,
			'dbHost' => $dbHost,
			'dbUser' => $dbUser,
			'dbPassword' => $dbPassword,
			'dbName' => $dbName,
		];
		foreach ($configKeyValues as $k => $v) {
			$meowConfig->$k = $v;
		}
		file_put_contents(MEOW_CONFIG_FILE_PATH, json_encode($meowConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));


		// テーブル作成
		$this->initDb();


		$this->display('init/complete.twig');
	}

	private function initDbConnect ($dbHost, $dbUser, $dbPassword, $dbName) {

		$dbConfig = [
			'dsn' => '',
			'hostname' => $dbHost,
			'username' => $dbUser,
			'password' => $dbPassword,
			'database' => $dbName,
			'dbdriver' => 'mysqli',
			'dbprefix' => '',
			'pconnect' => FALSE,
			'db_debug' => (ENVIRONMENT !== 'production'),
			'cache_on' => FALSE,
			'cachedir' => '',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_general_ci',
			'swap_pre' => '',
			'encrypt' => FALSE,
			'compress' => FALSE,
			'stricton' => FALSE,
			'failover' => array(),
			'save_queries' => TRUE
		];
		try {
			ini_set('display_errors', "off");
			$this->load->database($dbConfig);
		} catch (\Exception $e) {
			return false;
		} finally {
			ini_set('display_errors', "on");
		}

		if ($this->db->query(' select 1 ')->row()) {
			return true;
		} else {
			return false;
		}

	}

	private function initDb () {

		$sqls = file_get_contents(MEOW_CONFIG_BASE_DIR . "/assets/sql/meow.sql");

		$sqls = explode(';', $sqls);

		foreach ($sqls as $sql) {
			if ($sql) {
				$this->db->query($sql);
			}
		}
	}

	public function register () {
		$this->display('init/register.twig');
	}

	public function doRegister () {

		global $meowConfig;

		$errors = [];

		$mid = $this->input->post('mid');
		$name = $this->input->post('name');
		$password = $this->input->post('password');
		$isAdmin = $this->input->post('isAdmin');
		$adminPass = $this->input->post('adminPass');

		if (!$mid) { $errors[] = 'アカウントIDを入力してください。'; }
		if (!$name) { $errors[] = 'ユーザー名を入力してください。'; }
		if (!$password) { $errors[] = 'パスワードを入力してください。'; }
		if (!$adminPass) { $errors[] = '管理者パスワードを入力してください。'; }

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $mid)) {
			$errors[] = 'アカウントIDは英数字、半角アンダーバーのみ利用可能です。';
		}

		// mid 重複チェック
		$sql = " select * from user where mid = ? ";
		if ($row = $this->db->query($sql, [$mid])->row()) {
			$errors[] = '指定の meow_id は既に使われています';
		}

		if (!password_verify($adminPass, $meowConfig->adminPassHash)) {
			$errors[] = '管理者パスワードが正しくありません。';
		}

		if ($errors) {
			$this->twig->display('init/register.twig', [
				'mid' => $mid,
				'name' => $name,
				'isAdmin' => $isAdmin,
				'errors' => $errors,
			]);
			return;
		}

		ini_set('display_errors', "on");

		// ユーザー作成

		// RSA key
		$this->load->library('Signature');
		$keyPair = Signature::generateKeyPair();

		$password_hash = password_hash($password, PASSWORD_BCRYPT);

		$auth_key = md5(microtime() . $meowConfig->salt);

		$values = [
			'mid' => $mid,
			'name' => $name,
			'password_hash' => $password_hash,
			'auth_key' => $auth_key,
			'privkey' => $keyPair['privkey'],
			'pubkey' => $keyPair['pubkey'],
			'is_admin' => 1,
		];
		$this->db->insert('user', $values);

		header('Location: /init/completeRegister');
	}

	public function completeRegister () {

		$this->display('init/completeRegister.twig');
	}
}
