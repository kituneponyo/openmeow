<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/04/25
 * Time: 15:57
 */

class LibraryBase
{
	private static $db = null;

	/**
	 * @return CI_DB_mysqli_driver
	 */
	protected static function db() {
		if (!self::$db) {
			/** @var CI_Controller $CI */
			$CI =& get_instance();
			$CI->load->database();
			self::$db = $CI->db;
		}
		return self::$db;
	}
}