<?php namespace RoomHub;
/* connect here */

/* define db models here */

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Validation\Validator;
use Cake\ORM\{Entity, Table, TableRegistry};
use Exception;
use PDOException;


// models

// table for listing that allows linking a room
class ListingTable extends Table {
	public function initialize(array $config) {
		// 1 room can have many listings
		$this->belongsTo('Room', ['className' => 'Roomhub\RoomTable']);
		// 1 listing can have many opt_ins
		$this->hasMany('Opt_in', ['className' => 'Roomhub\Opt_inTable'])
		     ->setForeignKey('listing_id');
		// probeer zoiets: 'className' => 'Publishing.Authors' ; https://book.cakephp.org/3.next/en/orm/associations.html
	}

	// table for opt-ins and listings that allows linking an opt-in with a room


	/**
	 * Wrapper for default validate for listings
	 *
	 * @param Validator $validator
	 * @param null|array $skip
	 *
	 * @return Validator
	 */
	private function _validate($validator, $skip = null) {

		_validate_required_fields($validator, $this->getSchema(), $skip);

		$validator
			->add('room_id', 'existing room that you own', [
				'rule'    => function($room_id) {
					$room_info = get_info($_SERVER['db']->room, 'room_id', $room_id);

					return $room_info && $room_info['owner_id'] == $_SESSION['user_id'];
				},
				'message' => 'This room does not exist.'
			])
			->add('room_id', 'no active listings', [
				'rule'    => function($room_id) {
					return $this->find()->where(['room_id' => $room_id])->count() == 0;
				},
				'message' => 'This room is already listed!'
			])
			->add('available_from', 'valid date', [
				'rule' => function($date) {
					return valid_dates($date, true);
				}
			])->add('available_to', 'valid date', [
				'rule' => function($date) {
					return valid_dates($date, true);
				}
			]);

		return $validator;
	}

	/**
	 * Validator to use when adding entry
	 *
	 * @param Validator $validator
	 *
	 * @return Validator
	 */
	public function validationDefault($validator) {
		return $this->_validate($validator);
	}

	/**
	 * Validator to use when updating entry
	 * Use as $db->listing->patchEntity($listing, $listing_data, ['validate' => 'update']);
	 *
	 * @param Validator $validator
	 *
	 * @return Validator
	 */
	public function validationUpdate($validator) {
		return $this->_validate($validator, ['status', 'room_id']);
	}

	/**
	 * Validator to use when closing entry
	 * Use as $db->listing->patchEntity($listing, $listing_data, ['validate' => 'close']);
	 *
	 * @param Validator $validator
	 *
	 * @return Validator
	 */
	public function validationClose($validator) {
		return $this->_validate($validator, ['room_id', 'available_from']);
	}

}

// table for opt-ins that allows linking 'listing's
class Opt_inTable extends Table {
	public function initialize(array $config) {
		// Many opt-ins belong to a listing
		$this->belongsTo('Listing', ['className' => 'Roomhub\ListingTable'])
		     ->setForeignKey('listing_id');
		// Many opt-ins belong to a user
		$this->belongsTo('User', ['className' => 'Roomhub\UserTable'])
		     ->setForeignKey('user_id');
	}
}

class UserTable extends Table {
	public function initialize(array $config) {
		// 1 user can have many opt_ins
		$this->hasMany('Opt_in', ['className' => 'Roomhub\Opt_inTable'])
		     ->setForeignKey('user_id');
		// 1 user can have many rooms
		$this->hasMany('Room', ['classname' => 'Roomhub\RoomTable'])
		     ->setForeignKey('user_id');
	}

	/**
	 * Wrapper for default validate for users
	 *
	 * @param Validator $validator
	 * @param null|array $skip
	 *
	 * @return Validator
	 */
	public function _validate($validator, $skip = null) {
		_validate_required_fields($validator, $this->getSchema(), $skip);

		$validator
			->add('email', 'validFormat',
				[
					'rule'    => 'email',
					'message' => 'Please enter a valid email format.'
				])
			// add a validator for the phone number
			->add('phone_number', 'phone number check', [
				'rule'    => function($number) {
					return (preg_match('/^\d{2}-?\d{8}$/', $number) or
					        preg_match('/^\d{4}-?\d{6}$/', $number));
				},
				'message' => 'You have not entered a good number'

			])
			//add a validator for birthdate
			->add('birthdate', 'legal ages', [
				'rule' => function($dateString) {

					return valid_dates($dateString);
				}
			]);

		return $validator;
	}

