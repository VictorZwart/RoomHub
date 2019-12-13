<?php

/* Enable error reporting */

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function load_templating($cache) {
	// only use cache if enabled in config
	$cache = isset($cache['enable']) && $cache['enable'];

	$loader = new FilesystemLoader('views');
	$opts   = [];
	if ($cache) {
		$opts['cache'] = '/tmp/twig/cache';
	}

	return new Environment($loader, $opts);
}


function load_config() {
	if (file_exists('config.ini')) {
		$inifile = 'config.ini';
	} else {
		$inifile = 'config.example.ini';
	}

	return parse_ini_file($inifile, true);
}