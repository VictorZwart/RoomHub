<?php namespace RoomHub;
/* connect here */

/* define db models here */

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\ORM\{Entity, Table, TableRegistry};
use Exception;
use PDOException;


// models

// table for listing that allows linking a room
class ListingTable extends Table {
	public function initialize(array $config) {
		// 1 room can have many listings
		$this->belongsTo('Room');
	}
}

// table for room that allows linking listing
class RoomTable extends Table {
	public function initialize(array $config) {
		// 1 room can have many listings
		$this->hasMany('Listing')
			// ->setConditions(['status' => 'active'])
			 ->setForeignKey('room_id');
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
		} catch(Exception $e) {
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
		} catch(Exception $e) {
			try {
				// commit initial migration
				$this->_migrate('0.sql');

				// check if migration succeeded
				$last_migration_file = $migrations->find()->order(['migration_id' => 'DESC'])->first()->migration_file;
			} catch(Exception $e) {
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
	} catch(PDOException $e) {
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

		$errors = validate_listing($listing_data, $table);
		if ($errors) {
			$_SESSION['feedback'] = ['message' => 'The room could not be listed.', 'errors' => $errors];
			return false;
		}
		$new_listing = $table->newEntity($listing_data);

		return safe_save($new_listing, $table);

	}

	return false;
}