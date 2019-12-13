<?php

/* Require composer autoloader */

require __DIR__ . '/vendor/autoload.php';

include 'models.php';

/* load config from config.ini or config.example.ini */
$config = load_config();

/* Connect to DB */
$db = connect_db($config['db']);


/* setup templating */
$twig = load_templating($config['cache']);

/* Create Router instance */
$router = new Bramus\Router\Router();

// Add routes here

// regular 404
$router->set404(function() {
	header('HTTP/1.1 404 Not Found');
	// ... do something special here

	echo 'This page could not be found.';

});


// welcome page
$router->get('/', function() use ($db, $twig) {
	echo $twig->render('index.html', ['name' => 'Fabien']);

});

/* GET for getting an overview of all rooms */
$router->get('/rooms', function() use ($db) {
	echo 'rooms here';
});

/* GET for reading specific rooms */
$router->get('/rooms/(\d+)', function($id) use ($db) {

});
/* GET to view specific account */
$router->get('/account/(\d+)', function($id) use ($db) {

});

/* DELETE for removing your own room */
$router->delete('/rooms/(\d+)/delete', function($id) use ($db) {


});

/* POST for adding room*/
$router->post('/rooms', function() use ($db) {
});

/* PUT for Editing rooms */
$router->put('/rooms/(\d+)', function($id) use ($db) {
	$_PUT = array();
	parse_str(file_get_contents('php://input'), $_PUT);

});
/* PUT for Editing account */
$router->put('/account/(\d+)', function($id) use ($db) {
	$_PUT = array();
	parse_str(file_get_contents('php://input'), $_PUT);

});


/* Run the router */
$router->run();
