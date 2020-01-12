<?php namespace RoomHub;


class AccountController {
	public function __construct($router, $db, $twig) {

		/* GET to view your account */
		$router->get('/', function() use ($db, $twig) {
			require_login();
			echo $twig->render('account.twig', []);
		});

		/* GET to view optins */
		$router->get('/opt-in', function() use ($db, $twig) {
			require_login();
			$me = $db->user->get($_SESSION['user_id']);
			//check to see if the user is a tenant
			if ($me['role'] == 'owner') {
				$_SESSION['feedback'] = ['message' => 'An owner cannot view opt-ins!', 'state' => 'alert'];
				redirect('account');
			}

			$all_info = $db->opt_in->find('all', ['contain' => 'Listing.room'])
			                       ->where(['user_id' => $me['user_id']])->toList();

			echo $twig->render('opt_ins.twig', ['all_info' => $all_info]);
		});

		/* GET to view all your reactions */
		$router->get('/reactions', function() use ($db, $twig) {
			require_login();
			$me = $db->user->get($_SESSION['user_id']);
			//check to see if the user is an owner
			if ($me['role'] == 'tenant') {
				$_SESSION['feedback'] = ['message' => 'A tenant cannot view reactions!', 'state' => 'alert'];
				redirect('account');
			}

			$all_info = $db->room->find('all', ['contain' => 'Listing.Opt_in.User'])
			                     ->where(['owner_id' => $me['user_id']])->toList();

			echo $twig->render('reactions.twig', ['all_info' => $all_info]);
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
			require_login();  // you should log in to log out
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


			$current_user = get_info($db->user, 'user_id', @$_SESSION['user_id']);

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
	}
}

