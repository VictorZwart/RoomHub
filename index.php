<?php

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
$db = new DB($config);

/* Create Router instance */
$router = new Router();

/* setup templating */
$twig = load_templating($config->get('cache', []), $router->getBasePath());


// Add routes here

// regular 404
$router->set404(function() {
	header('HTTP/1.1 404 Not Found');
	// ... do something special here

	echo 'This page could not be found.';

});


// GET for welcome page
$router->get('/', function() use ($db, $twig) {
	$name = $db->user->find()->first()->first_name;
	echo $twig->render('index.twig', ['name' => $name]);

});


$router->mount('/rooms', function() use ($router, $db, $twig) {

	/* GET for getting an overview of all rooms */
	$router->get('/', function() use ($db, $twig) {
		echo $twig->render('rooms.twig', []);
	});

	/* GET for reading specific rooms */
	$router->get('/(\d+)', function($id) use ($db, $twig) {
		echo $twig->render('room.twig', []);


	});

	/* GET for editing room */
	$router->get('/edit/(\d+)', function($id) use ($db, $twig) {

		$form_html = crud($twig, $db->room, ['id']);

		echo $twig->render('room_edit.twig', ['form' => $form_html]);
	});

	/* GET for adding room */
	$router->get('/new', function() use ($db, $twig) {
		echo $twig->render('room_new.twig', []);
	});


	/* DELETE for removing your own room */
	$router->delete('/(\d+)', function($id) use ($db) {

	});


	/* POST for adding room */
	$router->post('/', function() use ($db) {
	});


	/* PUT for editing room */
	$router->put('/(\d+)', function($id) use ($db) {
		$_PUT = array();
		parse_str(file_get_contents('php://input'), $_PUT);
	});

});

$router->mount('/account', function() use ($router, $db, $twig) {

	/* GET to view your account */
	$router->get('/', function() use ($db, $twig) {
		echo $twig->render('account.twig', []);
	});

	/* GET to view specific account */
	$router->get('/(\d+)', function($id) use ($db, $twig) {
		echo $twig->render('account.twig', []);
	});


	/* GET for adding account */
	$router->get('/signup', function() use ($db, $twig) {
		echo $twig->render('new_account.twig', []);
	});

	/* GET for editing account */
	$router->get('/edit/(\d+)', function($id) use ($db, $twig) {
		echo $twig->render('account_edit.twig', []);
	});

	/* PUT for editing account */
	$router->post('/update/(\d+)', function($id) use ($db) {
		$_PUT = array();
		parse_str(file_get_contents('php://input'), $_PUT);
	});

	/* POST for adding account */
	$router->post('/signup', function() use ($db) {
		// todo: logic for validating and inserting
		echo 'WIP';

	});

	/* DELETE for removing your account */
	$router->post('/delete/(\d+)', function($id) use ($db) {

	});

});

/* Run the router */
$router->run();