<?php

ini_set('max_file_uploads', 10);

ini_set('display_errors', "off");

const MEOW_VERSION = "0.0.1";

const MEOW_IS_DEBUG = false;

define('MEOW_CONFIG_BASE_DIR', dirname(dirname(__DIR__)));
const MEOW_CONFIG_FILE_PATH = MEOW_CONFIG_BASE_DIR . "/application/config/meow_config.json";
if (!is_file(MEOW_CONFIG_FILE_PATH)) {
	$meowConfig = [
		'salt' => md5(time()),
		'bgcolor' => 'rgb(220, 220, 220)',
	];
	file_put_contents(MEOW_CONFIG_FILE_PATH, json_encode($meowConfig, JSON_UNESCAPED_SLASHES));
}
$meowConfig = json_decode(file_get_contents(MEOW_CONFIG_FILE_PATH));
define('MEOW_CONFIG_SITE_NAME', $meowConfig->siteName ?? 'meow');
define('MEOW_CONFIG_FQDN', $meowConfig->FQDN ?? $_SERVER['REMOTE_ADDR']);
define('MEOW_CONFIG_SALT', $meowConfig->salt);
define('MEOW_CONFIG_ADMIN_PASS_HASH', $meowConfig->adminPassHash ?? '');

define('MEOW_CONFIG_DB_HOST', $meowConfig->dbHost ?? '');
define('MEOW_CONFIG_DB_USER', $meowConfig->dbUser ?? '');
define('MEOW_CONFIG_DB_PASSWORD', $meowConfig->dbPassword ?? '');
define('MEOW_CONFIG_DB_NAME', $meowConfig->dbName ?? '');

/**
 * Class MY_Controller
 *
 * @property CI_Config $config
 * @property CI_Input $input
 * @property CI_Email $email
 *
 * @property CI_DB_mysqli_driver $db
 *
 * @property CI_Session $session
 *
 * @property Twig $twig
 */
class MY_Controller extends CI_Controller {

	public function __construct() {

		if (MEOW_IS_DEBUG) {
			ini_set('display_errors', "On");
		}

		parent::__construct();

		$this->config->set_item('base_url', 'https://' . MEOW_CONFIG_FQDN . "/");

		$this->load->helper('form');
		$this->load->helper('url');

		$this->load->library('twig');

		if (MEOW_CONFIG_DB_HOST && MEOW_CONFIG_DB_USER && MEOW_CONFIG_DB_PASSWORD && MEOW_CONFIG_DB_NAME) {
			$dbConfig = [
				'dsn' => '',
				'hostname' => MEOW_CONFIG_DB_HOST,
				'username' => MEOW_CONFIG_DB_USER,
				'password' => MEOW_CONFIG_DB_PASSWORD,
				'database' => MEOW_CONFIG_DB_NAME,
				'dbdriver' => 'mysqli',
				'dbprefix' => '',
				'pconnect' => FALSE,
				'db_debug' => (ENVIRONMENT !== 'production'),
				'cache_on' => FALSE,
				'cachedir' => '',
				'char_set' => 'utf8mb4',
				'dbcollat' => 'utf8mb4_general_ci',
				'swap_pre' => '',
				'encrypt' => FALSE,
				'compress' => FALSE,
				'stricton' => FALSE,
				'failover' => array(),
				'save_queries' => TRUE
			];
			$this->load->database($dbConfig);
		}

		$this->load->library('FileManager');

		session_start();
	}

	static $me;
	protected function getMe () {

		if (self::$me) {
			if (self::$me->is_admin) {
				ini_set('display_errors', "on");
			}
			return self::$me;
		}

		$mid = $_COOKIE['mid'] ?? '';
		$auth_key = $_COOKIE['auth'] ?? '';

		// ログインしてる？
		if ($mid && $auth_key) {
			$sql = " select * from user where mid = ? and auth_key = ? ";
			if ($me = $this->db->query($sql, [$mid, $auth_key])->row()) {
				if ($me->icon) {
					$me->prof_path = MeowManager::getProfPath($me->id);
					$me->icon_path = $me->icon ? "{$me->prof_path}/{$me->icon}" : '/assets/icons/cat_footprint.png';
				}
				$me->post_is_private = ($_COOKIE['post_is_private'] ?? 0);

				$me->actor = new \stdClass();
				$me->actor->id = Meow::BASE_URL . "/u/" . $me->mid;

				self::$me = $me;
				return self::$me;
			}
		}

		return false;
	}

	protected function getSalt () {

	}

	protected function apiOk () {
		print 1;
		exit;
	}
	protected function apiError () {
		print 0;
		exit;
	}
	protected function getMeOrApiError () {
		$me = $this->getMe();
		if (!$me) {
			$this->apiError();
		}
		return $me;
	}

	protected function getMeOrJumpTop () {
		($me = $this->getMe()) || $this->toTop();
		return $me;
	}

	protected function toTop () {
		header('location: /');
		exit;
	}

	protected function getValues ($columns) {
		$values = [];
		foreach ($columns as $column) {
			$values[$column] = $this->input->post_get($column);
		}
		return $values;
	}

	protected function file_post_contents ($url, $params) {

		// POSTデータ
		$data = http_build_query($params);

		// header
		$header = array(
			"Content-Type: application/x-www-form-urlencoded",
			"Content-Length: ".strlen($data)
		);

		$context = array(
			"http" => array(
				"method"  => "POST",
				"header"  => implode("\r\n", $header),
				"content" => $data
			)
		);

		return file_get_contents($url, false, stream_context_create($context));
	}

	protected function display ($template, $values = []) {

		header('Permissions-Policy: interest-cohort=()');

		$values['me'] = self::getMe();
		$values['fontSize'] = $_COOKIE['fontSize'] ?? 'medium';
		if (!isset($values['enableSearch'])) {
			$values['enableSearch'] = 1;
		}
		if (!isset($values['enableMeowStartButton'])) {
			$values['enableMeowStartButton'] = 1;
		}

		global $meowConfig;
		$config = $meowConfig;
		$config->host = $config->FQDN;
		$config->fqdn = $config->FQDN;
		$config->baseUrl = "https://" . $config->FQDN;
		$values['config'] = $config;


		$this->twig->display($template, $values);
		return true;
	}
}
