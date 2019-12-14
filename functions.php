<?php

/* Enable error reporting */

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/**
 * setup the templating engine
 *
 * @param array $cache settings about cache
 *
 * @return Environment: templating instance
 */
function load_templating($cache) {
	// only use cache if enabled in config

	$loader = new FilesystemLoader('views');
	$opts   = [];

	if (@$cache['enable']) {
		$opts['cache'] = @$cache['path'] ?? '/tmp/twig/cache';
	}

	return new Environment($loader, $opts);
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
