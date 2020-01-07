<?php

/* Require composer autoloader */

require __DIR__ . '/vendor/autoload.php';

use Bramus\Router\Router;


/* include all models from the model folder here */

foreach (glob("models/*.php") as $filename) {
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
	echo $twig->render('index.twig', ['name' => $name, 'availablerooms' => '(iets uit de db)']);

});


$router->mount('/rooms', function() use ($router, $db, $twig) {

	/* GET for getting an overview of all rooms */
	$router->get('/', function() use ($db, $twig) {

		$rooms = $db->room->find('all');

		echo $twig->render('rooms.twig', ['all_rooms'=>$rooms]);
	});

	/* GET for reading specific rooms */
	$router->get('/(\d+)', function($id) use ($db, $twig) {
		$room = $db->room->get($id);
		echo $twig->render('room.twig', ['room'=>$room]);


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
	$router->post('/new', function() use ($db) {

		$room_data = [
			'description' => @$_POST['description'],
			'price'       => @$_POST['price'],
			'size'        => @$_POST['size'],
			'type'        => @$_POST['type'],
			'city'        => @$_POST['city'],
			'zipcode'     => @$_POST['zipcode'],
			'street_name' => @$_POST['street_name'],
			'number'      => @$_POST['number'],
			//todo: Add owner id to the list.
			'owner_id' => '1'
		];

		$errors = validate_room($room_data, $db->room);

		if ($errors) {
			// there are errors
			echo 'not allowed -> redirect signup with errors';
			pprint($errors);

			return;
		};

		$new_room = $db->room->newEntity($room_data);

		if ($new_room->getErrors()) {
			echo 'nee er ging iets mis -> redirect signup with errors';
			pprint($new_room->getErrors());

			return;
		}

		try {
			$result = $db->room->save($new_room);
		} catch (PDOException $e) {
			pprint($e);
			$result = false;
		}

		if ($result) {
			echo 'het ging goed -> redirect to room page';
			echo $new_room->id;
		} else {
			echo 'iets ging mis D: -> redirect to edit page';
		}

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
		echo $twig->render('account.twig', ['name' => '(get from db)']);
	});

	/* GET to view specific account */
	$router->get('/(\d+)', function($id) use ($db, $twig) {
		echo $twig->render('account.twig', []);
	});


	/* GET for adding account */
	$router->get('/signup', function() use ($db, $twig) {
		echo $twig->render('account_new.twig', []);
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
		// https://book.cakephp.org/3/en/orm/saving-data.html

		$user_data = [
			'username'     => @$_POST['username'],
			'password'     => @$_POST['password'],
			'first_name'   => @$_POST['first_name'],
			'last_name'    => @$_POST['last_name'],
			'email'        => @$_POST['email'],
			'phone_number' => @$_POST['phone_number'],
			'language'     => @$_POST['language'],
			'birthdate'    => @$_POST['birthdate'],
			'biography'    => @$_POST['biography'],
			'occupation'   => @$_POST['occupation'],
			'role'         => @$_POST['role']
		];


		$errors = validate_user($_POST, $db->user);

		if ($errors) {
			// there are errors
			echo 'not allowed -> redirect signup with errors';
			pprint($errors);

			return;
		};

		$new_user = $db->user->newEntity($user_data);


		if ($new_user->getErrors()) {
			// Entity failed validation.
			echo 'nee er ging iets mis -> redirect signup with errors';
			pprint($new_user->getErrors());

			return;
		}
		// no errors

		try {
			$result = $db->user->save($new_user);
		} catch (PDOException $e) {
			$result = false;
			pprint($e);
		}
		if ($result) {
			echo 'het ging goed -> redirect account page';
			echo $new_user->id;

			return;
		} else {
			echo 'iets ging mis :( -> redirect signup';

			return;
		}


	});

	/* DELETE for removing your account */
	$router->post('/delete/(\d+)', function($id) use ($db) {

	});

});

/* Run the router */
$router->run();