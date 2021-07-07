<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MeowManager extends LibraryBase
{
	public static function getBasicColumns () {
		return "
			m.id,
			m.reply_to,
			m.user_id,
			m.summary,
			m.text,
			'' as file,
			m.files,
			m.orgfiles,
			m.create_at,
			m.is_private,
			m.is_sensitive,
			m.reply_count,
			m.fav_count,
			m.is_paint,
			m.has_thumb,
			m.ap_object_id,
			u.mid,
			u.name,
			u.icon
		";
	}

	public static function getMeow ($id, $me = false) {

		// meowの公開範囲
		if ($me) {
			// 自分かフォロアー
			$where_private = "
				(
					m.is_private = 0
					or m.user_id = {$me->id}
					or (m.is_private <= 3 and m.user_id in (select follow_user_id from follow where user_id = {$me->id}))
				)
			";
		} else {
			// 全体
			$where_private = " m.is_private = 0 ";
		}

		$basicColumns = self::getBasicColumns();

		$sql = "
			select
				{$basicColumns},
				u.twitter_id,
				ru.mid as reply_mid
			from
				meow m 
				inner join user u
					on u.id = m.user_id
				left outer join meow r
					on r.id = m.reply_to
				left outer join user ru
					on ru.id = r.user_id
			where
				m.id = ?
				and {$where_private}
			limit 1
		";
		$meow = self::db()->query($sql, [$id])->row();
		return $meow ? MeowManager::decorate($meow) : false;
	}

	public static function checkMyPublicTimeLine ($me, $lastUpdate = false, $q = '') {

		if (!$me || !$lastUpdate) {
			return 0;
		}

		$where_sensitive = $me->show_sensitive
			? " "
			: " and m.is_sensitive = 0 ";

		$sql = "
			select m.id
			from meow m 
			where
				m.create_at > '{$lastUpdate}'
				and m.is_private = 0
				and m.is_deleted = 0
				and m.reply_to = 0
				{$where_sensitive}
				and m.user_id != {$me->id}
				and m.user_id not in (select mute_user_id from mute where user_id = {$me->id})
			order by m.create_at desc
			limit 1
		";
		$meow = self::db()->query($sql)->row();
		return $meow ? $meow->id : 0;
	}

	public static function search ($me, $q, $lastUpdate = false) {

		if (!$me) {
			return false;
		}

		$where_sensitive = $me->show_sensitive
			? " "
			: " and m.is_sensitive = 0 ";

		$basicColumns = self::getBasicColumns();

		$sql = "
			select
				{$basicColumns},
				'' as reply_mid
			from
				meow m 
				inner join user u
					on u.id = m.user_id
			where
				m.create_at > ?
				and (m.is_private = 0 or (m.is_private <= 1 and m.user_id = {$me->id}))
				and m.is_deleted = 0
				and m.reply_to = 0
				and (
					m.text like ?
				)
				{$where_sensitive}
				and m.user_id not in (select mute_user_id from mute where user_id = {$me->id})
			order by m.create_at desc
			limit 50
		";
		$meows = self::db()->query($sql, [
			($lastUpdate ? $lastUpdate : date('Y-m-d H:i:s', strtotime(' - 1 year '))),
			"%{$q}%"
		])->result();

		return self::decorateMeows($meows);
	}

	public static function getUser ($mid) {
		$sql = "
			select
				*,
				(actor != '') as is_remote_user 
			from user 
			where mid = ? 
			limit 1
		";
		$user = self::db()->query($sql, [$mid])->row();
		if (!$user) {
			return false;
		}
		if (!$user->is_remote_user) {
			$user->actor = Meow::BASE_URL . "/u/" . $user->mid;
		}
		return $user;
	}

	public static function getUserTimeLine ($me, $user, $mode, $lastUpdate = 0, $q = '', $max = 0) {

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

		if ($mode == 'media') {
			$where_media = " and (m.files != '') ";
		} else {
			$where_media = '';
		}

		if ($q) {
			$where_word = " and m.text like '%{$q}%' ";
		} else {
			$where_word = '';
		}

		$where_max = $max ? " and m.create_at < '{$max}' " : '';

		$sql = "
			select
				m.*,
				u.mid,
				u.name,
				u.icon,
				ru.mid as reply_mid
			from
				meow m 
				inner join user u
					on u.id = m.user_id
				left outer join meow r
					on r.id = m.reply_to
					and r.is_deleted = 0
				left outer join user ru
					on ru.id = r.user_id
			where
				m.user_id = ?
				and m.is_deleted = 0
				and {$where_private}
				{$where_media}
				{$where_word}
				{$where_max}
			order by m.create_at desc
			limit 100
		";
		$meows = self::db()->query($sql, [$user->id])->result();
		foreach ($meows as $i => $meow) {
			$meows[$i] = MeowManager::decorate($meow);
		}

		return $meows;
	}

	public static function getAlbumTimeLine ($me, $params) {

		$lastUpdate = $params['lastUpdate'];
		$q = $params['q'];
		$albumId = $params['albumId'];
		$max = $params['max'];

		if (!$me) {
			return false;
		}

		$params = [
			$albumId,
			($lastUpdate ? $lastUpdate : date('Y-m-d H:i:s', strtotime(' - 1 day ')))
		];

		$where_sensitive = $me->show_sensitive
			? " "
			: " and m.is_sensitive = 0 ";

		if ($q) {
			$where_q = " and (m.text like ? or u.name = ?)";
			$params[] = "%{$q}%";
			$params[] = $q;
		} else {
			$where_q = '';
		}

		$where_max = $max ? " and m.create_at < '{$max}' " : '';

		$basicColumns = self::getBasicColumns();

		$sql = "
			select
				{$basicColumns},
				'' as reply_mid				
			from
				bookmark b
				inner join meow m 
					on m.id = b.meow_id
				inner join user u
					on u.id = m.user_id
			where
				b.album_id = ?
				and m.create_at > ?
				and (m.is_private = 0 or (m.is_private <= 1 and m.user_id = {$me->id}))
				and m.is_deleted = 0
				and m.reply_to = 0
				and m.ap_object_id = 0
				{$where_sensitive}
				{$where_q}
				{$where_max}
				and m.user_id not in (select mute_user_id from mute where user_id = {$me->id})
			order by m.create_at desc
			limit 50
		";
		$meows = self::db()->query($sql, $params)->result();

		return self::decorateMeows($meows);
	}

	public static function getMyPublicTimeLine ($me, $params) {

		$lastUpdate = $params['lastUpdate'];
		$q = $params['q'];
		$albumId = $params['albumId'];
		$max = $params['max'];

		if (!$me) {
			return false;
		}

		$params = [
			($lastUpdate ? $lastUpdate : date('Y-m-d H:i:s', strtotime(' - 1 day ')))
		];

		$where_sensitive = $me->show_sensitive
			? " "
			: " and m.is_sensitive = 0 ";

		if ($q) {
			$where_q = " and (m.text like ? or u.name = ?)";
			$params[] = "%{$q}%";
			$params[] = $q;
		} else {
			$where_q = '';
		}

		$where_max = $max ? " and m.create_at < '{$max}' " : '';

		$basicColumns = self::getBasicColumns();

		$sql = "
			select
				{$basicColumns},
				'' as reply_mid				
			from
				meow m 
				inner join user u
					on u.id = m.user_id
			where
				m.create_at > ?
				and (m.is_private = 0 or (m.is_private <= 1 and m.user_id = {$me->id}))
				and m.is_deleted = 0
				and m.reply_to = 0
				and m.ap_object_id = 0
				{$where_sensitive}
				{$where_q}
				{$where_max}
				and m.user_id not in (select mute_user_id from mute where user_id = {$me->id})
			order by m.create_at desc
			limit 50
		";
		$meows = self::db()->query($sql, $params)->result();

		return self::decorateMeows($meows);
	}

	public static function getPublicTimeLine () {

		$oneDayAgo = date('Y-m-d H:i:s', strtotime(' - 1 day '));

		$basicColumns = self::getBasicColumns();

		$sql = "
			select
				{$basicColumns},
				'' as reply_mid
			from
				meow m 
				inner join user u
					on u.id = m.user_id
			where
				m.create_at > '{$oneDayAgo}'
				and m.is_private = 0
				and m.is_deleted = 0
				and m.reply_to = 0
				and m.is_sensitive = 0
				and m.ap_object_id = 0
			order by m.create_at desc
			limit 50
		";
		$meows = self::db()->query($sql)->result();
		return self::decorateMeows($meows);
	}

	public static function checkHomeTimeline ($me, $lastUpdate = false, $q = '') {

		if (!$me || !$lastUpdate) {
			return 0;
		}

		$params = [$lastUpdate];

		$where_sensitive = $me->show_sensitive
			? " "
			: " and m.is_sensitive = 0 ";

		if ($q) {
			$where_q = " and (m.text like ?)";
			$params[] = "%{$q}%";
		} else {
			$where_q = '';
		}

		$sql = "
			select m.id
			from meow m
			where
				m.create_at > ?
				and m.is_deleted = 0
				and m.user_id != {$me->id}
				and (
					m.is_private = 0
					and (m.is_private <= 4 and m.user_id in (select follow_user_id from follow where user_id = {$me->id}))
				)
				{$where_sensitive}
				and m.user_id not in (select mute_user_id from mute where user_id = {$me->id})
				{$where_q}
			order by m.create_at desc
			limit 1
		";
		$meow = self::db()->query($sql, $params)->row();
		return $meow ? $meow->id : 0;
	}

	public static function getHomeTimeline ($me, $lastUpdate = false, $q = '', $max = 0) {

		if (!$me) { return []; }

		$params = [];

		$where_sensitive = $me->show_sensitive
			? " "
			: " and m.is_sensitive = 0 ";

		// 自分かフォロアーの発言だけ
		$where_private = "
			and (
				(m.is_private <= 4 and m.user_id = {$me->id})
				or (m.is_private = 0
					and m.user_id in (select follow_user_id from follow where user_id = {$me->id})
				)
			)
		";

		if ($lastUpdate) {
			$where_create_at = " and m.create_at > ? ";
			$params[] = $lastUpdate;
		} else {
			$where_create_at = '';
		}

		if ($q) {
			$where_q = " and (m.text like ?)";
			$params[] = "%{$q}%";
		} else {
			$where_q = '';
		}

		$where_max = $max ? " and m.create_at < '{$max}' " : '';

		$basicColumns = self::getBasicColumns();

		$sql = "
			select
				{$basicColumns},
				ru.mid as reply_mid,
				ao.object_id as ap_object_id,
				ao.object as ap_object
			from
				meow m 
				inner join user u
					on u.id = m.user_id
				left outer join meow r
					on r.id = m.reply_to
					and r.is_deleted = 0
				left outer join user ru
					on ru.id = r.user_id
				left outer join ap_object ao
					on ao.id = m.ap_object_id
			where
				m.is_deleted = 0
				{$where_create_at}
				{$where_private}
				{$where_sensitive}
				{$where_q}
				{$where_max}
				and m.user_id not in (select mute_user_id from mute where user_id = {$me->id})
			order by m.create_at desc
			limit 50
		";
		$meows = self::db()->query($sql, $params)->result();
		return self::decorateMeows($meows);
	}

	public static function checkEachFollowTimeLine ($me, $lastUpdate = false, $q = '') {

		if (!$me || !$lastUpdate) {
			return 0;
		}

		$params = [$lastUpdate];

		if ($q) {
			$where_q = " and (m.text like ?)";
			$params[] = "%{$q}%";
		} else {
			$where_q = '';
		}

		$sql = "
			select m.id
			from meow m 
			where
				m.create_at > ?
				and m.is_deleted = 0
				and m.user_id != {$me->id}
				and (
					m.is_private = 3
					and (select user_id from follow where user_id = {$me->id} and follow_user_id = m.user_id limit 1) = {$me->id}
					and (select follow_user_id from follow where user_id = m.user_id and follow_user_id = {$me->id} limit 1) = {$me->id}
				)
				{$where_q}
			order by m.create_at desc
			limit 1
		";
		$meow = self::db()->query($sql, $params)->row();
		return $meow ? $meow->id : 0;
	}

	public static function getPrivateTimeline ($me, $lastUpdate = false, $q = '', $max = 0) {

		if (!$me) {
			return [];
		}

		$params = [];

		// 自分かフォロアーの発言だけ
		$where_private = "
			and (
				(
					m.is_private = 3
					and m.user_id = {$me->id}
				)
				or (m.is_private = 3
					and (select user_id from follow where user_id = {$me->id} and follow_user_id = m.user_id limit 1) = {$me->id}
					and (select follow_user_id from follow where user_id = m.user_id and follow_user_id = {$me->id} limit 1) = {$me->id}
				)
			)
		";

		if ($lastUpdate) {
			$where_create_at = " and m.create_at > ? ";
			$params[] = $lastUpdate;
		} else {
			$where_create_at = '';
		}

		if ($q) {
			$where_q = " and (m.text like ?)";
			$params[] = "%{$q}%";
		} else {
			$where_q = '';
		}

		$where_max = $max ? " and m.create_at < '{$max}' " : '';

		$basicColumns = self::getBasicColumns();

		$sql = "
			select
				{$basicColumns},
				ru.mid as reply_mid
			from
				meow m 
				inner join user u
					on u.id = m.user_id
				left outer join meow r
					on r.id = m.reply_to
					and r.is_deleted = 0
				left outer join user ru
					on ru.id = r.user_id
			where
				m.is_deleted = 0
				{$where_private}
				{$where_create_at}
				{$where_q}
				{$where_max}
			order by m.create_at desc
			limit 50
		";
		$meows = self::db()->query($sql, $params)->result();
		return self::decorateMeows($meows);
	}

	public static function getMeowReplies ($me, $id) {

		if ($me) {
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

		// replies
		$sql = "
			select
				m.*,
				u.mid,
				u.name,
				u.icon,
				ru.mid as reply_mid
			from
				meow m 
				inner join user u
					on u.id = m.user_id
				left outer join meow r
					on r.id = m.reply_to
					and r.is_deleted = 0
				left outer join user ru
					on ru.id = r.user_id
			where
	            m.reply_to = ?
				and m.is_deleted = 0
				and {$where_private}
			order by create_at desc 
			limit 10
	    ";
		$replies = self::db()->query($sql, [$id])->result();
		foreach ($replies as $i => $reply) {
			$replies[$i] = MeowManager::decorate($reply);
		}

		return $replies;
	}

	public static function checkReply ($me, $time) {

		if (!$me || !$time) {
			return false;
		}

		$ymdhis = date('Y-m-d H:i:s', $time);

		// 自分宛
		$sql = "
			select m.id
			from
				meow m 
				inner join reply r
					on r.meow_id = m.id
			where
				m.create_at > '{$ymdhis}'
				and r.reply_user_id = {$me->id}
				and m.is_deleted = 0
			order by m.create_at desc
			limit 1
		";
		$meow = self::db()->query($sql)->row();
		return $meow ? $meow->id : false;
	}

	public static function getNotices ($me, $lastUpdate = 0, $q = '', $max = 0) {

		$where_max = $max ? " and m.create_at < '{$max}' " : '';

		$sql = "
			select
				n.type,
				m.id,
				m.reply_to,
				n.from_user_id as user_id,
				case
					when n.type = 1 then m.text
					when n.type = 2 then d.text
					else '' end as text,
				case
					when n.type = 1 then ''
					when n.type = 2 then d.file
					else '' end as file,
				case
					when n.type = 1 then m.files
					when n.type = 2 then d.file
					else '' end as files,
				n.create_at,
				m.is_private,
				m.is_sensitive,
				m.reply_count,
				m.fav_count,
				m.is_paint,
				m.has_thumb,
				u.mid,
				u.name,
				u.icon,
				ru.mid as reply_mid,
				d.id as dm_id
			from
				notice n
				left outer join user u 
					on n.from_user_id = u.id
				left outer join meow m 
					on n.meow_id > 0
					and n.meow_id = m.id
					and m.is_deleted = 0
				left outer join dm d 
					on n.type = 2
					and n.object_id = d.id
					and d.is_deleted = 0
				left outer join meow rm
					on rm.id = m.reply_to
					and rm.is_deleted = 0
				left outer join user ru
					on ru.id = rm.user_id
			where
				n.to_user_id = {$me->id}
				{$where_max}
			order by n.create_at desc 
			limit 50
		";

		$notices = self::db()->query($sql)->result();
		return self::decorateMeows($notices);
	}

	public static function createLatestJson () {
		$dateFrom = date('Y-m-d H:i:s', strtotime(' - 1 day '));
		$basicColumns = self::getBasicColumns();
		$sql = "
			select
				{$basicColumns},
				m.fav_count,
				'' as reply_mid
			from
				meow m 
				inner join user u
					on u.id = m.user_id
			where
				m.create_at > '{$dateFrom}'
				and m.is_private = 0
				and m.is_deleted = 0
				and m.reply_to = 0
				and m.ap_object_id = 0
			order by m.create_at desc
			limit 50
		";
		if ($meows = self::db()->query($sql)->result()) {
			$meows = self::decorateMeows($meows);
			$summary = [];
			foreach ($meows as $meow) {
				$summary[] = [
					'id' => $meow->id,
					'user_id' => $meow->user_id,
					'create_at' => strtotime($meow->create_at),
				];
			}
			$summaryFilePath = $_SERVER['DOCUMENT_ROOT'] . "/latestSummary.json";
			file_put_contents($summaryFilePath, json_encode($summary));
			$filePath = $_SERVER['DOCUMENT_ROOT'] . "/latest.json";
			file_put_contents($filePath, json_encode($meows));
		}
	}

	public static function getDisplayTime ($datetime) {

		$now = time();
		$meow_time = strtotime($datetime);
		$diff = $now - $meow_time;

		if ($diff < 60) { // 60秒未満
			return '今';
		} elseif ($diff <= 3600) { // 60分未満
			return intval(($diff % 3600) / 60) . "分";
		} elseif ($diff <= (24 * 3600)) { // 24時間未満
			return intval(($diff % (3600 * 24)) / 3600) . "時間";
		} elseif ($diff <= (7 * 24 * 3600)) {
			return intval(($diff % (3600 * 24 * 7)) / (3600 * 24)) . "日";
		} elseif ($diff <= (12 * 7 * 24 * 3600)) {
			return date('m月d日', $meow_time);
		} else {
			return date('Y年m月d日', $meow_time);
		}
	}

	public static function decorateMeows ($meows) {
		foreach ($meows as $i => $meow) {
			$meow = MeowManager::decorate($meow);
			$meows[$i] = $meow;
		}
		return $meows;
	}

	public static function decorate ($meow) {

		$meow->display_time = self::getDisplayTime($meow->create_at);

		$meow->prof_path = self::getProfPath($meow->user_id);

		if ($meow->icon) {
			$meow->icon_path = (strpos($meow->icon, 'https://') === 0)
				? $meow->icon
				: $meow->prof_path . "/" . $meow->icon;
		} else {
			$meow->icon_path = '';
		}

		if ($meow->files) {
			$meow->files = explode(',', $meow->files);
			if (!empty($meow->orgfiles)) {
				$meow->orgfiles = $meow->orgfiles
					? explode(',', $meow->orgfiles)
					: array_fill(0, count($meow->files), '');
			} else {
				$meow->orgfiles = array_fill(0, count($meow->files), '');
			}
		}

		return $meow;
	}

	public static function incrementFavCount ($meow_id) {
		$sql = " update meow set fav_count = fav_count + 1 where id = {$meow_id} ";
		self::db()->query($sql);
	}
	public static function decrementFavCount ($meow_id) {
		$sql = " update meow set fav_count = fav_count - 1 where id = {$meow_id} and fav_count > 0 ";
		self::db()->query($sql);
	}
	public static function calcFavCount ($meow_id) {
		$sql = "
	        update meow 
	        set fav_count = (select count(id) from fav where fav.meow_id = meow.id)
	        where meow.id = {$meow_id}
	    ";
		self::db()->query($sql);
	}

	public static function incrementReplyCount ($meow_id) {
		$sql = " update meow set reply_count = reply_count + 1 where id = {$meow_id} ";
		self::db()->query($sql);
	}
	public static function decrementReplyCount ($meow_id) {
		$sql = " update meow set reply_count = reply_count - 1 where id = {$meow_id} ";
		self::db()->query($sql);
	}
	public static function calcReplyCount($meow_id) {
		$sql = "
		        update meow 
		        set reply_count = (select count(m2.id) from meow m2 where m2.reply_to = meow.id)
		        where meow.id = {$meow_id}
		    ";
		self::db()->query($sql);
	}

	public static function lsort ($a, $b) {
		$la = strlen($a);
		$lb = strlen($b);
		if ($la > $lb) return 1;
		if ($la < $lb) return -1;
		return 0;
	}
	public static function lrsort ($a, $b) {
		$la = strlen($a);
		$lb = strlen($b);
		if ($la < $lb) return 1;
		if ($la > $lb) return -1;
		return 0;
	}

	public static function getUserPath ($user_id) {
		$id = str_pad($user_id, 4, '0', STR_PAD_LEFT);
		$id0 = substr($id, -1, 1);
		$id1 = substr($id, -2, 1);
		$id2 = substr($id, -3, 1);
		return "{$id0}/{$id1}/{$id2}";
	}
	public static function getProfPath ($user_id) {
		return "/up/user/" . self::getUserPath($user_id);
	}

	public static function checkNotice ($me) {
		$now = date('Y-m-d H:i:s');
		if ($me->unread_reply_count || $me->unread_dm_count) {
			$sql = " 
 					update user set 
 						unread_reply_count = 0, 
 						unread_dm_count = 0,
 						check_notice_at = '{$now}'
                    where id = {$me->id} 
                ";
			self::db()->query($sql);
			$me->unread_reply_count = 0;
			$me->unread_dm_count = 0;
			MeowManager::createUserInfoJson($me->id);
		} else {
			$sql = " update user set check_notice_at = '{$now}' where id = {$me->id} ";
			self::db()->query($sql);
		}
		$me->check_notice_at = $now;
		return $me;
	}

	public static function createUserInfoJson ($user_id) {
		$dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/json/user/' . MeowManager::getUserPath($user_id) . "/{$user_id}";
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		$sql = "
            select
                id,
                mid,
                name,
                twitter_id,
                note,
                icon,
                header_img,
                url,
                create_at,
                bgcolor,
                unread_reply_count,
                unread_dm_count
            from user
            where id = {$user_id}
        ";
		$user = self::db()->query($sql)->row();
		$path = $dir . "/user.json";
		file_put_contents($path, json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		return $path;
	}
}