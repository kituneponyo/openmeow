<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ComicManager
{
	public static function square ($src, $dst, $w = 100, $h = 100) {

		$mime = mime_content_type($src);
		$ext = FileManager::mimeTypeToExt($mime);
		$img = self::getImg($src, $mime);
		if (!$img) {
			return false;
		}

		$width = ImageSx($img);
		$height = ImageSy($img);

		$long = $width >= $height ? $width : $height;

		$newW = $width * $w / $long;
		$newH = $height * $h / $long;

		if ($newW > $newH) {
			// 横長だったら
			$newW = $newH;
			$w_offset = ($width - $height) / 2;
			$h_offset = 0;
		} elseif ($newH > $newW) {
			// 縦長
			$newH = $newW;
			$w_offset = 0;
			$h_offset = ($height - $width) / 2;
		} else {
			$w_offset = 0;
			$h_offset = 0;
		}

		$out = ImageCreateTrueColor($newW, $newH);
		ImageCopyResampled($out, $img,
			0,0, $w_offset, $h_offset,
			$newW, $newH, $width - ($w_offset * 2), $height - ($h_offset * 2));

		self::outImg($out, $dst, $mime, $ext);

		return true;
	}

	public static function rotateImage ($img, $orientation) {
		//回転角度
		$degrees = 0;
		switch($orientation) {
			case 1:		//回転なし（↑）
				return $img;
			case 8:		//右に90度（→）
				$degrees = 90;
				break;
			case 3:		//180度回転（↓）
				$degrees = 180;
				break;
			case 6:		//右に270度回転（←）
				$degrees = 270;
				break;
			case 2:		//反転　（↑）
				$mode = IMG_FLIP_HORIZONTAL;
				break;
			case 7:		//反転して右90度（→）
				$degrees = 90;
				$mode = IMG_FLIP_HORIZONTAL;
				break;
			case 4:		//反転して180度なんだけど縦反転と同じ（↓）
				$mode = IMG_FLIP_VERTICAL;
				break;
			case 5:		//反転して270度（←）
				$degrees = 270;
				$mode = IMG_FLIP_HORIZONTAL;
				break;
			default:
				return $img;
		}
		//反転(2,7,4,5)
		if (isset($mode)) {
			$img = imageflip($img, $mode);
		}
		//回転(8,3,6,7,5)
		if ($degrees > 0) {
			$img = imagerotate($img, $degrees, 0);
		}
		return $img;
	}

	public static function imageOrientation($filepath, $orientation = false)
	{
		$exif = exif_read_data($filepath);
		if (!$orientation) {
			$orientation = $exif['Orientation'] ?? 0;
		}
		if (!$orientation) {
			return true;
		}

		$mime = mime_content_type($filepath);
		$ext = FileManager::mimeTypeToExt($mime);
		$img = self::getImg($filepath, $mime);

		//画像ロード
		$img = self::rotateImage($img, $orientation);

		//保存
		self::outImg($img, $filepath, $mime, $ext);

		//メモリ解放
		imagedestroy($img);
		return ob_get_clean();
	}

	public static function resize ($src, $dst, $w = 100, $h = 100) {

		$mime = mime_content_type($src);
		$ext = FileManager::mimeTypeToExt($mime);
		$img = self::getImg($src, $mime);
		if (!$img) {
			return false;
		}

		$exif = $ext == 'jpg' ? exif_read_data($src) : false;

		$width = ImageSx($img);
		$height = ImageSy($img);

		$long = $width >= $height ? $width : $height;

		$newW = $width * $w / $long;
		$newH = $height * $h / $long;

		if ($newW > $newH) {
			// 横長だったら
		} elseif ($newH > $newW) {
			// 縦長
		} else {
		}

		$out = ImageCreateTrueColor($newW, $newH);
		ImageCopyResampled($out, $img,
			0,0,0,0, $newW, $newH, $width, $height);

		if (isset($exif['Orientation'])) {
			$out = self::rotateImage($out, $exif['Orientation']);
		}

		self::outImg($out, $dst, $mime, $ext);

		return true;
	}

	private static function outImg ($out, $dst, $mime, $ext) {
		if ($mime == 'image/png' || $ext == 'png') {
			imagepng($out, $dst);
		} elseif ($mime == 'image/jpeg' || $ext == 'jpg') {
			imagejpeg($out, $dst);
		} elseif ($mime == 'image/gif' || $ext == 'gif') {
			imagegif($out, $dst);
		}
	}

	private static function getImg ($src, $mime) {
		if ($mime == 'image/png') {
			return ImageCreateFromPNG($src);
		} elseif ($mime == 'image/jpeg') {
			return ImageCreateFromJPEG($src);
		} elseif ($mime == 'image/gif') {
			return ImageCreateFromGIF($src);
		} else {
			return false;
		}
	}
}