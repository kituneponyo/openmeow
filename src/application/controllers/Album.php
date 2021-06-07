<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Album extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index($mid, int $albumId)
	{
		$me = $this->getMe();

		$sql = " select * from user where mid = ? ";
		$user = $this->db->query($sql, [$mid])->row();

		if (!$user) {
			header('Location: /');
			exit;
		}

		$user->prof_path = MeowManager::getProfPath($user->id);
		$user->icon_path = $user->icon ? "{$user->prof_path}/{$user->icon}" : '/assets/icons/cat_footprint.png';
		$user->icon_media_type = mime_content_type($_SERVER['DOCUMENT_ROOT'] . $user->icon_path);
		$user->note = str_replace(['<', '>'], ['&lt;', '&gt;'], $user->note);

		// フォローしてる？
		if ($me) {
			$sql = " select * from follow where user_id = ? and follow_user_id = ? ";
			$follow = $this->db->query($sql, [$me->id, $user->id])->row();
			$followed = $this->db->query($sql, [$user->id, $me->id])->row();
		} else {
			$follow = [];
			$followed = [];
		}

		// meowの公開範囲
		if ($me) {
			// 自分かフォロアー
			$where_private = "
				(
					(m.is_private <= 4 and m.user_id = {$me->id})
					or m.is_private = 0
					or (m.is_private < 4 and m.user_id in (select follow_user_id from follow where user_id = {$me->id}))
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
				u.icon,
				ru.mid as reply_mid
			from
				album a
				left outer join bookmark b
					on b.album_id = a.id
				inner join meow m
					on m.id = b.meow_id
					and m.is_deleted = 0
				inner join user u
					on u.id = m.user_id
				left outer join meow r
					on r.id = m.reply_to
					and r.is_deleted = 0
				left outer join user ru
					on ru.id = r.user_id
			where
				a.id = {$albumId}
				and a.user_id = ?
				and {$where_private}
			order by m.create_at desc
			limit 100
		";
		$meows = $this->db->query($sql, [$user->id])->result();
		foreach ($meows as $i => $meow) {
			$meows[$i] = MeowManager::decorate($meow);
		}

		$sql = " select * from album where user_id = {$user->id} and id = {$albumId} and is_public = 1 ";
		$album = $this->db->query($sql)->row();

		$this->display('user/index.twig', [
			'mode' => 'albums',
			'user' => $user,
			'bgcolor' => $user->bgcolor,
			'follow' => $follow,
			'followed' => $followed,
			'meows' => $meows,
			'album' => $album,
			'hasPublicAlbum' => 1,
		]);
		return true;
	}
}