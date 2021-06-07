<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/05/12
 * Time: 6:22
 */


class DeliverActivity extends MY_Controller
{
	public function __construct () {
		parent::__construct();

		$this->load->library('ActivityPubService');

		$me = $this->getMe();

		// 自前限定
		if (($me && $me->is_admin) || $_SERVER['REMOTE_ADDR'] != file_get_contents(Meow::BASE_URL . '/api/ip')) {
			http_response_code(403);
			exit;
		}
	}

	private function getDeliverDests (int $userId) {
		$sql = "
			select
				s.shared_inbox
			from
				follow f
				inner join user ru
					on ru.id = f.user_id
					and ru.actor != ''
				inner join ap_actor actor
					on actor.actor = ru.actor
				inner join ap_service s
					on s.host = actor.host
					and s.enable_deliver = 1
		  	where
		  		f.follow_user_id = ?
		  		and s.shared_inbox != ''
		  	group by s.shared_inbox
    	";
		return $this->db->query($sql, [$userId])->result();
	}

	private function returnResponse () {
		// 必ず呼び出す
		ob_start();

		/// レスポンスを返す
		echo 1;

		/// TCP接続終了のヘッダー送信
		header('Connection: close');
		header('Content-Length: '.ob_get_length());
		// とりあえず全てを出力
		@ob_end_flush();
		@ob_flush();
		@flush();
	}

	private function deliver ($userId, $mid, $activity, $to) {
		if (is_array($activity)) {
			$jActivity = json_encode($activity, JSON_UNESCAPED_SLASHES);
			$activity = json_decode($jActivity);
		}
		if ($rows = $this->getDeliverDests($userId)) {
			foreach ($rows as $row) {
				// 全体向けなら shared_inbox にぶちこむ
				if (in_array('https://www.w3.org/ns/activitystreams#Public', $to)) {
					$response = ActivityPubService::safe_remote_post($row->shared_inbox, $activity, $mid);

					MeowLogger::logDeliver($row->shared_inbox, '', $activity, $response);
				}
			}
		}
	}

	public function createNote (int $userId, int $id) {

		$meow = Meow::load($id);
		if (!$meow) {
			http_response_code(403);
			exit;
		}

		// 本人限定
		if ($meow->user_id != $userId) {
			http_response_code(403);
			exit;
		}

		// 重い処理の前にレスポンスを返してしまう
		$this->returnResponse();

		$activity = $meow->createActivity();

		if (empty($activity->object->to)) {
			exit;
		}

		// 配信
		$this->deliver($userId, $meow->mid, $activity, $activity->object->to);
	}

	public function updatePerson (int $userId) {
		$user = MeowUser::get($userId);
		if (!$user) {
			http_response_code(403);
			exit;
		}

		// 重い処理の前にレスポンスを返してしまう
		$this->returnResponse();

		$activity = MeowUser::updateActivity($user);

		// 配信
		$this->deliver($userId, $user->mid, $activity, $activity->to);

		print "ok";
	}

	public function deleteNote (int $userId, int $id) {

		$meow = Meow::load($id);
		if (!$meow) {
			http_response_code(403);
			exit;
		}

		// 本人限定
		if ($meow->user_id != $userId) {
			http_response_code(403);
			exit;
		}

		// 重い処理の前にレスポンスを返してしまう
		$this->returnResponse();

		$activity = $meow->deleteActivity();

		// 配信
		$this->deliver($userId, $meow->mid, $activity, $activity->object->to);
	}

}