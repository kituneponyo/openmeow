<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/05/17
 * Time: 6:47
 */

class Signature extends LibraryBase
{
	const SSH_KEY_DIR = Meow::BASE_DIR . "/application/pubkey";
	const PRIVATE_KEY_PATH = self::SSH_KEY_DIR . "/meow.pem";
	const PUBLIC_KEY_PATH = self::SSH_KEY_DIR . "/meow.pub";

	public static function generateServerKeyPair () {
		$keyPair = self::generateKeyPair();
		FileManager::checkExistsDir(self::SSH_KEY_DIR);
		file_put_contents(self::PRIVATE_KEY_PATH, $keyPair['privkey']);
		file_put_contents(self::PUBLIC_KEY_PATH, $keyPair['pubkey']);
	}

	public static function loadServerPrivateKey () {
		if (!is_file(self::PRIVATE_KEY_PATH)) {
			self::generateServerKeyPair();
		}
		return trim(file_get_contents(self::PRIVATE_KEY_PATH));
	}

	public static function loadServerPublicKey () {
		if (!is_file(self::PUBLIC_KEY_PATH)) {
			self::generateServerKeyPair();
		}
		return trim(file_get_contents(self::PUBLIC_KEY_PATH));
	}

	public static function generateKeyPair () {

		// RSA key
		$config = [
			"digest_alg" => "sha256",
			"private_key_bits" => 2048,
			"private_key_type" => OPENSSL_KEYTYPE_RSA,
		];
		// Create the private and public key
		$res = openssl_pkey_new($config);
		// Extract the private key from $res to $privKey
		openssl_pkey_export($res, $privKey);
		// Extract the public key from $res to $pubKey
		$pubKey = openssl_pkey_get_details($res);
		$pubKey = $pubKey["key"];

		return [
			'pubkey' => $pubKey,
			'privkey' => $privKey
		];
	}

	public static function generate_digest( $body ) {
		$digest = \base64_encode(\hash('sha256', $body, true ) ); // phpcs:ignore
		return "$digest";
	}

	public static function generate_signature($user_id, $url, $date, $digest = null, $privkey = null) {

		$url_parts = parse_url($url);

		$host = $url_parts['host'];
		$path = '/';

		// add path
		if (!empty($url_parts['path'])) {
			$path = $url_parts['path'];
		}

		// add query
		if (!empty($url_parts['query'])) {
			$path .= '?' . $url_parts['query'];
		}

		$signed_string = $digest
			? "(request-target): post $path\nhost: $host\ndate: $date\ndigest: SHA-256=$digest"
			: "(request-target): post $path\nhost: $host\ndate: $date";

		if (!$privkey) {
			$privkey = Signature::loadServerPrivateKey();
		}

		$_privkey = openssl_get_privatekey($privkey, '');

		$signature = null;
		\openssl_sign( $signed_string, $signature, $_privkey, \OPENSSL_ALGO_SHA256 );

		$signature = \base64_encode( $signature ); // phpcs:ignore

		if ($user_id) {
			$key_id = Meow::BASE_URL . "/u/{$user_id}#main-key";
		} else {
			$key_id = Meow::BASE_URL . "/#main-key";
		}

		if ( ! empty( $digest ) ) {
			return \sprintf( 'keyId="%s",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="%s"', $key_id, $signature );
		} else {
			return \sprintf( 'keyId="%s",algorithm="rsa-sha256",headers="(request-target) host date",signature="%s"', $key_id, $signature );
		}
	}

	/**
	 * @param array $header HTTP Request Header
	 * @param $pubkey OpenSSL key or row public key string
	 * @param string $path
	 * @return int
	 */
	public static function verify (array $header, $pubkey, string $path) {

		if (is_string($pubkey)) {
			if (strpos($pubkey, '-----BEGIN PUBLIC KEY-----') === 0) {
				$pubkey = openssl_pkey_get_public($pubkey);
			} else {
				return false;
			}
		}

		foreach ($header as $k => $v) {
			$header[strtolower($k)] = $v;
			unset($header[$k]);
		}

		// HTTPリクエストヘッダの Signature を分解
		$headerSignatureKeyValues = [];
		foreach (explode(',', $header['signature']) as $keyValue) {
			list($key, $value) = explode('=', $keyValue);
			$headerSignatureKeyValues[$key] = str_replace('"', '', $value);
		}

		$headersToSign = [];
		foreach (explode(' ', $headerSignatureKeyValues['headers']) as $h) {
			if ($h == '(request-target)') {
				$headersToSign[$h] = 'post ' . $path;
			} elseif (isset($header[$h])) {
				$headersToSign[$h] = $header[$h];
			}
		}

		$signingString = implode("\n", array_map(function($k, $v){
			return strtolower($k).': '.$v;
		}, array_keys($headersToSign), $headersToSign));

		return openssl_verify($signingString, base64_decode($headerSignatureKeyValues['signature']), $pubkey, \OPENSSL_ALGO_SHA256);
	}
}