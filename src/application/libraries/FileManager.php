<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FileManager
{
	public static function extToMimeType ($ext) {
		$values = [
			'png' => 'image/png',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif' => 'image/gif',
			'mid' => 'audio/midi',
		];
		return $values[$ext] ?? '';
	}
	public static function mimeTypeToExt ($mimeType) {
		if ($mimeType == 'image/png') {
			return 'png';
		} elseif ($mimeType == 'image/jpeg') {
			return 'jpg';
		} elseif ($mimeType == 'image/gif') {
			return 'gif';
		} elseif (in_array($mimeType, ['audio/mid', 'audio/midi', 'audio/x-midi'])) {
			return 'mid';
		}
		return '';
	}

	public static function getMimeType ($filename) {
		$ext = self::getExt($filename);
		return self::extToMimeType($ext);
	}

    public static function getExt ($filename) {
        $pathInfo = pathinfo($filename);
        return strtolower($pathInfo['extension']);
    }

    public static function unlinkIfExists ($path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }

	public static function checkExistsDir ($dir) {
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
	}
}