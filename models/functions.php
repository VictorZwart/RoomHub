<?php namespace RoomHub;

/* Enable error reporting */

use Cake\ORM\Table;
use Twig\{Environment, Extension\DebugExtension, TwigFunction};
use Twig\Loader\FilesystemLoader;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * setup the templating engine
 *
 * @param Config $config settings
 *
 * @return Environment: templating instance
 */
function load_templating($config) {
	// only use cache if enabled in config
	$cache    = $config->get('cache', []);
	$debug    = $config->get('debug', []);
	$basepath = $_SERVER['basepath'];

	$loader = new FilesystemLoader('views');
	$opts   = [];

	if (@$cache['enable']) {
		$opts['cache'] = @$cache['path'] ?? '/tmp/twig/cache';
	}

	$debug_enabled = @$debug['enable'];

	if ($debug_enabled) {
		$opts['debug'] = true;
	}


	$twig = new Environment($loader, $opts);

	if ($debug_enabled) {
		$twig->addExtension(new DebugExtension());
	}

	$db = $_SERVER['db'];

	$current_user = [];

	if (isset($_SESSION['user_id'])) {
		$current_user = get_info($db->user, 'user_id', $_SESSION['user_id']);
		if (!$current_user) {
			// user was removed, give them a new session
			session_destroy();
			session_start();
		} else {
			$current_user['loggedin'] = true;
		}
	}

	$twig->addGlobal('user', $current_user);
	$twig->addGlobal('feedback', @$_SESSION['feedback']);


	$twig->addFunction(new TwigFunction('static',
		function($relative_path) use ($basepath) {
			// load static files from base path
			return $basepath . 'static/' . $relative_path;
		}));


	// link to an uploaded file using for example {{ uploads('rooms/room-1.jpg') }}
	$twig->addFunction(new TwigFunction('uploads',
		function($relative_path) use ($basepath) {
			// load static files from base path
			return $basepath . 'uploads/' . $relative_path;
		}));

	$twig->addFunction(new TwigFunction('base',
		function() use ($basepath) {
			// load static files from base path
			return $basepath;
		}));

	$twig->addFunction(new TwigFunction('url', function($url) use ($basepath) {
		return $basepath . $url;
	}));

	return $twig;

}

/**
 * gets the info from the given table for the give nid_name with the id given
 *
 * @param Table $table mixed the user table
 * @param string $id_name name of the id in the table
 * @param string $id id
 * @param array $options list of cake options (like contain)
 *
 * @return mixed array with all the account info
 */
function get_info($table, $id_name, $id, $options = []) {
	return $table
		->find('all', $options)
		->where([$id_name => $id])
		->first();
}

class Config {
	/**
	 * Config holds all settings that can be set in config.ini
	 */
	private $config;

	function __construct() {
		if (file_exists('config.ini')) {
			$inifile = 'config.ini';
		} else {
			$inifile = 'config.example.ini';
		}

		$this->config = parse_ini_file($inifile, true);
	}

	/**
	 * Syntactic sugar for using config with a fallback value (like .get in python)
	 *
	 * @param string $key the setting you are looking for
	 * @param mixed $default fallback value if key is not found
	 *
	 * @return mixed value of the key of default
	 */
	function get($key, $default = null) {
		$c = $this->config;
		if (isset($c[$key])) {
			return $c[$key];
		}

		return $default;
	}
}

function fix_phone($phone_number) {
	$phone_number = $phone_number ?: '';
	if (strpos($phone_number, '-') !== false) {
		$phone_number = str_replace('-', '', $phone_number);
	}

	return $phone_number;
}

/**Takes the db name and the db and the id and then saves the picture to the database
 * with a name consisting of the dbname and the id
 *
 * @param $id int an id for either room or user
 * @param $db mixed connection to the database
 * @param $dbname string either 'user' or 'room'
 *
 * @return bool whether it succeeded
 */
function handle_file_upload($id, $db, $dbname) {
	$errors         = []; // Store all foreseen and unforseen errors here
	$fileExtensions = ['jpeg', 'jpg', 'png']; // Get all the file extensions
	$fileName       = $_FILES['fileToUpload']['name'];
	$fileSize       = $_FILES['fileToUpload']['size'];
	$fileTmpName    = $_FILES['fileToUpload']['tmp_name'];

	if (!$fileTmpName) {
		// no file was uploaded
		return true;
	}


	$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
	$newfileName   = $dbname . $id . '.' . $fileExtension;
	$uploadFolder  = 'uploads/' . $dbname;

	if (!file_exists($uploadFolder)) {
		mkdir($uploadFolder, 0777, true);
	}

	$uploadPath = realpath($uploadFolder) . '/' . basename($newfileName);

	if (!in_array($fileExtension, $fileExtensions)) {
		$errors[] = "This file extension is not allowed. Please upload a JPG or PNG file";
	}
	if ($fileSize > 2000000) {
		$errors[] = "This file is more than 2MB. Sorry, it has to be less than or equal to 2MB";
	}
	if (empty($errors)) {
		if (file_exists($uploadPath)) {
			// enable 'overwrite'
			unlink($uploadPath);
		}

		$didUpload = move_uploaded_file($fileTmpName, $uploadPath);
		if ($didUpload) {
			$dbpic  = [
				'picture' => $newfileName
			];
			$active = $db->$dbname->get($id);
			$db->$dbname->patchEntity($active, $dbpic);

			safe_save($active, $db->$dbname);

			return true;
		} else {
			$_SESSION['feedback'] = ['message' => "Something went wrong uploading your picture."];
		}
	} else {
		$_SESSION['feedback'] = [
			'message' => "Something went wrong uploading your picture.",
			'errors'  => $errors
		];
	}

	return false;
}

// TODO: write docs

// debug
function pprint($something) {
	echo '<pre>';
	print_r($something);
	echo '</pre>';
}

// redirect with optional feedback
function redirect($to, $feedback = null, $feedback_state = null) {

	if ($feedback) {
		$fb = ['message' => $feedback];
		if ($feedback_state) {
			$fb['state'] = $feedback_state;
		}
		$_SESSION['feedback'] = $fb;
	}

	$basepath = $_SERVER['basepath'];
	header("Location: $basepath$to");
	die();
}


function require_login() {
	if (!isset($_SESSION['user_id'])) {
		$_SESSION['feedback'] = ['message' => 'Please log-in first!'];
		redirect('account/login');
	}
}

function require_anonymous($fallback = 'account') {
	if (isset($_SESSION['user_id'])) {
		redirect($fallback);
	}

}

function require_exists($item) {
	if (!$item) {
		$_SESSION['feedback'] = ['message' => 'This room does not exist.'];
		redirect('rooms');
	}
}

function require_mine($item) {
	require_exists($item);

	if ($item['owner_id'] !== $_SESSION['user_id']) {
		$_SESSION['feedback'] = ['message' => 'This room does not belong to you.'];
		redirect('rooms');
	}
}