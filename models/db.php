<?php
/* connect here */

/* define db models here */

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\ORM\{Entity, Table, TableRegistry};


/**
 * DB is a wrapper for database related functions
 *
 * @property object $conn database connection, can be used for queries and such
 *
 * automatic properties based on the schema:
 * @property Table $user database table for users
 * @property Table $room database table for rooms
 * @property Table $migration database table for migrations (internal use)
 */
class DB {
	public $conn;

	/**
	 * @param Config $cnf config object
	 */
	function __construct($cnf) {
		$config = $cnf->get('db', []);
		$schema = @$config['schema'] ?? 'mysql';

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

		$_SESSION['feedback'] = ['message' => 'Some fields were not filled in correctly!', 'errors' => $object->getErrors()];


		return false;
	}
	// no errors (according to cake)

	try {
		return $table->save($object);
	} catch(PDOException $e) {
		$_SESSION['feedback'] = [
			'message' => 'Something went wrong.'
		];

		return false;
	}

}