	/**
	 * Validator to use when adding entry
	 *
	 * @param Validator $validator
	 *
	 * @return Validator
	 */
	public function validationDefault($validator) {
		$validator
			->add('username', 'unique username', [
				// username must not exist!
				'rule' => function($username) {

					return unique($this, 'username', $username);

				}
			])
			->add('username', 'valid username', [
				// username can only have letters and numbers
				'rule'    => function($username) {
					return (bool) preg_match('/^\w+$/', $username);
				},
				'message' => 'Invalid username, please use only alphanumerical characters'
			])
			->add('email', 'unique email', [
				// email must not exist
				'rule' => function($email) {
					return unique($this, 'email', $email);
				}
			])
			->add('password', 'matching password and validation', [
				// password must match validation
				'rule'    => function() {
					return isset($_POST['password']) and
					       isset($_POST['password2']) and
					       $_POST['password'] == $_POST['password2'];
				},
				'message' => 'The password does not match'
			]);

		return $this->_validate($validator);
	}

	/**
	 * Validator to use when updating entry
	 * Use as $db->room->patchEntity($room, $room_data, ['validate' => 'update']);
	 *
	 * @param Validator $validator
	 *
	 * @return Validator
	 */
	public function validationUpdate($validator) {
		return $this->_validate($validator, ['username', 'password']);
	}

}

// table for room that allows linking 'listing's
class RoomTable extends Table {
	public function initialize(array $config) {
		// 1 room can have many listings
		$this->hasMany('Listing', ['className' => 'RoomHub\ListingTable'])
			// ->setConditions(['status' => 'open'])
			 ->setForeignKey('room_id');
		// 1 owner can have many rooms
		$this->belongsTo('User', ['className' => 'Roomhub\UserTable'])
		     ->setForeignKey('owner_id');
	}

	/**
	 * Wrapper for default validate for rooms
	 *
	 * @param Validator $validator
	 * @param null|array $skip
	 *
	 * @return Validator
	 */
	function _validate($validator, $skip = null) {

		_validate_required_fields($validator, $this->getSchema(), $skip);

		$validator
			->add('zipcode', 'valid zipcode', [
				//zipcode must have format of 0000AA
				'rule'    => function($zipcode) {
					return (bool) preg_match('/^\d{4} ?[a-zA-Z]{2}$/', $zipcode);
				},
				'message' => 'You have entered a wrong zipcode format'
			])
			->add('number', 'valid housenumber', [
				//streetnumber must contain minimum of 1 number
				'rule'    => function($number) {
					return (bool) preg_match('/^\d\w*/', $number);
				},
				'message' => 'Please enter a number starting with a digit.'
			])
			->add('size', 'valid room area', [
				//streetnumber must contain minimum of 1 number
				'rule' => function($size) {
					if ($size > 1) {
						return true;
					} else {
						return 'Please enter a size which is larger than 1.';
					}
				}
			])
			->add('street_name', 'valid street name', [
				// street name can only be letters, apostrophe, dash and space
				'rule'    => function($street_name) {
					return (bool) preg_match("/^[a-zA-Z\-' ]+$/", $street_name);
				},
				'message' => 'Please enter a street name with only letters.'
			])
			->add('city', 'valid city name', [
				//streetnumber must contain minimum of 1 number
				'rule'    => function($city) {
					return (bool) preg_match('/^[a-zA-Z]+$/', $city);
				},
				'message' => 'Please enter a city name with only letters.'
			]);

		return $validator;
	}


	/**
	 * Validator to use when adding entry
	 *
	 * @param Validator $validator
	 *
	 * @return Validator
	 */
	public function validationDefault($validator) {
		return $this->_validate($validator);
	}

	/**
	 * Validator to use when updating entry
	 * Use as $db->listing->patchEntity($listing, $listing_data, ['validate' => 'update']);
	 *
	 * @param Validator $validator
	 *
	 * @return Validator
	 */
	public function validationUpdate($validator) {
		$validator = $this->_validate($validator, ['owner_id']);

		// room should be yours when updating
		$validator->add('owner_id', 'right owner', [
			'rule'    => function($owner_id) {
				return $owner_id == $_SESSION['user_id'];
			},
			'message' => 'You dont\'t own this room'
		]);

		return $validator;
	}


}

