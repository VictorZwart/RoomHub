<?php namespace RoomHub;

session_start();

/* Require composer autoloader */

require __DIR__ . '/vendor/autoload.php';

use Bramus\Router\Router;
use Cake\Datasource\Exception\RecordNotFoundException;


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
	echo $twig->render('index.twig', ['availablerooms' => '(iets uit de db)']);

});


$router->mount('/rooms', function() use ($router, $db, $twig) {


	/* GET for getting an overview of all rooms */
	$router->get('/', function() use ($db, $twig) {

//		echo $twig->render('rooms.twig', ['all_rooms' => $db->room->find()]);
//		return;

		// WIP:

		$me = $db->user->get($_SESSION['user_id']);

		if (@$_GET['filter']) {
			if ($_GET['filter'] == 'mine' && $me['role'] == 'owner') {
				$user_id = $me['user_id'];
			} else {
				$owner = get_info($db->user, 'username', $_GET['filter']);
				if (!$owner) {
					redirect('rooms');
				}
				$user_id = $owner->user_id;
			}

			// if you want to see your rooms or those of a user, you should just see rooms
			$listings = $db->room->find('all', ['contain' => 'Listing'])
			                     ->where(['owner_id' => $user_id]);

		} else {
			// else you should see listings
			$listings = $db->listing->find('all', ['contain' => 'Room'])
			                        ->where(['status' => 'open']);
		}


		echo $twig->render('rooms.twig', ['all_rooms' => $listings]);
	});

    /* GET for getting the opt_in form */
    $router->get('/opt-in/(\d+)', function($listing_id) use ($db, $twig) {
        require_login();
        $userdata = $db->user->get($_SESSION['user_id']);
        if($userdata['role'] !== 'tenant'){
            $_SESSION['feedback'] = ['message' => 'only tenants can react on a room!', 'state' => 'warning'];
            redirect("rooms");
        }
        $roomdata = $db->listing->get($listing_id, ['contain' => 'Room']);

        echo $twig->render('optinform.twig', ['room' => $roomdata, 'user' => $userdata]);
    });
	/* GET for reading specific rooms */
	$router->get('/(\d+)', function($id) use ($db, $twig) {
		$room = get_info($db->room, 'room_id', $id);
		require_exists($room);
		$listings = $db->listing->find()->where(['room_id' => $room['room_id']])->toList();

		echo $twig->render('room.twig', ['room' => $room, 'listings' => $listings]);


	});

	/* GET for adding listing */
	$router->get('/list/add/(\d+)', function($room_id) use ($twig, $db) {
		require_login();
		require_mine(get_info($db->room, 'room_id', $room_id));
		echo $twig->render('listing_form.twig', ['room_id' => $room_id]);
	});

	/* POST for adding listing */
	$router->post('/list/add/(\d+)', function($room_id) use ($db) {
		require_login();
		$listing_result = handle_add_listing($room_id, $_POST, $db->listing);
		if ($listing_result) {
			$_SESSION['feedback'] = ['message' => 'Room successfully listed!', 'state' => 'success'];
			redirect("rooms/$room_id");
		} else {
			redirect("rooms/list/add/$room_id");
		}
	});

	/* GET for editing listing */
	$router->get('/list/edit/(\d+)', function($listing_id) use ($twig, $db) {
		require_login();

		try {

			$listing_info = get_info($db->listing, 'listing_id', $listing_id, ['contain' => 'Room']);

			require_exists($listing_info);

			require_mine($listing_info['room']);

		} catch(RecordNotFoundException $e) {
			$_SESSION['feedback'] = ['message' => 'This listing does not exist.'];
			redirect("rooms");

			return;
		}
		if ($listing_info['status'] != 'open') {
			$_SESSION['feedback'] = ['message' => 'This listing is not active.'];
			redirect('rooms');
		}

		echo $twig->render('listing_form.twig', ['listing' => $listing_info, 'is_edit' => true]);
	});


	/* GET for canceling listing */
	$router->get('/list/cancel/(\d+)', function() {
		require_login();
		echo 'weg yeeten jwz';
	});

	/* POST for editing listing */
	$router->post('/list/edit/(\d+)', function($listing_id) use ($db) {
		require_login();

		$listing_info = $db->listing->get($listing_id);

		$room_id = $listing_info['room_id'];


		$listing_data = [
			'available_from' => @$_POST['available_from']
		];
		if (!@$_POST['is_indefinite'] == 'on') {
			// do something with available_to
			$listing_data['available_to'] = @$_POST['available_to'];
		}


		$db->listing->patchEntity($listing_info, $listing_data, ['validate' => 'update']);


		if (safe_save($listing_info, $db->listing)) {
			$_SESSION['feedback'] = ['message' => 'Listing successfully updated', 'status' => 'success'];
			redirect("rooms/$room_id");
		} else {
			redirect("rooms/list/edit/$listing_id");
		}


	});

	/* GET for editing room */
	$router->get('/edit/(\d+)', function($room_id) use ($db, $twig) {
		require_login();
		$db_room_info = get_info($db->room, 'room_id', $room_id);


		require_mine($db_room_info);

		if (@$_SESSION['post']) {
			$room_info = $_SESSION['post'];
		} else {
			$room_info = $db_room_info;
		}


		echo $twig->render('room_form.twig', ['room_info' => $room_info, 'is_edit' => true]);
	});

	/* GET for adding room */
	$router->get('/new', function() use ($db, $twig) {
		require_login();
		$owner_role = get_info($db->user, 'user_id', $_SESSION['user_id'])['role'];
		if ($owner_role !== 'owner') {
			$_SESSION['feedback'] = ['message' => 'You should be listed as owner to publish a room!'];
			redirect('account/login');
		}
		echo $twig->render('room_form.twig', ['room_info' => @$_SESSION['post']]);
	});


	/* DELETE for removing your own room */
	$router->delete('/(\d+)', function($id) use ($db) {
		require_login();
		echo 'TODO' . $id;
	});


	/* POST for adding opt in */
    $router->post('/opt-in/(\d+)', function($listing_id) use($db) {

        $optin_data = [
            'listing_id' => $listing_id,
            'user_id' => @$_SESSION['user_id'],
            'message' => @$_POST['message'],
            'date' => date('Y-m-d h:i:s')
        ];
        $new_message = $db->opt_in->newEntity($optin_data);
        pprint($new_message);
        $result = safe_save($new_message, $db->opt_in);
        pprint($result);
        if ($result) {
            $opt_in_id = $result->opt_in_id;
            if ($db->opt_in->get($opt_in_id)) {
                $_SESSION['feedback'] = ['message' => 'Message successfully sent!', 'state' => 'success'];
            } else {
                $_SESSION['feedback'] = [
                    'message' => 'Your message was not sent succesfully!',
                    'state' => 'alert'
                ];
            }
            redirect("rooms");
        }
    });


	/* POST for adding room */
	$router->post('/new', function() use ($db) {
		require_login();

		$_SESSION['post'] = $_POST;
		$room_data        = [
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


			if (handle_file_upload($room_id, $db, 'room')) {

				// validate listing

				$listing_result = handle_add_listing($room_id, $_POST, $db->listing);

				if ($db->room->get($room_id)->picture) {
					$_SESSION['feedback'] = ['message' => 'Room successfully created!', 'state' => 'success'];
				} else {
					$_SESSION['feedback'] = [
						'message' => 'Room successfully created but you did not add a picture!',
						'state'   => 'warning'
					];
				}

				if ($listing_result) {
					$_SESSION['feedback']['message'] .= ' Room successfully listed.';
				} else {
					$_SESSION['feedback']['message'] .= ' However, the room was not listed!';
					$_SESSION['feedback']['state']   = 'warning';
				}

				redirect("rooms/$room_id");
			} else {
				redirect("rooms/edit/$room_id");
			}
		} else {
			redirect('rooms/new');
		}


	});


	/* POST for editing room */
	$router->post('/edit/(\d+)', function($room_id) use ($db) {
		require_login();
		$_SESSION['post'] = $_POST;
		$room_data        = [
			'description' => @$_POST['description'],
			'price'       => @$_POST['price'],
			'size'        => @$_POST['size'],
			'type'        => @$_POST['type'],
			'city'        => @$_POST['city'],
			'zipcode'     => @$_POST['zipcode'],
			'street_name' => @$_POST['street_name'],
			'number'      => @$_POST['number']
		];
		$errors           = validate_room($room_data, $db->room);

		if ($errors) {
			// there are errors
			$_SESSION['feedback'] = ['message' => 'Some fields were not filled in correctly!', 'errors' => $errors];

			redirect("rooms/edit/$room_id");
		};
		$active_room = $db->room->get($room_id);
		require_mine($active_room);
		$db->room->patchEntity($active_room, $room_data);

		$result = safe_save($active_room, $db->room);

		if ($result) {
			if (handle_file_upload($room_id, $db, 'room')) {
				if ($db->room->get($room_id)->picture) {
					$_SESSION['feedback'] = ['message' => 'Room successfully updated!', 'state' => 'success'];
				} else {
					$_SESSION['feedback'] = [
						'message' => 'Room successfully updated but you did not add a picture!',
						'state'   => 'warning'
					];
				}
				redirect("rooms/$room_id");
			} else {
				redirect("rooms/edit/$room_id");
			}
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

	/* GET to view optins */
    $router->get('/opt-in/(\w +)', function($username) use($db, $twig){
        $me = $db->user->get($_SESSION['user_id']);
        

        echo $twig->render('listing_and_optins.twig', []);

//        $me = $db->user->get($_SESSION['user_id']);
//
//		if (@$_GET['filter']) {
//            if ($_GET['filter'] == 'mine' && $me['role'] == 'owner') {
//                $user_id = $me['user_id'];
//            } else {
//                $owner = get_info($db->user, 'username', $_GET['filter']);
//                if (!$owner) {
//                    redirect('rooms');
//                }
//                $user_id = $owner->user_id;
//            }
//
//            // if you want to see your rooms or those of a user, you should just see rooms
//            $listings = $db->room->find('all', ['contain' => 'Listing'])
//                ->where(['owner_id' => $user_id]);
//
//        } else {
//            // else you should see listings
//            $listings = $db->listing->find('all', ['contain' => 'Room'])
//                ->where(['status' => 'open']);
//        }
//
//
//		echo $twig->render('rooms.twig', ['all_rooms' => $listings]);
    });

	/* GET to view specific account by username */
	$router->get('/u/(\w +)', function($username) use ($db, $twig) {
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
			'birthdate'    => @$_SESSION['post']['birthdate']
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
			$account_info             = $_SESSION['post'];
			$account_info['username'] = $db_account_info['username'];
			$birthdate                = $account_info['birthdate'];
		} else {
			$account_info = $db_account_info;
			$birthdate    = $db_account_info['birthdate'];
		}

		$ctx = [
			'account_info' => $account_info,
			'role_default' => $account_info['role'],
			'birthdate'    => $birthdate,
			'is_edit'      => !isset($_SESSION['post']),
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

			$_SESSION['user_id']  = $user['user_id'];
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
			$_SESSION['user_id'] = $user_id = $result->user_id;
			if (handle_file_upload($user_id, $db, 'user')) {
				if ($db->user->get($user_id)->picture) {
					$_SESSION['feedback'] = ['message' => 'Account successfully updated!', 'state' => 'success'];
				} else {
					$_SESSION['feedback'] = [
						'message' => 'Account successfully updated but you did not add a picture!',
						'state'   => 'warning'
					];
				}
				redirect('account');
			} else {
				redirect('account/edit');
			}
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
			$_SESSION['user_id'] = $user_id = $result->user_id;
			if (handle_file_upload($user_id, $db, 'user')) {
				if ($db->user->get($user_id)->picture) {
					$_SESSION['feedback'] = ['message' => 'Account successfully created!', 'state' => 'success'];
				} else {
					$_SESSION['feedback'] = [
						'message' => 'Account successfully created but you did not add a picture!',
						'state'   => 'warning'
					];
				}
				redirect('account');
			} else {
				redirect('account/edit');
			}
		} else {
			redirect('account/signup');
		}

	});

	/* DELETE for removing your account */
	$router->post('/delete/(\d+)', function($id) use ($db) {
		require_login();
		echo 'TODO' . $id;
	});

});

/* Run the router and cleanup session (remove feedback, post data etc) */
$router->run(function() {
	unset($_SESSION['feedback']);
	unset($_SESSION['post']);
});