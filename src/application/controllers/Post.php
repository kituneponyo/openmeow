<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Meow\ActivityPub;
use Meow\ActivityPub\Activity\CreateActivity;

class Post extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    private function getFileDir () {
	    $Y = date('Y');
	    $m = date('m');
	    $d = date('d');
	    return "/up/{$Y}/{$m}/{$d}";
    }
    private function createFileDir () {
	    $path = $_SERVER['DOCUMENT_ROOT'] . $this->getFileDir();
	    if (!file_exists($path)) {
		    mkdir($path, 0777, true);
	    }
	    return $path;
    }

    private function getUaId () {
	    if ($ua = ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
	    	$sql = " select id from ua where ua = ? ";
	    	if ($row = $this->db->query($sql, [$ua])->row()) {
	    		return $row->id;
		    }
	    	$this->db->insert('ua', ['ua' => $ua]);
	    	return $this->db->insert_id();
	    }
	    return 0;
    }

    private function getHostId () {
	    if ($host = gethostbyaddr($_SERVER['REMOTE_ADDR'] ?? '')) {
		    $sql = " select id from host where host = ? ";
		    if ($row = $this->db->query($sql, [$host])->row()) {
			    return $row->id;
		    }
		    $this->db->insert('host', ['host' => $host]);
		    return $this->db->insert_id();
	    }
	    return 0;
    }

	/**
	 * meow投稿
	 * @return bool
	 */
    public function index () {

	    $this->load->library('ComicManager');

    	$mode = $this->input->post('mode');

    	$text = $this->input->post('text');
	    $text = str_replace('<', '&lt;', $text);
	    $text = str_replace('>', '&gt;', $text);

	    if (!$text && empty($_FILES['img']['name']) && empty($_POST['img1base64']) && empty($_POST['newFiles'])) {
		    header('location: /');
		    return false;
	    }

        $me = $this->getMeOrJumpTop();

	    // 二重投稿禁止
	    if ($text) {
	    	$sql = " select * from meow where user_id = {$me->id} and is_deleted = 0 order by id desc limit 1 ";
	    	$myLastMeow = $this->db->query($sql)->row();
	    	if ($myLastMeow && $myLastMeow->text == $text) {
	    		if ($mode == 'api') {
	    			print '';
			    } else {
				    header('location: /');
			    }
			    return false;
		    }
	    }

	    $mtime = str_replace('.', '', microtime(true));

	    $finfo = finfo_open(FILEINFO_MIME_TYPE);

	    $newFileInfos = [];
	    $filenames = [];
	    $orgfilenames = [];

	    // 新しい複数画像アップロード
	    if (!empty($_POST['newFiles'])) {
	    	$datas = explode('|', $_POST['newFiles']);
		    $orgNames = explode('|', $_POST['newFileNames'] ?? '');
	    	foreach ($datas as $i => $data) {
			    // base64デコード
			    if (strpos($data, ",") === false) {
			    	continue;
			    }
			    list($base64header, $base64data) = explode(',', $data);
			    $data = base64_decode($base64data);

			    // finfo_bufferでMIMEタイプを取得
			    $mime_type = finfo_buffer($finfo, $data);

			    //MIMEタイプをキーとした拡張子の配列
			    if ($ext = FileManager::mimeTypeToExt($mime_type)) {

				    //MIMEタイプから拡張子を選択してファイル名を作成
				    $_filename = "{$me->id}_{$mtime}_{$i}.{$ext}";
				    $dir = $this->createFileDir();
				    $fullpath = $dir . '/' . $_filename;

				    // 画像ファイルの保存
				    file_put_contents($fullpath, $data);

				    if (in_array($ext, ['jpg', 'png', 'gif'])) {

					    if (filesize($fullpath) > 1024 * 1024) {
						    ComicManager::resize($fullpath, $fullpath, 1024, 1024);
					    } elseif ($ext == 'jpg') {
						    $exif = exif_read_data($fullpath);
						    if (isset($exif['Orientation'])) {
							    ComicManager::imageOrientation($fullpath, $exif['Orientation']);
						    }
					    }
					    // 100KBより大きければサムネ作る
					    $thumbPath = "{$dir}/{$me->id}_{$mtime}_{$i}_t.{$ext}";
					    if (filesize($fullpath) > 1024 * 100) {
						    ComicManager::resize($fullpath, $thumbPath, 560, 560);
					    } else {
					    	symlink($fullpath, $thumbPath);
					    }
				    }

				    $filenames[] = $_filename;
				    $orgfilenames[] = $orgNames[$i];
				    $newFileInfos[] = [
				    	'dir' => pathinfo($fullpath, PATHINFO_DIRNAME),
				    	'filename' => $_filename,
				    	'ext' => $ext,
					    'orgname' => $orgNames[$i],
					    'size' => filesize($fullpath),
				    ];
			    }
		    }
	    }

	    $reply_to = intval($_POST['reply_to'] ?? 0);

	    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

	    $is_private = $_POST['private'] ?? 0;

	    if ($is_private < 4) {
		    setcookie('post_is_private', 0, time()+60*60*24*365, '/');
	    }

	    $is_sensitive = !empty($_POST['is_sensitive']) ? 1 : 0;

	    $create_at = date('Y-m-d H:i:s');

	    // host
	    $hostId = $this->getHostId();

	    // ua
	    $uaId = $this->getUaId();

	    $values = [
        	'user_id' => $me->id,
		    'summary' => ($is_sensitive ? $this->input->post('summary') : ''),
	        'text' => $text,
	        'ipint' => ip2long($ip),
		    'host_id' => $hostId,
		    'ua_id' => $uaId,
		    'files' => implode(',', $filenames),
		    'orgfiles' => implode(',', $orgfilenames),
		    'reply_to' => $reply_to,
		    'is_private' => $is_private,
		    'is_sensitive' => $is_sensitive,
		    'is_paint' => intval($this->input->post('is_paint')),
		    'has_thumb' => 1,
		    'create_at' => $create_at,
        ];
        $this->db->insert('meow', $values);

        $meow_id = $this->db->insert_id();

	    // meow内容からリプライ先を取得
	    $matches = [];
	    $reply_user_ids = [];
	    if (preg_match_all('/@([a-zA-Z0-9_]+)/', $text, $matches)) {
	    	// meow_id から user_id 取得
		    $sql = " select id from user where mid in ? ";
		    $rows = $this->db->query($sql, [$matches[1]])->result();
		    foreach ($rows as $row) {
		    	$reply_user_ids[] = $row->id;
		    }
	    }

	    // リプライ先のmeowがある場合
	    $mutes = [];
	    if ($reply_to) {
	    	// リプライ先のユーザ取得
		    $sql = "
				select
					m.user_id,
					n.object_id as note_id,
					n.actor_id
				from
					meow m
					left outer join ap_note n 
						on n.id = m.ap_note_id
				where
					m.id = ?
			";
		    $reply_meow = $this->db->query($sql, [$reply_to])->row();
		    if (!in_array($reply_meow->user_id, $reply_user_ids)) {
			    $reply_user_ids[] = $reply_meow->user_id;
		    }
		    // ミュート情報
		    $sql = " select * from mute where user_id = {$reply_meow->user_id} and mute_user_id = {$me->id} ";
		    if ($mute = $this->db->query($sql)->row()) {
			    $mutes[$reply_meow->user_id] = $mute;
		    }
		    if (!$mute) {
			    // リプライ先のreply_count更新
			    MeowManager::incrementReplyCount($reply_to);
		    }

		    // リプライ先が remote note の場合
		    if ($reply_meow->note_id) {
		    	$values = [
		    		'reply_to_note_id' => $reply_meow->note_id,
				    'reply_to_actor_id' => $reply_meow->actor_id,
			    ];
		    	$where = " id = {$meow_id} ";
		    	$this->db->update('meow', $values, $where);
		    }
	    }

	    if ($reply_user_ids) {
		    foreach ($reply_user_ids as $reply_user_id) {
		    	// ミュートしてたら関係ない
		    	if (isset($mutes[$reply_user_id])) {
		    		continue;
			    }
			    $values = [
				    'meow_id' => $meow_id,
				    'user_id' => $me->id,
				    'reply_user_id' => $reply_user_id
			    ];
			    $this->db->insert('reply', $values);

			    // 通知
			    $values = [
			    	'from_user_id' => $me->id,
			    	'to_user_id' => $reply_user_id,
				    'type' => 1, // reply = 1
				    'object_id' => $meow_id,
				    'create_at' => $create_at,
				    'meow_id' => $meow_id,
			    ];
			    $this->db->insert('notice', $values);

			    // リプライ先の未読件数++
			    $sql = " 
                    update user 
                    set unread_reply_count
                    	= case
                    		when unread_reply_count < 255 then unread_reply_count + 1 
                    		else 255
                    		end
                    where id = {$reply_user_id}
                ";
			    $this->db->query($sql);

			    // リプライ先ユーザ情報静的化
			    MeowManager::createUserInfoJson($reply_user_id);
		    }
	    }

	    // 貼付画像
	    if ($newFileInfos) {
	    	foreach ($newFileInfos as $info) {
	    		$info['user_id'] = $me->id;
	    		$info['meow_id'] = $meow_id;
	    		$this->db->insert('file', $info);
		    }
	    }

	    // activitypub
	    $this->load->library('ActivityPubService');
	    $meow = MeowManager::getMeow($meow_id, $me);

	    // オープン発言で、リモートフォローされていれば
	    if ($meow->is_private == 0 && MeowUser::isRemoteFollowed($me->id)) {
	    	$needDeliver = false;
	    	if ($reply_user_ids && $me->id == 1) {
	    		// リプライ先はリモートユーザーか？
			    $sql = " select id from user where id in ? and actor != '' limit 1 ";
			    if ($this->db->query($sql, [$reply_user_ids])->row()) {
				    $needDeliver = true;
			    }
		    } else {
	    		// 全体発言なら配信
			    $needDeliver = true;
		    }

		    // Noteを配信
		    if ($needDeliver) {

//		    	$_meow = Meow::load($meow);
//			    $activity = $_meow->createActivity();
//			    $apActivityId = ActivityPub\Activity\Activity::insert($me->id, $activity);
//			    ActivityPubService::insertDeliverQueue($me->id, $apActivityId);
//			    ActivityPubService::requestAsync(Meow::BASE_URL . "/cron/deliverActivity");

			    ActivityPubService::requestAsync(Meow::BASE_URL . "/deliverActivity/createNote/{$me->id}/{$meow->id}");
		    }
	    }

	    // 新着変更
	    MeowManager::createLatestJson();

	    // urlが含まれてる？


	    if ($mode == 'api') {
	    	print json_encode($meow);
	    	return true;
	    } else {
		    if ($reply_to) {
			    header("location: /p/{$me->mid}/{$reply_to}");
		    } else {
			    header('location: /');
		    }
	    }
    }

    public function delete () {

    	$id = $_POST['id'] ?? 0;
    	if (!$id) {
    		print 0;
    		exit;
	    }

    	$me = $this->getMe();

    	// 自分自身のmeowだけ削除できる
    	$sql = " select * from meow where id = ? and user_id = ? ";
	    $meow = $this->db->query($sql, [$id, $me->id])->row();
	    if (!$meow) {
	    	print 0;
	    	exit;
	    }

	    // ユーザー情報
	    $sql = " select * from user where id = ? limit 1 ";
	    $user = $this->db->query($sql, [$meow->user_id])->row();

    	$sql = " update meow set is_deleted = 1 where id = ? and user_id = ? ";
    	$this->db->query($sql, [$id, $me->id]);

    	// リプライ先がある場合
	    if ($meow->reply_to) {

		    // リプライ先情報取得
		    $sql = " select * from meow where id = {$meow->reply_to} ";
		    $reply_meow = $this->db->query($sql)->row();
		    // リプライ先にミュートされてる？
		    $sql = " select * from mute where user_id = {$reply_meow->user_id} and mute_user_id = {$me->id} ";
		    $mute = $this->db->query($sql)->row();

		    // どくさいmeowでない、ミュートされてない
		    if ($meow->is_private < 4 && !$mute) {
			    // リプライ先のreply_count更新
			    MeowManager::decrementReplyCount($meow->reply_to);
		    }
	    }

	    // dmの場合
	    if ($meow->dm_id) {
	    	$sql = " update dm set is_deleted = 1 where user_id = {$me->id} and id = {$meow->dm_id} ";
	    	$this->db->query($sql);
	    }

	    // notice削除
	    $sql = " delete from notice where meow_id = {$meow->id} ";
	    $this->db->query($sql);

	    // パブリックTLに出るものなら新着変更
	    if (!$meow->reply_to && !$meow->is_private) {
		    MeowManager::createLatestJson();
	    }

	    // activitypub
	    if (MeowUser::isRemoteFollowed($me->id) && $meow->is_private == 0) {
		    $this->load->library('ActivityPubService');

		    $meow = Meow::load($meow);

//		    $activity = $meow->deleteActivity();
//		    $apActivityId = ActivityPub\Activity\Activity::insert($me->id, $activity);
//		    ActivityPubService::insertDeliverQueue($me->id, $apActivityId);
//		    ActivityPubService::requestAsync(Meow::BASE_URL . "/cron/deliverActivity");

		    ActivityPubService::requestAsync(Meow::BASE_URL . "/deliverActivity/deleteNote/{$me->id}/{$meow->id}");
	    }

    	print 1;
    }

    public function detail ($id, $mid = '', $mode = '') {

    	$id || $this->toTop();

	    // meowの公開範囲
	    if ($me = $this->getMe()) {
		    // 自分かフォロアー
		    $where_private = "
				(
					m.is_private = 0
					or m.user_id = {$me->id}
					or (
						m.is_private < 4 
						and m.user_id in (select follow_user_id from follow where user_id = {$me->id})
					)
				)
			";
	    } else {
		    // 全体
		    $where_private = " m.is_private = 0 ";
	    }

	    $sql = "
			select
				m.*,
				u.mid,
				u.name,
				u.twitter_id,
				u.icon,
				ru.mid as reply_mid,
				n.object_id as ap_note_object_id,
				n.object as ap_note_object
			from
				meow m 
				inner join user u
					on u.id = m.user_id
				left outer join meow r
					on r.id = m.reply_to
				left outer join user ru
					on ru.id = r.user_id
				left outer join ap_note n 
					on n.id = m.ap_note_id
			where
				m.id = ?
				and m.is_deleted = 0
				and {$where_private}
			limit 1
		";
	    $meow = $this->db->query($sql, [$id])->row();
	    if (!$meow) {
		    if ($mode == 'activity') {
		    	http_response_code(404);
		    	print "404 not found";
		    } else {
		    	header('Location: /');
		    }
	    	exit;
	    }

	    $meow = MeowManager::decorate($meow);

	    if ($meow->ap_note_object) {
	    	$object = json_decode($meow->ap_note_object);
	    	if (!empty($object->tag)) {
	    		foreach ($object->tag as $tag) {
	    			if ($tag->type == 'Emoji') {
	    				if (!empty($tag->icon) && !empty($tag->icon->url)) {
	    					$meow->text = str_replace($tag->name, '<img class="meow-text-emoji" src="' . $tag->icon->url . '">', $meow->text);
					    }
				    }
			    }
		    }
	    }


	    // 親がある場合
	    if ($meow->reply_to) {
		    $parent = MeowManager::getMeow($meow->reply_to, $me);
	    }

	    $replies = MeowManager::getMeowReplies($me, $id);

	    if ($mode == 'activity') {
		    $this->load->library('ActivityPubService');
		    $createNoteActivity = CreateActivity::noteFromMeow($meow);
		    print $createNoteActivity->json();
	    	exit;
	    }

	    $this->display('post/detail.twig', [
		    'me' => $me,
		    'parent' => $parent ?? false,
		    'meow' => $meow,
		    'replies' => $replies,
		    'enableSearch' => 0,
	    ]);
    }



}
