-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- ホスト: localhost:3306
-- 生成日時: 2021 年 6 月 03 日 18:22
-- サーバのバージョン： 10.3.25-MariaDB-log-cll-lve
-- PHP のバージョン: 7.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


--
-- データベース: `mfymdexr_meow`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `album`
--

CREATE TABLE `album` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_public` tinyint(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_activity`
--

CREATE TABLE `ap_activity` (
  `id` int(11) NOT NULL,
  `object_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `actor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_actor`
--

CREATE TABLE `ap_actor` (
  `id` int(11) NOT NULL,
  `actor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferred_username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `host` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `update_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_deliver_queue`
--

CREATE TABLE `ap_deliver_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ap_activity_id` int(11) NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_emoji`
--

CREATE TABLE `ap_emoji` (
  `id` int(11) NOT NULL,
  `object_id` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `update_at` datetime NOT NULL,
  `icon_image_url` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_follow`
--

CREATE TABLE `ap_follow` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `actor_id` int(255) NOT NULL DEFAULT 0,
  `follow_user_id` int(11) NOT NULL DEFAULT 0,
  `follow_actor_id` int(11) NOT NULL DEFAULT 0,
  `is_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `apply` tinyint(1) NOT NULL DEFAULT 1,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_note`
--

CREATE TABLE `ap_note` (
  `id` int(11) NOT NULL,
  `object_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` int(11) NOT NULL,
  `acct` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_object`
--

CREATE TABLE `ap_object` (
  `id` int(11) NOT NULL,
  `object_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` int(11) NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ap_service`
--

CREATE TABLE `ap_service` (
  `id` int(11) NOT NULL,
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shared_inbox` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enable_deliver` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `bookmark`
--

CREATE TABLE `bookmark` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meow_id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `dm`
--

CREATE TABLE `dm` (
  `id` int(11) NOT NULL,
  `meow_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `text` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ipint` int(10) UNSIGNED NOT NULL,
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ua` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `fav`
--

CREATE TABLE `fav` (
  `id` int(11) NOT NULL,
  `meow_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `file`
--

CREATE TABLE `file` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `meow_id` int(11) NOT NULL,
  `dir` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ext` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orgname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `follow`
--

CREATE TABLE `follow` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `follow_user_id` int(11) NOT NULL,
  `follow_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_accepted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `host`
--

CREATE TABLE `host` (
  `id` int(11) NOT NULL,
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `inbox`
--

CREATE TABLE `inbox` (
  `id` int(10) UNSIGNED NOT NULL,
  `object_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `mid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `apply` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `meow`
--

CREATE TABLE `meow` (
  `id` int(11) NOT NULL,
  `reply_to` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `summary` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ipint` int(10) UNSIGNED NOT NULL,
  `ua_id` int(11) NOT NULL DEFAULT 0,
  `host_id` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL,
  `is_private` smallint(11) NOT NULL,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `reply_count` smallint(5) UNSIGNED NOT NULL,
  `fav_count` smallint(5) UNSIGNED NOT NULL,
  `is_paint` tinyint(4) NOT NULL DEFAULT 0,
  `files` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orgfiles` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_thumb` tinyint(1) NOT NULL DEFAULT 0,
  `dm_id` int(10) UNSIGNED NOT NULL,
  `ap_note_id` int(11) NOT NULL DEFAULT 0,
  `reply_to_actor_id` int(11) NOT NULL DEFAULT 0,
  `reply_to_note_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `mute`
--

CREATE TABLE `mute` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mute_user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `mute_word`
--

CREATE TABLE `mute_word` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `word` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `notice`
--

CREATE TABLE `notice` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `type` smallint(6) NOT NULL,
  `object_id` int(11) NOT NULL,
  `meow_id` int(11) NOT NULL,
  `dm_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ogp`
--

CREATE TABLE `ogp` (
  `id` int(11) NOT NULL,
  `url` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ogp` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumb` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_width` smallint(6) NOT NULL,
  `image_height` smallint(6) NOT NULL,
  `twitter_card` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `page`
--

CREATE TABLE `page` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `html` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `update_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `password_reminder`
--

CREATE TABLE `password_reminder` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `auth_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `reply`
--

CREATE TABLE `reply` (
  `id` int(11) NOT NULL,
  `meow_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reply_user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `test`
--

CREATE TABLE `test` (
  `id` int(11) NOT NULL,
  `create_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ua`
--

CREATE TABLE `ua` (
  `id` int(11) NOT NULL,
  `ua` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `twitter_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `header_img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `header_img_size` int(11) NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apply` tinyint(1) NOT NULL DEFAULT 1,
  `password` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `register_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `update_at` datetime NOT NULL DEFAULT current_timestamp(),
  `follows` int(11) NOT NULL,
  `followers` int(11) NOT NULL,
  `bgcolor` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `show_sensitive` smallint(6) NOT NULL DEFAULT 0,
  `unread_reply_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `unread_dm_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `respect_cat` tinyint(1) NOT NULL DEFAULT 0,
  `is_dokusai` tinyint(1) NOT NULL DEFAULT 0,
  `invite_key` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invite_message` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enable_fediverse` tinyint(4) NOT NULL DEFAULT 0,
  `check_notice_at` datetime NOT NULL,
  `mute_edit_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `mute_word_edit_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `album_edit_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `privkey` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pubkey` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `actor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `album`
--
ALTER TABLE `album`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`name`);

--
-- テーブルのインデックス `ap_activity`
--
ALTER TABLE `ap_activity`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `ap_actor`
--
ALTER TABLE `ap_actor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `actor_id` (`actor`);

--
-- テーブルのインデックス `ap_deliver_queue`
--
ALTER TABLE `ap_deliver_queue`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `ap_emoji`
--
ALTER TABLE `ap_emoji`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `ap_follow`
--
ALTER TABLE `ap_follow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`actor_id`,`follow_user_id`,`follow_actor_id`);

--
-- テーブルのインデックス `ap_note`
--
ALTER TABLE `ap_note`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `object_id` (`object_id`);

--
-- テーブルのインデックス `ap_object`
--
ALTER TABLE `ap_object`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `ap_service`
--
ALTER TABLE `ap_service`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `bookmark`
--
ALTER TABLE `bookmark`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`meow_id`);

--
-- テーブルのインデックス `dm`
--
ALTER TABLE `dm`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `fav`
--
ALTER TABLE `fav`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meow_id` (`meow_id`,`user_id`);

--
-- テーブルのインデックス `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `follow`
--
ALTER TABLE `follow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`follow_user_id`);

--
-- テーブルのインデックス `host`
--
ALTER TABLE `host`
  ADD PRIMARY KEY (`id`),
  ADD KEY `host` (`host`);

--
-- テーブルのインデックス `inbox`
--
ALTER TABLE `inbox`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `object_id` (`object_id`);

--
-- テーブルのインデックス `meow`
--
ALTER TABLE `meow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `create_at` (`create_at`);

--
-- テーブルのインデックス `mute`
--
ALTER TABLE `mute`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`mute_user_id`);

--
-- テーブルのインデックス `mute_word`
--
ALTER TABLE `mute_word`
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`word`);

--
-- テーブルのインデックス `notice`
--
ALTER TABLE `notice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `create_at` (`create_at`);

--
-- テーブルのインデックス `ogp`
--
ALTER TABLE `ogp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `url` (`url`);

--
-- テーブルのインデックス `page`
--
ALTER TABLE `page`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `password_reminder`
--
ALTER TABLE `password_reminder`
  ADD KEY `id` (`id`);

--
-- テーブルのインデックス `reply`
--
ALTER TABLE `reply`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meow_id` (`meow_id`,`reply_user_id`);

--
-- テーブルのインデックス `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `ua`
--
ALTER TABLE `ua`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ua` (`ua`);

--
-- テーブルのインデックス `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mid` (`mid`);

--
-- ダンプしたテーブルのAUTO_INCREMENT
--

--
-- テーブルのAUTO_INCREMENT `album`
--
ALTER TABLE `album`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_activity`
--
ALTER TABLE `ap_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_actor`
--
ALTER TABLE `ap_actor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_deliver_queue`
--
ALTER TABLE `ap_deliver_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_emoji`
--
ALTER TABLE `ap_emoji`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_follow`
--
ALTER TABLE `ap_follow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_note`
--
ALTER TABLE `ap_note`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_object`
--
ALTER TABLE `ap_object`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ap_service`
--
ALTER TABLE `ap_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `bookmark`
--
ALTER TABLE `bookmark`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `dm`
--
ALTER TABLE `dm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `fav`
--
ALTER TABLE `fav`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `file`
--
ALTER TABLE `file`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `follow`
--
ALTER TABLE `follow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `host`
--
ALTER TABLE `host`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `inbox`
--
ALTER TABLE `inbox`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `meow`
--
ALTER TABLE `meow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `mute`
--
ALTER TABLE `mute`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `mute_word`
--
ALTER TABLE `mute_word`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `notice`
--
ALTER TABLE `notice`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ogp`
--
ALTER TABLE `ogp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `page`
--
ALTER TABLE `page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `password_reminder`
--
ALTER TABLE `password_reminder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `reply`
--
ALTER TABLE `reply`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `ua`
--
ALTER TABLE `ua`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルのAUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;