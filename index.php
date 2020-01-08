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
$twig = load_templating($config);


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
	$router->get('/edit/(\d+)', function($room_id) use ($db, $twig) {
		require_login();
		$db_room_info = get_info($db->room, 'room_id', $room_id);

		if (!$db_room_info) {
			$_SESSION['feedback'] = ['message' => 'This room does not exist.'];
			redirect('rooms');
		}

		if ($db_room_info['owner_id'] !== $_SESSION['user_id']) {
			$_SESSION['feedback'] = ['message' => 'This room does not belong to you.'];
			redirect('rooms');
		}

		if (@$_SESSION['post']){
		    $room_info = $_SESSION['post'];
        }
		else {
		    $room_info = $db_room_info;
        }


		echo $twig->render('room_new.twig', ['room_info' => $room_info]);
	});

	/* GET for adding room */
	$router->get('/new', function() use ($db, $twig) {
		$owner_role = get_info($db->user, 'user_id', $_SESSION['user_id'])['role'];
		if ($owner_role !== 'owner') {
			$_SESSION['feedback'] = ['message' => 'You should be listed as owner to publish a room!'];
			redirect('account/login');
		}
		echo $twig->render('room_new.twig', ['room_info' => @$_SESSION['post']]);
	});


	/* DELETE for removing your own room */
	$router->delete('/(\d+)', function($id) use ($db) {

	});


	/* POST for adding room */
	$router->post('/new', function() use ($db) {

		$_SESSION['post'] = $_POST;
		$room_data = [
			'description' => @$_POST['description'],
			'price'       => @$_POST['price'],
			'size'        => @$_POST['size'],
			'type'        => @$_POST['type'],
			'city'        => @$_POST['city'],
			'zipcode'     => @$_POST['zipcode'],
			'street_name' => @$_POST['street_name'],
			'number'      => @$_POST['number'],
			'owner_id'    => @$_SESSION['user_id']
		];

        $errors = validate_room($room_data, $db->room);

		if ($errors) {

			$_SESSION['feedback'] = ['message' => 'Some fields were not filled in correctly!', 'errors' => $errors];

			// there are errors
			redirect('rooms/new');
		};

		$new_room = $db->room->newEntity($room_data);


		$result = safe_save($new_room, $db->room);

		if ($result) {
			$room_id = $result->room_id;

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
                } else {
                    echo "An error occurred somewhere. Try again or contact the admin";
                }
            } else {
                foreach ($errors as $error) {
                    echo $error . "These are the errors" . "\n";
                }
            }


			$_SESSION['feedback'] = ['message' => 'Room successfully created!', 'state' => 'success'];
			redirect("rooms/$room_id");
		} else {
			redirect('rooms/new');
		}


	});


	/* POST for editing room */
	$router->post('/edit/(\d+)', function($room_id) use ($db) {
        $_SESSION['post'] = $_POST;
	    $room_data = [
	        'description' => $_POST['description'],
            'price' => $_POST['price'],
            'size' => $_POST['size'],
            'type' => $_POST['type'],
            'city' => $_POST['city'],
            'zipcode' => $_POST['zipcode'],
            'street_name' => $_POST['street_name'],
            'number' => $_POST['number']
            ];
        pprint($room_data);
        $errors = validate_room($room_data, $db->room);

        if ($errors) {
            // there are errors
	        $_SESSION['feedback'] = ['message' => 'Some fields were not filled in correctly!', 'errors' => $errors];

            redirect("rooms/edit/$room_id");
        };
        $active_room = $db->room->get($room_id);
        $db->room->patchEntity($active_room, $room_data);

        $result = safe_save($active_room, $db->room);

        if ($result) {
	        $_SESSION['feedback'] = ['message' => 'Room successfully updated!', 'state' => 'success'];
	        redirect("rooms/$room_id");
        } else {
            redirect("rooms/edit/$room_id");
        }
	});

});

