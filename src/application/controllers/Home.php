<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller {

	public function __construct () {
		parent::__construct();
	}

	public function index () {

		global $meowConfig;
		if (empty($meowConfig->dbHost)) {
			header('location: /init');
			exit;
		}

		$me = $this->getMe();

		$q = $this->input->post_get('q');
		if ($t = $this->input->post_get('t')) {
			$q = '#' . $t;
		}
		$lastUpdate = date('Y-m-d', strtotime($q ? ' - 1 year ' : ' - 1 day '));

		$tl = $_GET['tl'] ?? '';

		if ($me && in_array($tl, ['p', 'l', 'h', 'e', 'r', 'n'])) {
			setcookie('tl', 'l', time() + 60 * 60 * 24 * 30 * 12, '/');
		} else {
			$tl = $_COOKIE['tl'] ?? 'p';
			setcookie('tl', $tl, time()+60*60*24*30*12, '/');
		}

		if ($me) {
			if ($tl == 'r') {
				$meows = MeowManager::getNotices($me, $lastUpdate, $q);
				$me = MeowManager::checkNotice($me);
			} elseif ($tl == 'n') {
				$meows = MeowManager::getNotices($me, $q);
				$me = MeowManager::checkNotice($me);
			} else {
				$meows = [];
			}
		} else {
			$filePath = $_SERVER['DOCUMENT_ROOT'] . "/latest.json";
			if (is_file($filePath)) {
				$meows = json_decode(file_get_contents($filePath));
				foreach ($meows as $i => $meow) {
					if ($meow->is_sensitive) {
						unset($meows[$i]);
					}
					if ($meow->user_id == 1) {
						unset($meows[$i]);
					}
				}
				$meows = array_merge($meows);
			} else {
				$meows = [];
			}
		}

		// フォローリクエスト
		$followRequestCount = 0;
		if ($me) {
			$sql = " select count(id) as count from follow where follow_user_id = ? and is_accepted = 0 ";
			if ($row = $this->db->query($sql, [$me->id])->row()) {
				$followRequestCount = $row->count;
			}
		}

		return $this->display('index.twig', [
			'me' => $me,
			'tl' => $tl,
			'meows' => $meows,
			'q' => $q,
			'enableMeowForm' => 1,
			'head_prefix' => 'website',
			'followRequestCount' => $followRequestCount,
		]);
	}

	public function checkReply ($time) {
		$me = $this->getMe();
		print MeowManager::checkReply($me, $time);
	}

	public function notfound () {
		$this->display('errors/notfound.twig', [
		]);
	}
}
