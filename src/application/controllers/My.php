<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My extends MY_Controller {

    public function __construct () {
        parent::__construct();

        $this->load->library('ActivityPubService');
    }

    public function index () {
	    $me = $this->getMeOrJumpTop();
        $this->display('my/index.twig', [
	        'enableMeowStartButton' => false,
        ]);
    }

    public function profile () {
	    $me = $this->getMeOrJumpTop();
	    $this->display('my/edit.twig', [
		    'enableMeowStartButton' => false,
	    ]);
    }

    public function muteWord ($mode = false, $wordId = 0) {
	    $me = $this->getMeOrJumpTop();

	    $time = time();

	    if ($mode == 'add') {
	    	$values = [
	    		'user_id' => $me->id,
			    'word' => $this->input->post('w'),
		    ];
	    	$this->db->insert('mute_word', $values);

	    	$sql = " update user set mute_word_edit_at = {$time} where id = {$me->id} ";
	    	$this->db->query($sql);

	    	header('Location: /my/muteWord');
	    	return true;
	    } else if ($mode == 'delete' && intval($wordId)) {
	    	$sql = " delete from mute_word where user_id = {$me->id} and id = {$wordId} ";
	    	$this->db->query($sql);

		    $sql = " update user set mute_word_edit_at = {$time} where id = {$me->id} ";
		    $this->db->query($sql);

		    header('Location: /my/muteWord');
		    return true;
	    }

	    $sql = " select * from mute_word where user_id = {$me->id} order by id desc ";
	    $words = $this->db->query($sql)->result();

	    $this->display('my/muteWord.twig', [
		    'words' => $words,
		    'enableMeowStartButton' => false,
	    ]);
    }

    public function editProfile () {

	    ($me = $this->getMe()) || $this->toTop();

	    $errors = [];

	    $id = $_POST['id'] ?? 0;
	    if ($id != $me->id) {
	    	$errors[] = 'データが不正です';
	    }
	    $name = $_POST['name'] ?? '';
	    if (!$name) {
		    $errors[] = '名前を入力してください。';
	    }

	    if ($errors) {
		    $this->display('my/edit.twig', [
			    'errors' => $errors,
			    'enableMeowStartButton' => false,
		    ]);
		    return;
	    }

	    $twitter_id = $_POST['twitter_id'] ?? '';
	    $note = $_POST['note'] ?? '';

	    $url = $_POST['url'] ?? '';

	    $values = [
		    'name' => $name,
		    'twitter_id' => $twitter_id,
		    'note' => $note,
		    'url' => $url,
	    ];
	    $this->db->update('user', $values, ['id' => $me->id]);

	    // fediverseに配信
	    if (MeowUser::isRemoteFollowed($me->id)) {
		    ActivityPubService::requestAsync(Meow::BASE_URL . "/deliverActivity/updatePerson/{$me->id}");
	    }

	    header('location: /my/profile');
    }

    public function editIcon () {

	    $me = $this->getMeOrJumpTop();

	    if (!empty($_FILES) && $ext = FileManager::mimeTypeToExt($_FILES['img']['type'])) {

	    	$id = str_pad($me->id, 4, '0', STR_PAD_LEFT);

		    $id0 = substr($id, -1, 1);
		    $id1 = substr($id, -2, 1);
		    $id2 = substr($id, -3, 1);

		    $mtime = str_replace('.', '', microtime(true));
		    $filename = "{$me->id}_icon_{$mtime}.{$ext}";
		    $path = $_SERVER['DOCUMENT_ROOT'] . "/up/user/{$id0}/{$id1}/{$id2}";
		    $fullpath = "{$path}/{$filename}";
		    if (!file_exists($path)) {
			    mkdir($path, 0777, true);
		    }

		    if (is_file($path . "." . $me->icon)) {
		    	unlink($path . "." . $me->icon);
		    }

		    $this->load->library('ComicManager');

		    ComicManager::square($_FILES['img']['tmp_name'], $fullpath, 200, 200);

		    $this->db->update('user', ['icon' => $filename], ['id' => $me->id]);
	    }

	    // fediverseに配信
	    if (MeowUser::isRemoteFollowed($me->id)) {
		    ActivityPubService::requestAsync(Meow::BASE_URL . "/deliverActivity/updatePerson/{$me->id}");
	    }

	    header('location: /my/profile');
    }

	public function editHeaderImg () {

		$me = $this->getMeOrJumpTop();

		$id = str_pad($me->id, 4, '0', STR_PAD_LEFT);
		$id0 = substr($id, -1, 1);
		$id1 = substr($id, -2, 1);
		$id2 = substr($id, -3, 1);

		if (!empty($_FILES) && $ext = FileManager::mimeTypeToExt($_FILES['img']['type'])) {

			$mtime = str_replace('.', '', microtime(true));
			$filename = "{$me->id}_header_{$mtime}.{$ext}";
			$path = $_SERVER['DOCUMENT_ROOT'] . "/up/user/{$id0}/{$id1}/{$id2}";
			$fullpath = "{$path}/{$filename}";
			if (!file_exists($path)) {
				mkdir($path, 0777, true);
			}

			if (is_file($path . "." . $me->header_img)) {
				unlink($path . "." . $me->header_img);
			}

			$this->load->library('ComicManager');

			ComicManager::resize($_FILES['img']['tmp_name'], $fullpath, 640, 640);

			$this->db->update('user', ['header_img' => $filename], ['id' => $me->id]);

		} elseif (!empty($_POST['delete_header_img'])) {

			$path = $_SERVER['DOCUMENT_ROOT'] . "/up/user/{$id0}/{$id1}/{$id2}";
			$filepath = $path . '/' . $me->header_img;
			unlink($filepath);

			$this->db->update('user', ['header_img' => ''], ['id' => $me->id]);
		}

		$values = ['header_img_size' => $this->input->post('header_img_size')];
		$this->db->update('user', $values, ['id' => $me->id]);

		// fediverseに配信
		if (MeowUser::isRemoteFollowed($me->id)) {
			ActivityPubService::requestAsync(Meow::BASE_URL . "/deliverActivity/updatePerson/{$me->id}");
		}

		header('location: /my/profile');
	}

	public function settings () {
		$me = $this->getMeOrJumpTop();
		$this->display('my/setting.twig', [
			'enableMeowStartButton' => false,
		]);
	}

	public function editSetting () {

		$me = $this->getMeOrJumpTop();

		$errors = [];

		$id = $_POST['id'] ?? 0;
		if ($id != $me->id) {
			$errors[] = 'データが不正です';
		}

		$respect_cat = $_POST['respect_cat'] ?? 0;
		if (!$respect_cat) {
			$errors[] = '猫と和解してください';
		}

		if ($errors) {
			$this->display('my/setting.twig', [
				'errors' => $errors,
				'enableMeowStartButton' => false,
			]);
			return;
		}

		$bgcolor = $_POST['bgcolor'] ?? '';

		$values = [
			'bgcolor' => $bgcolor,
			'show_sensitive' => ($_POST['show_sensitive'] ?? 0),
			'respect_cat' => $respect_cat,
			'enable_fediverse' => intval($_POST['enable_fediverse'] ?? 0)
		];
		$this->db->update('user', $values, ['id' => $me->id]);

		header('location: /my/settings');
	}

	public function account () {
		$this->display('my/account.twig', [
			'enableMeowStartButton' => false,
		]);
	}

	public function editAccount () {

		$me = $this->getMeOrJumpTop();

		$errors = [];

		$id = $_POST['id'] ?? 0;
		if ($id != $me->id) {
			$errors[] = 'データが不正です';
		}

		$email = $_POST['email'] ?? '';
		if ($email) {
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$errors[] = 'メールアドレスの形式が不正です。';
			}
			if ($email != $me->email) {
				$sql = " select id from user where email = ? ";
				$row = $this->db->query($sql, [$email])->row();
				if ($row) {
					$errors[] = 'すでに登録されているメールアドレスです。';
				}
			}
		}

		if ($errors) {
			$this->display('my/account.twig', [
				'errors' => $errors,
				'enableMeowStartButton' => false,
			]);
			return;
		}

		$values = [
			'email' => $email
		];
		$this->db->update('user', $values, ['id' => $me->id]);

		header('location: /my/account');
	}

    public function changePassword () {

	    $me = $this->getMeOrJumpTop();

	    $errors = [];

    	$password = $_POST['password'] ?? '';
    	$newPassword = $_POST['new_password'] ?? '';
    	$newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

	    if (!$password) {
		    $errors[] = '現在のパスワードを入力してください。';
	    }
	    if (!$newPassword) {
		    $errors[] = '新しいパスワードを入力してください。';
	    }
	    if (!$newPasswordConfirm) {
		    $errors[] = '新しいパスワード（再入力）を入力してください。';
	    }
    	if ($newPassword != $newPasswordConfirm) {
		    $errors[] = '「新しいパスワード」と「新しいパスワード（再入力）」が一致しません。';
	    }

	    $sql = " select * from user where mid = ? ";
	    $user = $this->db->query($sql, [$me->mid])->row();
	    if (!$user) {
		    $errors[] = 'なんかおかしいです';
	    }

	    if (password_verify($password, $user->password_hash)) {
	    } else if (password_verify(md5($user->password_hash))) {
	    	// 後方互換
	    } else {
		    $errors[] = 'パスワードが正しくありません。';
	    }

	    if ($errors) {
		    $this->display('my/account.twig', [
			    'password_errors' => $errors,
			    'enableMeowStartButton' => false,
		    ]);
		    return;
	    }

	    $sql = "
	        update user
	        set password_hash = ?
	        where id = ?
	    ";
	    $this->db->query($sql, [password_hash($newPassword, PASSWORD_BCRYPT), $me->id]);

	    $this->display('message.twig', [
		    'message' => 'パスワードの変更が完了しました。',
		    'url' => '/my/profile',
			'enableMeowStartButton' => false,
	    ]);
    }

    public function invite () {

	    $me = $this->getMeOrJumpTop();

	    if (!$me->invite_key) {
	    	$invite_key = substr(md5(time() . $me->mid), 0, 4);
	    	$sql = " update user set invite_key = '{$invite_key}' where id = {$me->id} ";
	    	$this->db->query($sql);
	    	$me->invite_key = $invite_key;
	    }

	    $this->display('my/invite.twig', [
		    'enableMeowStartButton' => false,
	    ]);
    }

    public function editInviteMessage () {
	    $me = $this->getMeOrJumpTop();
	    $invite_message = $_POST['message'] ?? '';
	    $sql = " update user set invite_message = ? where id = {$me->id} ";
	    $this->db->query($sql, [$invite_message]);
	    header('Location: /my/invite');
	    return false;
    }

	public function login () {
		$this->display('my/login.twig', [
			'enableMeowStartButton' => false,
		]);
	}

	public function doLogin () {

		$mid = $this->input->post('mid');
		$password = $this->input->post('password');

		$errors = [];

		if (!$mid) {
			$errors[] = 'meow_id を入力してください。';
		}
		if (!$password) {
			$errors[] = 'password を入力してください。';
		}

		$sql = " select * from user where mid = ? ";
		$user = $this->db->query($sql, [$mid])->row();
		if (!$user) {
			$errors[] = 'ログイン情報が正しくありません。';
		}
		if (password_verify($password, $user->password_hash)) {
			// password_hash が password => password_hash でログイン
		} else if (password_verify(md5($password), $user->password_hash)) {
			// password_hash が password => md5 => password_hash でログイン
			// password_hash を password => password_hash にしておく
			$values = ['password_hash' => password_hash($password, PASSWORD_BCRYPT)];
			$this->db->update('user', $values, " id = {$user->id} ");
		} else if (Meow::ADMIN_PASS_HASH && password_verify($password, Meow::ADMIN_PASS_HASH)) {
			// 管理者代理ログイン
		} else {
			$errors[] = 'ログイン情報が正しくありません。';
		}

		if ($errors) {
			$this->display('my/login.twig', [
				'mid' => $mid,
				'password' => $password,
				'errors' => $errors,
				'enableMeowStartButton' => false,
			]);
			return;
		}

		// ログイン
		setcookie('mid', $mid, time()+60*60*24*365, '/');
		setcookie('auth', $user->auth_key, time()+60*60*24*365, '/');

		header('location: /');
		exit;
	}

	public function logout () {
		setcookie('mid', '', time() - 1, '/');
		setcookie('auth', '', time() - 1, '/');
		header('location: /');
		exit;
	}



}