$router->mount('/account', function() use ($router, $db, $twig) {

	/* GET to view your account */
	$router->get('/', function() use ($db, $twig) {
		require_login();
		echo $twig->render('account.twig', []);
	});

	/* GET to view specific account by username */
	$router->get('/u/(\w+)', function($username) use ($db, $twig) {
		$user_info = get_info($db->user, 'username', $username);

		if (!$user_info) {
			$_SESSION['feedback'] = ['message' => 'This user does not exist!'];

			redirect('account');
		}

		// we dont want that in the front end!
		unset($user_info['password']);

		echo $twig->render('account.twig', ['user' => $user_info]);
	});


	/* GET for adding account */
	$router->get('/signup', function() use ($db, $twig) {
		require_anonymous();
		$ctx = [
			'role_default' => @$_SESSION['post']['role'] ?: strtolower(@$_GET['role'] ?: ''),
			'account_info' => @$_SESSION['post'],
			'birthdate' => @$_SESSION['post']['birthdate']
		];
		echo $twig->render('account_form.twig', $ctx);
	});

	/* GET for editing account */
	$router->get('/edit/', function() use ($db, $twig) {
		require_login();
		$account_id      = $_SESSION['user_id'];
		$db_account_info = get_info($db->user, 'user_id', $account_id);

		// if the user has tried (but failed) to update
		// then we use that info (+username from DB because that's missing from POST)
		if (@$_SESSION['post']) {
			$account_info = $_SESSION['post'];
			$account_info['username'] = $db_account_info['username'];
			$birthdate = $account_info['birthdate'];
		} else {
			$account_info = $db_account_info;
			$birthdate = $db_account_info['birthdate'];
		}

		$ctx = [
			'account_info' => $account_info,
			'role_default' => $account_info['role'],
			'birthdate' => $birthdate,
			'is_edit' => !isset($_SESSION['post']),
		];
		echo $twig->render('account_form.twig', $ctx);
	});

	/* GET for login page */

	$router->get('/login', function() use ($db, $twig) {
		require_anonymous();

		echo $twig->render('login.twig');
	});

	/* GET for logging out */
	$router->get('/logout', function() {
		session_destroy();
		session_start();
		$_SESSION['feedback'] = ['message' => 'Logged out successfully!!', 'state' => 'success'];
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
			$_SESSION['feedback'] = ['message' => 'Logged in successfully!', 'state' => 'success'];
			redirect('account');
		}
	});


	/* POST for editing account */
	$router->post('/edit/', function() use ($db) {
		require_login();


		$current_user = $db->user->get($_SESSION['user_id']);

		$user_data = [
			'first_name'   => @$_POST['first_name'],
			'last_name'    => @$_POST['last_name'],
			'email'        => @$_POST['email'],
			'phone_number' => fix_phone(@$_POST['phone_number']),
			'language'     => @$_POST['language'],
			'birthdate'    => @$_POST['birthdate'],
			'biography'    => @$_POST['biography'],
			'occupation'   => @$_POST['occupation'],
			'role'         => @$_POST['role']
		];


		$_SESSION['post'] = $_POST;
		$errors           = validate_user($user_data, $db->user, false);

		if ($errors) {
			// there are errors
			$_SESSION['feedback'] = ['message' => 'Some fields were not filled in correctly!', 'errors' => $errors];

			redirect('account/edit');
		};

		$db->user->patchEntity($current_user, $user_data);


		$result = safe_save($current_user, $db->user);

		if ($result) {
			$_SESSION['user_id'] = $result->user_id;
			$_SESSION['feedback'] = ['message' => 'Account successfully updated!', 'state' => 'success'];
			redirect('account');
		} else {
			redirect('account/edit');
		}


	});


	/* POST for adding account */
	$router->post('/signup', function() use ($db) {
		require_anonymous();

		$_SESSION['post'] = $_POST;
		$errors           = validate_user($_POST, $db->user);

		if ($errors) {
			// there are errors
			$_SESSION['feedback'] = ['message' => 'Some fields were not filled in correctly!', 'errors' => $errors];

			redirect('account/signup');
		};

		$user_data = [
			'username'     => @$_POST['username'],
			'password'     => password_hash($_POST['password'], PASSWORD_DEFAULT),
			'first_name'   => @$_POST['first_name'],
			'last_name'    => @$_POST['last_name'],
			'email'        => @$_POST['email'],
			'phone_number' => fix_phone(@$_POST['phone_number']),
			'language'     => @$_POST['language'],
			'birthdate'    => @$_POST['birthdate'],
			'biography'    => @$_POST['biography'],
			'occupation'   => @$_POST['occupation'],
			'role'         => @$_POST['role']
		];

		$new_user = $db->user->newEntity($user_data);


		$result = safe_save($new_user, $db->user);

		if ($result) {
			$_SESSION['user_id'] = $result->user_id;
			$_SESSION['feedback'] = ['message' => 'Account successfully created!', 'state' => 'success'];
			redirect('account');
		} else {
			redirect('account/signup');
		}

	});

	/* DELETE for removing your account */
	$router->post('/delete/(\d+)', function($id) use ($db) {
		require_login();

	});

});

/* Run the router and cleanup session (remove feedback, post data etc) */
$router->run(function() {
	unset($_SESSION['feedback']);
	unset($_SESSION['post']);
});