/**
 * DB is a wrapper for database related functions
 *
 * @property object $conn database connection, can be used for queries and such
 *
 * automatic properties based on the schema:
 * @property Table $user database table for users
 * @property RoomTable $room database table for rooms
 * @property Table $migration database table for migrations (internal use)
 * @property ListingTable $listing database table for listings
 * @property Table $opt_in database table for opt_ins
 */
class DB {
	public $conn;

	/**
	 * @param Config $cnf config object
	 */
	function __construct($cnf) {
		$config = $cnf->get('db', []);
		$schema = @$config['schema'] ?? 'mysql';

		// connect models

		try {
			$dsn = "$schema://${config['user']}:${config['pass']}@${config['host']}/${config['db']}";
			ConnectionManager::setConfig('default', ['url' => $dsn]);

			$this->conn = ConnectionManager::get('default');
		} catch (Exception $e) {
			echo 'something went wrong connecting to the database.';
			die();
		}

		if ($config['migrate'] == 'auto') {
			$this->auto_migrate();
		}
	}

	/**
	 * Magic function (syntactic sugar) to access db tables:
	 * $db->name to access a specific table
	 *
	 * @param string $name of the table
	 *
	 * @return Table
	 */
	function __get($name) {
		$ucname = 'Roomhub\\' . ucfirst($name) . 'Table';


		if (class_exists($ucname)) {
			// custom table class exists, use that
			return TableRegistry::getTableLocator()->get(ucfirst($name), ['className' => $ucname]);
		}

		// use default generated (cake) table class
		return TableRegistry::getTableLocator()->get($name);
	}

	/**
	 * Perform database migration, based on an sql file
	 *
	 * @param string $filename sql file to migrate
	 */
	function _migrate($filename) {
		$migrations = $this->migration;

		$this->conn->execute(file_get_contents("migrations/$filename"));
		$migrations->save($migrations->newEntity(['migration_file' => $filename]));
	}

	/**
	 * Migrate all files in /migration that have not been done yet
	 *
	 * @param null|string $last_migration_file previous migration
	 */
	function auto_migrate($last_migration_file = null) {
		$migrations = $this->migration;


		try {
			# get last migration (if not given):
			$last_migration_file = $last_migration_file ??
			                       $migrations->find()->order(['migration_id' => 'DESC'])->first()->migration_file;
		} catch (Exception $e) {
			try {
				// commit initial migration
				$this->_migrate('0.sql');

				// check if migration succeeded
				$last_migration_file = $migrations->find()->order(['migration_id' => 'DESC'])->first()->migration_file;
			} catch (Exception $e) {
				// initial migration failed, give up now
				http_response_code(500);
				echo 'the database could not be set-up. Please check your initial migration';
				die();
			}

		}


		// check if there is a migration file > last_migration and migrate it

		$migration_files = scandir('migrations');
		$migration_index = array_search($last_migration_file, $migration_files);
		if ($migration_index == null) {
			// file not found, just stop.
			return;
		}
		$new_file = @$migration_files[$migration_index + 1];
		if ($new_file) {
			$this->_migrate($new_file);

			// continue checking the rest
			$this->auto_migrate($new_file);
		} // else: no files left

	}
}


/**
 * check if the model can be saved and do so.
 *
 * @param EntityInterface $object
 * @param Table $table
 *
 * @return bool|Entity
 */
function safe_save($object, $table) {
	if ($object->getErrors()) {
		// Entity failed validation.

		$_SESSION['feedback'] = [
			'message' => 'Some fields were not filled in correctly!',
			'errors'  => $object->getErrors()
		];


		return false;
	}
	// no errors (according to cake)

	try {
		return $table->save($object);
	} catch (PDOException $e) {
		$_SESSION['feedback'] = [
			'message' => 'Something went wrong.',
			'errors'  => $e->getMessage(),
		];

		return false;
	}

}

/**
 * @param int $room_id
 * @param array $post
 * @param Table $table
 *
 * @return bool|Entity
 */
function handle_add_listing($room_id, $post, $table) {

	if (@$post['disable_listing'] == 'on') {
		// don't do anything with listing
	} else {
		// check listing
		$listing_data = [
			'status'         => 'open',
			'room_id'        => $room_id,
			'available_from' => @$post['available_from']
		];
		if (!@$post['is_indefinite'] == 'on') {
			// do something with available_to
			$listing_data['available_to'] = @$post['available_to'];
		}

		$new_listing = $table->newEntity($listing_data);

		return safe_save($new_listing, $table);

	}

	return false;
}