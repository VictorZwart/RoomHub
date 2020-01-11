<?php namespace RoomHub;

session_start();

/* Require composer autoloader */

require __DIR__ . '/vendor/autoload.php';

use Bramus\Router\Router;


/* include all models from the model folder here */

foreach(glob("models/*.php") as $filename) {
	include $filename;
}

/* load config from config.ini or config.example.ini */
$config = new Config();


/* Connect to DB */
$db = $_SERVER['db'] = new DB($config);

/* Create Router instance */
$router = new Router();

$_SERVER['basepath'] = $router->getBasePath();

/* setup templating */
$twig = load_templating($config);

require_once('controller/room.php');
require_once('controller/account.php');

// regular 404
$router->set404(function() {
	header('HTTP/1.1 404 Not Found');
	// ... do something special here

	echo 'This page could not be found.';

});


// GET for welcome page
$router->get('/', function() use ($db, $twig) {
	require_anonymous('rooms');
	echo $twig->render('index.twig', ['availablerooms' => '(iets uit de db)']);

});


// class-based controller Test

//class TestController {
//	private $my_config;
//
//	function __construct($cfg) {
//		$this->my_config = $cfg;
//	}
//
//	function test_function(){
//		pprint($this->my_config);
//	}
//}
//
//$test_controller = new TestController(123);
//
//
//$router->get('/test', 'TestController@test_function');

    /*mount for the room page*/
$router->mount('/rooms', function() use ($router, $db, $twig) {
    new RoomController($router, $db, $twig);
});

    /*mount for the account page*/
$router->mount('/account', function() use ($router, $db, $twig) {
    new AccountController($router, $db, $twig);
});

/* Run the router and cleanup session (remove feedback, post data etc) */
$router->run(function() {
	unset($_SESSION['feedback']);
	unset($_SESSION['post']);
});