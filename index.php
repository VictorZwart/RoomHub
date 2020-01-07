<?php

/* Require composer autoloader */

require __DIR__ . '/vendor/autoload.php';

use Bramus\Router\Router;

session_start();


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
$twig = load_templating($config->get('cache', []));


// Add routes here

// regular 404
$router->set404(function() {
	header('HTTP/1.1 404 Not Found');
	// ... do something special here

	echo 'This page could not be found.';

});


// GET for welcome page
$router->get('/', function() use ($db, $twig) {
	require_anonymous('rooms');
	$name = $db->user->find()->first()->first_name;
	echo $twig->render('index.twig', ['availablerooms' => '(iets uit de db)']);

});


$router->mount('/rooms', function() use ($router, $db, $twig) {

	/* GET for getting an overview of all rooms */
	$router->get('/', function() use ($db, $twig) {

		$rooms = $db->room->find('all');

		echo $twig->render('rooms.twig', ['all_rooms' => $rooms]);
	});

	/* GET for reading specific rooms */
	$router->get('/(\d+)', function($id) use ($db, $twig) {
		$room = $db->room->get($id);
		echo $twig->render('room.twig', ['room' => $room]);


	});

	/* GET for editing room */
	$router->get('/edit/(\d+)', function($id) use ($db, $twig) {
	    $room_id = $_GET['room_id'];
        $room_info = get_info($db->room, 'room_id', $room_id);
    //todo: check if the owner and the user are the same person
        //        if ($room_info['owner_id'] !== $_SESSION['id']){
//            //check if the owner and the session user are the same person
//        }


        echo $twig->render('room_new.twig', ['room_info' => $room_info]);
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
			'owner_id'    => '1'
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
		} catch(PDOException $e) {
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
		require_login();
	    $account_info = [
	                        'firstname' => 'Henk',
	                        'lastname' => 'Westerbroek',
	                        'username' => 'henkie2',
	                        'phone_number' => '0643544354',
	                        'email' => 'nepemail@mail.com',
	                        'language' => 'Dutch',
	                        'birthdate' => 'test',
	                        'occupation' => 'test',
	                        'role' => 'Tenant',
	                        'biography' => 'Lorem ipsum',
	                    ];
		echo $twig->render('account.twig', $account_info);
	});

	/* GET to view specific account by username */
	$router->get('/u/(\w+)', function($username) use ($db, $twig) {
		$user_info = get_info($db->user, 'username', $username);

		if(!$user_info){
			redirect('account');
		}

		// we dont want that in the front end!
		unset($user_info['password']);

		echo $twig->render('account.twig', ['user' => $user_info]);
	});


	/* GET for adding account */
	$router->get('/signup', function() use ($db, $twig) {
		require_anonymous();
		echo $twig->render('account_new.twig', ['role_default' => strtolower(@$_GET['role'] ?: '')]);
	});

	/* GET for editing account */
	$router->get('/edit/', function() use ($db, $twig) {
		require_login();
		$account_id   = $_SESSION['user_id'];
		$account_info = get_info($db->user, 'user_id', $account_id);
		echo $twig->render('account_new.twig', ['account_info' => $account_info]);
	});

	/* GET for login page */

	$router->get('/login', function() use ($db, $twig) {
		require_anonymous();

		$feedback = @$_SESSION['feedback'] ?: [];

		echo $twig->render('login.twig', ['feedback' => $feedback]);
	});

	/* GET for logging out */
	$router->get('/logout', function() {
		session_destroy();
		redirect('');
	});

	/* POST for logging in */
	$router->post('/login', function() use ($db) {
		require_anonymous();

		// look for username OR email
		$user = $db->user->find()->where([
			'OR' => [
				['username' => $_POST['username']],
				['email' => $_POST['username']]
			]
		])->first();

		if (!$user) {
			$_SESSION['feedback'] = ['message' => 'This username is not known. Please sign up.'];

			redirect('account/login');
		}

		if (!password_verify($_POST['password'], $user->password)) {

			$_SESSION['feedback'] = [
				'message'  => 'This password is not correct.',
				'username' => $_POST['username'],
			];

			redirect('account/login');
		} else {

			$_SESSION['user_id'] = $user['user_id'];

			redirect('account');
		}
	});


	/* POST for editing account */
	$router->post('/update/', function() use ($db) {
		require_login();
		// TODO
	});


	/* POST for adding account */
	$router->post('/signup', function() use ($db) {
		require_anonymous();

		$errors = validate_user($_POST, $db->user);

		if ($errors) {
			// there are errors
			echo 'not allowed -> redirect signup with errors';
			pprint($errors);

			return;
		};
		// if there is a - in the phone numbers then remove that
		$phone_number = $_POST['phone_number'];

		$phone_number = @$_POST['phone_number'] ?: '';
		if (strpos($phone_number, '-') !== false) {
			$phone_number = str_replace('-', '', $phone_number);
		}

		$user_data = [
			'username'     => @$_POST['username'],
			'password'     => password_hash($_POST['password'], PASSWORD_DEFAULT),
			'first_name'   => @$_POST['first_name'],
			'last_name'    => @$_POST['last_name'],
			'email'        => @$_POST['email'],
			'phone_number' => $phone_number,
			'language'     => @$_POST['language'],
			'birthdate'    => @$_POST['birthdate'],
			'biography'    => @$_POST['biography'],
			'occupation'   => @$_POST['occupation'],
			'role'         => @$_POST['role']
		];

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
		} catch(PDOException $e) {
			$result = false;
			pprint($e);
		}
		if ($result) {
			echo 'het ging goed -> redirect account page';
			echo $new_user->id;

			$_SESSION['user_id'] = $new_user->id;

			return;
		} else {
			echo 'iets ging mis :( -> redirect signup';

			return;
		}


	});

	/* DELETE for removing your account */
	$router->post('/delete/(\d+)', function($id) use ($db) {
		require_login();

	});

});

/* Run the router */
$router->run();