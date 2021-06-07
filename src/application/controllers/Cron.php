<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

	/**
	 * 積まれた Activity を配信する
	 */
    public function deliverActivity () {

    	$me = $this->getMe();
    	if ($me && $me->id == 1) {

	    } else {
		    // 必ず呼び出す
		    ob_start();

		    /// レスポンスを返す
		    echo 1;

		    /// TCP接続終了のヘッダー送信
		    header('Connection: close');
		    header('Content-Length: '.ob_get_length());
		    // とりあえず全てを出力
		    ob_end_flush();
		    ob_flush();
		    flush();
	    }

	    ini_set('display_errors', "on");

	    $this->load->library('ActivityPubService');

    	$sql = "
			select
				q.id as queue_id,
				activity.*,
				u.mid,
				s.shared_inbox
			from
				ap_deliver_queue q
				inner join ap_activity activity
					on activity.id = q.ap_activity_id
				inner join follow f
					on f.follow_user_id = q.user_id
				inner join user u
					on u.id = f.follow_user_id
				inner join user ru
					on ru.id = f.user_id
					and ru.actor != ''
				inner join ap_actor actor
					on actor.actor = ru.actor
				inner join ap_service s
					on s.host = actor.host
					and s.enable_deliver = 1
			order by
				activity.object_id asc
    	";
    	if ($rows = $this->db->query($sql)->result()) {
    		foreach ($rows as $row) {

			    $activity = json_decode($row->object);

			    // 全体向けなら shared_inbox にぶちこむ
			    if (isset($activity->object->to)
				    && $row->shared_inbox
			        && in_array('https://www.w3.org/ns/activitystreams#Public', $activity->object->to)
			    ) {
			    	$response = ActivityPubService::safe_remote_post($row->shared_inbox, $row->object, $row->mid);

				    ActivityPubService::logDeliver($row->shared_inbox, $row->id, $activity, $response);

			    	if ($response->getStatusCode() == 202) {
					    // キューを削除
					    $sql = " delete from ap_deliver_queue where id = ? ";
					    $this->db->query($sql, [$row->queue_id]);

					    // activityのキャッシュを削除
					    $sql = " delete from ap_activity where id = ? and type in ('Create', 'Delete') ";
					    $this->db->query($sql, [$row->id]);
				    }
			    }

		    }
	    }

	    print "ok";

    }

}
