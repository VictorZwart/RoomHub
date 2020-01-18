<?php namespace RoomHub;

use Bramus\Router\Router;
use Cake\ORM\Query;
use Twig\Environment;

class RoomController {

	/**
	 * RoomController constructor. Used to define all routes for room
	 *
	 * @param Router $router
	 * @param DB $db
	 * @param Environment $twig
	 */
	public function __construct($router, $db, $twig) {
		/* GET for getting an overview of all rooms */
		$router->get('/', function() use ($db, $twig) {

			// You can use the overview in three different ways:
			// = /rooms/: show all listings
			// = /rooms/?filter=mine: show all rooms that you own (if you're an owner)
			// = /rooms/?filter=username: show all rooms that 'username' owns


			$me = get_info($db->user, 'user_id', @$_SESSION['user_id']);

			if (@$_GET['filter']) {
				if ($me && $_GET['filter'] == 'mine' && $me['role'] == 'owner') {
					$user_id = $me['user_id'];
				} else {
					$owner = get_info($db->user, 'username', $_GET['filter']);
					if (!$owner) {
						// no such owner
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

			if ($me && $me['role'] == 'tenant') {
				$my_optins = $db->opt_in->find()->where([
					'user_id' => $me['user_id'],
					'status'  => 'open'
				]);
			} else {
				$my_optins = [];
			}


			echo $twig->render('rooms.twig', ['all_rooms' => $listings, 'my_optins' => $my_optins]);
		});

		/* GET for reading specific rooms */
		$router->get('/(\d+)', function($room_id) use ($db, $twig) {
			$room = get_info($db->room, 'room_id', $room_id, ['contain' => ['Listing', 'User']]);
			require_exists($room);
			$amount_of_rooms = $db->room->find()->where([
				'owner_id' => $room['owner_id']
			])->count();


			$is_opted          = false;
			$active_listing_id = 0;
			if (@$_SESSION['user_id']) {
				// user is logged in

				foreach ($room['listing'] as $index => $listing) {
					if ($listing['status'] == 'open') {
						$active_listing_id = $listing['listing_id'];

						$opt_in = $db->opt_in->find()->where([
							'user_id'    => $_SESSION['user_id'],
							'listing_id' => $active_listing_id,
							'status'     => 'open'
						]);

						if ($opt_in->count() > 0) {
							// the user has an opt-in for this listing (room)
							$is_opted = true;
						}

						break;
					}
				}

			}

			echo $twig->render('room.twig',
				[
					'room'            => $room,
					'opted'           => $is_opted,
					'active_listing'  => $active_listing_id,
					'owner'           => $room['user'],
					'amount_of_rooms' => $amount_of_rooms,
				]);


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
				'zipcode'     => fix_zip(@$_POST['zipcode']),
				'street_name' => @$_POST['street_name'],
				'number'      => @$_POST['number'],
				'owner_id'    => @$_SESSION['user_id']
			];


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
				'zipcode'     => fix_zip(@$_POST['zipcode']),
				'street_name' => @$_POST['street_name'],
				'number'      => @$_POST['number']
			];

			$active_room = get_info($db->room, 'room_id', $room_id);

			require_mine($active_room);
			$db->room->patchEntity($active_room, $room_data, ['validate' => 'update']);

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

		/* GET for removing your own room */
		$router->get('/remove/(\d+)', function($id) use ($db) {
			require_login();
			echo 'TODO' . $id;
		});

		$this->listing($router, $db, $twig);
		$this->opt_in($router, $db, $twig);

		/* DELETE for removing your room */
		$router->get('/delete/(\d+)', function($room_id) use ($db) {
			require_login();
			$active_room = get_info($db->room, 'room_id', $room_id);

			require_mine($active_room);
			$entity = get_info($db->room, 'room_id', $room_id);
			$db->room->delete($entity);
			$_SESSION['feedback'] = ['message' => 'Your room was deleted succesfully!', 'state' => 'succes'];
			redirect('/rooms');
		});
	}

	/**
	 * Define all opt-in routes
	 *
	 * @param Router $router
	 * @param DB $db
	 * @param Environment $twig
	 */
	private function opt_in($router, $db, $twig) {
		/* GET for getting the opt_in form */
		$router->get('/opt-in/(\d+)', function($listing_id) use ($db, $twig) {
			require_login();

			// todo: check no opt-in yet
			$existing_optin = $_SERVER['db']->opt_in->find()->where([
				'listing_id' => $listing_id,
				'user_id'    => $_SESSION['user_id'],
				'status'     => 'open'
			])->count();

			if ($existing_optin) {
				redirect('rooms', 'You are already opted-in for this listing!');
			}

			$userdata = get_info($db->user, 'user_id', @$_SESSION['user_id']);

			if ($userdata && $userdata['role'] !== 'tenant') {
				$_SESSION['feedback'] = ['message' => 'Only tenants can react on a room!'];
				redirect("rooms");
			}

			$roomdata = get_info($db->listing, 'listing_id', $listing_id, ['contain' => 'Room']);


			echo $twig->render('optinform.twig', ['room' => $roomdata, 'user' => $userdata]);
		});

		/* POST for adding opt in */
		$router->post('/opt-in/(\d+)', function($listing_id) use ($db) {
			require_login();

			$optin_data  = [
				'listing_id' => $listing_id,
				'user_id'    => @$_SESSION['user_id'],
				'message'    => @$_POST['message'],
				'date'       => date('Y-m-d h:i:s'),
				'status'     => 'open',
			];
			$new_message = $db->opt_in->newEntity($optin_data);
			$result      = safe_save($new_message, $db->opt_in);
			if ($result) {
				$opt_in_id = $result->opt_in_id;
				if ($db->opt_in->get($opt_in_id)) {
					$_SESSION['feedback'] = ['message' => 'Message successfully sent!', 'state' => 'success'];
				} else {
					$_SESSION['feedback'] = [
						'message' => 'Your message was not sent succesfully!',
						'state'   => 'alert'
					];
				}
				redirect("rooms");
			} else {
				redirect("rooms/opt-in/$listing_id");
			}
		});

		/* GET for cancelling opt in */
		$router->get('/opt-in/cancel/(\d+)', function($listing_id) use ($db) {
			// get because DELETE is not supported by browsers, and POST won't work with a button (easily that is)
			require_login();

			$opt_in = $db->opt_in->find()->where([
				'listing_id' => $listing_id,
				'user_id'    => $_SESSION['user_id'],
				'status'     => 'open',
			])->first();

			if (!$opt_in) {
				$_SESSION['feedback'] = ['message' => 'You are not opted-in to this listing.'];
				redirect('rooms');
			} else {
				$db->opt_in->patchEntity($opt_in, ['status' => 'cancelled'], ['validate' => 'cancel']);
				if (safe_save($opt_in, $db->opt_in)) {
					$_SESSION['feedback'] = ['message' => 'Opt-in successfully removed!', 'state' => 'success'];
					redirect('rooms');
				} else {
					redirect('rooms');
				}
			}
		});
	}

	/**
	 * Define all listing routes
	 *
	 * @param Router $router
	 * @param DB $db
	 * @param Environment $twig
	 */
	private function listing($router, $db, $twig) {
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


			$listing_info = get_info($db->listing, 'listing_id', $listing_id, ['contain' => 'Room']);

			require_exists($listing_info);
			require_mine($listing_info['room']);

			if ($listing_info['status'] != 'open') {
				$_SESSION['feedback'] = ['message' => 'This listing is not active.'];
				redirect('rooms');
			}

			echo $twig->render('listing_form.twig', ['listing' => $listing_info, 'is_edit' => true]);
		});

		/* POST for editing listing */
		$router->post('/list/edit/(\d+)', function($listing_id) use ($db) {
			require_login();

			$listing_info = get_info($db->listing, 'listing_id', $listing_id, ['contain' => 'Room']);

			require_exists($listing_info);
			require_mine($listing_info['room']);

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
				$_SESSION['feedback'] = ['message' => 'Listing successfully updated', 'state' => 'success'];
				redirect("rooms/$room_id");
			} else {
				redirect("rooms/list/edit/$listing_id");
			}


		});

		/* GET for canceling listing */
		$router->get('/list/cancel/(\d+)', function($listing_id) use ($db) {
			require_login();

			$listing = get_info($db->listing, 'listing_id', $listing_id, ['contain' => 'room']);

			require_exists($listing);
			require_mine($listing['room']);

			$db->listing->delete($listing);
			$_SESSION['feedback'] = ['message' => 'Your listing was deleted succesfully!', 'state' => 'succes'];
			redirect('/');

		});


		/* GET for assigning user to listing */
		$router->get('/list/assign/(\d+)', function($optin_id) use ($db) {
			require_login();
			$opt_in = get_info($db->opt_in, 'opt_in_id', $optin_id, ['contain' => 'Listing.Room']);

			$listing_info = $opt_in['listing'];
			require_exists($listing_info);
			require_mine($listing_info['room']);

			if ($listing_info['status'] != 'open' || $opt_in['status'] != 'open') {
				$_SESSION['feedback'] = ['message' => 'This listing or opt-in is not open!'];
				redirect("rooms/" . $listing_info['room']['room_id']);
			}

			$other_optins = $db->opt_in->find()->where([
				'listing_id' => $listing_info['listing_id'],
				'user_id !=' => $opt_in['user_id'],
				'status'     => 'open',
			]);


			$db->listing->patchEntity($listing_info, ['status' => 'closed'], ['validate' => 'close']);
			if (!safe_save($listing_info, $db->listing)) {
				$_SESSION['feedback'] = ['message' => 'Listing could not be closed.'];
				redirect('rooms/' . $listing_info['room']['room_id']);
			}


			$db->opt_in->patchEntity($opt_in, ['status' => 'accepted']);
			if (!safe_save($opt_in, $db->opt_in)) {
				$_SESSION['feedback'] = ['message' => 'Opt-in could not be accepted.'];
				redirect('rooms/' . $listing_info['room']['room_id']);
			}

			$errors = false;
			foreach ($other_optins as $other_optin) {
				$db->opt_in->patchEntity($other_optin, ['status' => 'rejected']);
				$res = safe_save($other_optin, $db->opt_in);
				if (!$res) {
					$errors = true;
				}
			}
			if ($errors) {
				$_SESSION['feedback'] = ['message' => 'Not all opt-ins could be rejected! Please try again'];
			} else {
				$_SESSION['feedback'] = ['message' => 'Listing successfully closed!', 'state' => 'success'];
			}

			redirect('rooms/' . $listing_info['room']['room_id']);
		});
	}

}

