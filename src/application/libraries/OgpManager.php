<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/06/08
 * Time: 14:27
 */

use KubAT\PhpSimple\HtmlDomParser;

class OgpManager extends LibraryBase
{
	public static function _loadOgp ($url) {
		$sql = " select * from ogp where url = ? ";
		$ogp = self::db()->query($sql, [$url])->row();
		$oneWeekAgo = date('Y-m-d H:i:s', strtotime(' - 1 week '));
		if ($ogp && $ogp->update_at > $oneWeekAgo) {
			return json_encode($ogp, JSON_UNESCAPED_SLASHES);
		} else {
			return self::_scrapeOgp($url);
		}
	}

	public static function _scrapeOgp ($url) {

		$flagFilePath = Meow::BASE_DIR . "/application/cache/flag_ogp_scrape.txt";

		if (is_file($flagFilePath)) {
			$flagInfo = json_decode(file_get_contents($flagFilePath));
			if (isset($flagInfo->time) && $flagInfo->time > time() + 5) {
				return "{}";
			}
		}

		$flagInfo = [
			'url' => $url,
			'time' => time(),
		];

		file_put_contents($flagFilePath, json_encode($flagInfo, JSON_UNESCAPED_SLASHES));

		$base_url = $url;
		/** @var \GuzzleHttp\Client $client */

		try {

			$client = new \GuzzleHttp\Client(
				[
					'base_uri' => $base_url,
					'',
					[
						'allow_redirects' => true,
						'verify' => false,
					],
				]
			);
			$response = $client->request('GET');

		} catch (GuzzleHttp\Exception\ClientException $e) {
			if ($e->getResponse()->getStatusCode() == '404') {
				$values = [
					'url' => $url,
					'notice' => $e->getMessage(),
				];
				self::storeOgp($values);
			}
			sleep(3);
			if (is_file($flagFilePath)) {
				unlink($flagFilePath);
			}
			return "{}";
		} catch (GuzzleHttp\Exception\RequestException $e) {
			$errorMessage = $e->getMessage();

			if (strpos($errorMessage, 'SSL certificate problem') !== false) {
				$values = [
					'url' => $url,
					'notice' => $errorMessage,
				];
				self::storeOgp($values);
			}

			$result = [
				'errorMessage' => $errorMessage,
			];
			sleep(3);
			if (is_file($flagFilePath)) {
				unlink($flagFilePath);
			}
			return json_encode($result, JSON_UNESCAPED_SLASHES);
		}

		$statusCode = $response->getStatusCode();
		if (substr($statusCode, 0, 1) == "2") {

			$html = (string)$response->getBody();

			$charset = strtolower(self::getCharset($html));
			if ($charset && $charset != 'utf-8') {
				$html = mb_convert_encoding($html, 'UTF-8', $charset);
			}

			$dom = HtmlDomParser::str_get_html($html);

			if (!$dom) {
				$result = [
					'error' => "html dom parser error",
					'url' => $url
				];
				print json_encode($result);
				return false;
			}

			$values = [
				'url' => $url,
				'update_at' => date('Y-m-d H:i:s'),
			];
			$filters = [
				'site_name' => 'meta[property=og:site_name]',
				'title' => 'meta[property=og:title]',
				'description' => 'meta[property=og:description]',
				'image' => 'meta[property=og:image]',
				'thumb' => 'meta[name="thumbnail]',
				'image_width' => 'meta[property=og:image:width]',
				'image_height' => 'meta[property=og:image:height]',
				'twitter_card' => 'meta[name=twitter:card]',
			];
			foreach ($filters as $key => $filter) {
				if ($elems = $dom->find($filter)) {
					/** @var \simple_html_dom\simple_html_dom_node $elem */
					$elem = $elems[0];
					$values[$key] = $elem->getAttribute('content');
				} else {
					$values[$key] = '';
				}
			}

			if (!$values['title']) {
				if ($elems = $dom->find('title')) {
					$elem = $elems[0];
					$values['title'] = $elem->innertext();
				}
			}
			if (!$values['description']) {
				if ($elems = $dom->find('meta[name=description]')) {
					$elem = $elems[0];
					$values['description'] = $elem->getAttribute('content');
				}
			}

			// / 始まりだった場合
			if ($values['image'] && $values['image'][0] == '/') {
				$values['image'] = 'https://' . parse_url($url, PHP_URL_HOST) . $values['image'];
			}
			if ($values['thumb'] && $values['thumb'][0] == '/') {
				$values['thumb'] = 'https://' . parse_url($url, PHP_URL_HOST) . $values['thumb'];
			}

			// ogpない場合
			if (!$values['title']) {
				$values = [
					'url' => $url,
					'notice' => 'no title'
				];
			}

			self::storeOgp($values);

			$values['charset'] = $charset;
		} else {

			$values = [
				'url' => $url,
				'notice' => "statusCode : {$statusCode}"
			];
			self::storeOgp($values);
		}

		sleep(3);

		if (is_file($flagFilePath)) {
			unlink($flagFilePath);
		}

		if (MEOW_IS_DEBUG) {
			ini_set('display_errors', "On");
		}

		return json_encode($values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	private static function storeOgp ($values) {
		if (empty($values['update_at'])) {
			$values['update_at'] = date('Y-m-d H:i:s');
		}
		$sql = " select id from ogp where url = ? limit 1 ";
		$ogp = self::db()->query($sql, [$values['url']])->row();
		if ($ogp) {
			self::db()->update('ogp', $values, " id = '{$ogp->id}' ");
		} else {
			self::db()->insert('ogp', $values);
		}
	}

	private static function normalizeEncoding ($enc) {
		$enc = strtolower($enc);
		switch ($enc) {
			case 'shiftjis':
			case 'shift-jis':
			case 'shift_jis':
			case 'sjis':
			case 'sjis-win':
				return 'sjis-win';

			case 'utf-8':
			case 'utf8':
				return 'utf-8';
		}

		return $enc;
	}

	public static function getCharset ($html) {

		// 自動判定
		mb_language('Ja');
		$codeList = ['ascii', 'iso-2022-jp', 'utf-8', 'euc-jp', 'sjis-win'];
		$detectedCharset = self::normalizeEncoding(mb_detect_encoding($html, $codeList, true));

		// HTML内の宣言をチェック
		$declaredCharset = self::normalizeEncoding(self::getDeclaredCharset($html));

		// 自動判定と記述が一致したら文句なし
		if ($detectedCharset == $declaredCharset) {
			return $declaredCharset;
		}

		// なんかおかしい場合

		// UTF-8 => UTF-8 で 一致すれば UTF-8
		if ($html == mb_convert_encoding($html, 'UTF-8', 'UTF-8')) {
			return 'utf-8';
		}

		if ($html == mb_convert_encoding($html, $declaredCharset, $declaredCharset)) {
			return $declaredCharset;
		}

		if ($html == mb_convert_encoding($html, $detectedCharset, $detectedCharset)) {
			return $detectedCharset;
		}

		return 'utf-8';
	}

	private static function getDeclaredCharset ($html) {
		if (preg_match('/<meta.+?charset="?([^"^>]+)?"?.*?>/', $html, $matches)) {
			return $matches[1];
		} else if (preg_match('/<meta.+?charset=(.+)?[";]/', $html, $matches)) {
			return $matches[1];
		}
		return '';
	}
}