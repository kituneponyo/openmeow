<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends MY_Controller {

	public function __construct () {
		parent::__construct();
	}

	public function index () {

		$me = $this->getMe();
		// ログインしてる人は登録できない
		if ($me) {
			header('Location: /');
			exit;
		}

		// 本日の登録者数
		$today = date('Y-m-d');
		$sql = " select count(id) as count from user where create_at > '{$today}' and mid not like '%@%' ";
		$row = $this->db->query($sql)->row();
		$count = $row->count;

		$this->display('register/index.twig', [
			'today_register_count' => $count,
		]);
	}

	public function invite ($mid, $key) {

		if (!$mid || !$key) {
			header('Location: /register');
			return true;
		}

		$me = $this->getMe();

		$sql = " select * from user where mid = ? and invite_key = ? ";
		$user = $this->db->query($sql, [$mid, $key])->row();

		$user->prof_path = MeowManager::getProfPath($user->id);
		$user->icon_path = $user->icon ? $user->prof_path . '/' . $user->icon : '';

		$this->display('register/index.twig', [
			'invite_user' => $user,
		]);
		return true;
	}

	public function doRegist () {

		// ログインしてる人は登録できない
		$this->getMe() && $this->toTop();

		$mid = $this->input->post('mid');
		$name = $this->input->post('name');
		$password = $this->input->post('password');
		$respect_cat = $this->input->post('respect_cat');
		$invite_user_id = intval($this->input->post('invite_user_id'));
		$invite_key = $this->input->post('invite_key');

		if ($invite_user_id && $invite_key) {
			$sql = " select * from user where id = ? and invite_key = ? ";
			$invite_user = $this->db->query($sql, [$invite_user_id, $invite_key])->row();
			$invite_user->prof_path = MeowManager::getProfPath($invite_user_id);
			$invite_user->icon_path = $invite_user->icon ? $invite_user->prof_path . '/' . $invite_user->icon : '';
		}

		$errors = [];

		if (!$mid) {
			$errors[] = 'meow_id を入力してください。';
		}
		if (!$name) {
			$errors[] = 'ユーザー名を入力してください。';
		}
		if (!$password) {
			$errors[] = 'パスワードを入力してください。';
		}
		if (!$respect_cat) {
			$errors[] = '猫と和解してください。';
		}

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $mid)) {
			$errors[] = 'meow_id は英数字、半角アンダーバーのみ利用可能です。';
		}

		// mid 重複チェック
		$sql = " select * from user where mid = ? ";
		if ($row = $this->db->query($sql, [$mid])->row()) {
			$errors[] = '指定の meow_id は既に使われています';
		}

		if ($errors) {
			$this->twig->display('register/index.twig', [
				'mid' => $mid,
				'name' => $name,
				'password' => $password,
				'errors' => $errors,
				'invite_user' => $invite_user ?? false,
			]);
			return;
		}

		$auth_key = md5(microtime() . Meow::SALT);

		// 招待者
		if ($invite_user_id && $invite_key) {
			$sql = " select * from user where id = ? and invite_key = ? ";
			$invite_user = $this->db->query($sql, [$invite_user_id, $invite_key])->row();
		} else {
			$invite_user = false;
		}

		// RSA key
		$this->load->library('Signature');
		$keyPair = Signature::generateKeyPair();

		$password_hash = password_hash($password, PASSWORD_BCRYPT);

		$values = [
			'mid' => $mid,
			'name' => $name,
			'password_hash' => $password_hash,
			'auth_key' => $auth_key,
			'respect_cat' => $respect_cat,
			'follows' => $invite_user ? 1 : 0,
			'followers' => $invite_user ? 1 : 0,
			'privkey' => $keyPair['privkey'],
			'pubkey' => $keyPair['pubkey'],
		];
		$this->db->insert('user', $values);

		$user_id = $this->db->insert_id();

		// 招待の場合
		if ($invite_user) {
			$this->db->insert('follow', [
				'user_id' => $user_id,
				'follow_user_id' => $invite_user_id
			]);
			$this->db->insert('follow', [
				'user_id' => $invite_user_id,
				'follow_user_id' => $user_id
			]);
		}

		// ログイン
		setcookie('mid', $mid, time()+60*60*24*365, '/');
		setcookie('auth', $auth_key, time()+60*60*24*365, '/');

		// 鳴き声設定
		setcookie('useCrying', ($this->input->post('crying') == "1"), time()+60*60*24*365, '/');

		// user.json 作る
		MeowManager::createUserInfoJson($user_id);

		header('location: /');
		exit;
	}
}
