<?php

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


	if (isset($_SESSION['user_id'])) {
		$current_user             = get_info($db->user, 'user_id', $_SESSION['user_id']);
		$current_user['loggedin'] = true;
	} else {
		$current_user = [];
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
 *
 * @return mixed array with all the account info
 */
function get_info($table, $id_name, $id) {
	return $table
		->find('all')
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





function handle_file_upload($room_id){
	// todo: edit path so it works
	$uploadDirectory = 'home/roomhub/public_html/uploads/images/roomuploads';
	$errors = []; // Store all foreseen and unforseen errors here
	$fileExtensions = ['jpeg','jpg','png']; // Get all the file extensions
	$fileName = $_FILES['fileToUpload']['name'];
	$fileSize = $_FILES['fileToUpload']['size'];
	$fileTmpName  = $_FILES['fileToUpload']['tmp_name'];
	$fileType = $_FILES['fileToUpload']['type'];
	$fileExtension = strtolower(end(explode('.',$fileName)));
	$newfileName = 'room' . $room_id . $fileExtension;
	$uploadPath = $uploadDirectory . basename($newfileName);
	if (! in_array($fileExtension,$fileExtensions)) {
		$errors[] = "This file extension is not allowed. Please upload a JPEG or PNG file";
	}
	if ($fileSize > 2000000) {
		$errors[] = "This file is more than 2MB. Sorry, it has to be less than or equal to 2MB";
	}
	if (empty($errors)) {
		$didUpload = move_uploaded_file($fileTmpName, $uploadPath);
		if ($didUpload) {
			echo "The file " . basename($fileName) . " has been uploaded";
			return true;
		} else {
			echo "An error occurred somewhere. Try again or contact the admin";
		}
	} else {
		foreach ($errors as $error) {
			echo $error . "These are the errors" . "\n";
		}
	}
}


// debug
function pprint($something) {
	echo '<pre>';
	print_r($something);
	echo '</pre>';
}

function redirect($to) {
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
		$_SESSION['feedback'] = ['message' => 'You are already logged in!'];
		redirect($fallback);
	}
}