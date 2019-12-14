<?php

/* Enable error reporting */

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function load_templating($cache) {
	// only use cache if enabled in config
	$use_cache = isset($cache['enable']) && $cache['enable'];

	$loader = new FilesystemLoader('views');
	$opts   = [];

	if ($use_cache) {
		$opts['cache'] = @$cache['path'] ?? '/tmp/twig/cache';
	}

	return new Environment($loader, $opts);
}


class Config {
	/**
	 * @var array|false
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

	function get($key, $default = null) {
		$c = $this->config;
		if (isset($c[$key])) {
			return $c[$key];
		}

		return $default;
	}
}


function load_config() {

}