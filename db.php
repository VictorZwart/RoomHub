<?php
/* connect here */

/* define db models here */

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;


/**
 * @property object $user database table for users
 * @property object $migration database table for migrations (internal use)
 */
class DB {
	private $conn;

	/**
	 * @param Config $cnf config object
	 */
	function __construct($cnf) {
		$config = $cnf->get('db', []);
		try {
			$dsn = "mysql://${config['user']}:${config['pass']}@${config['host']}/${config['db']}";
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


	function __get($name) {
		return TableRegistry::getTableLocator()->get($name);
	}

	function _migrate($filename) {
		$migrations = $this->migration;

		$this->conn->execute(file_get_contents("migrations/$filename"));
		$migrations->save($migrations->newEntity(['migration_file' => $filename]));
	}

	function auto_migrate($last_migration_file = null) {
		$migrations = $this->migration;


		try {
			# last migration:
			# SELECT migration_id FROM migration ORDER BY migration_id DESC LIMIT 1
			$last_migration_file = $last_migration_file ??
			                       $migrations->find()->order(['migration_id' => 'DESC'])->first()->migration_file;
		} catch(Exception $e) {


			try {
				// commit initial migration
				$this->_migrate('0.sql');


				# last migration: 0 (check in db anyway, to check if db is working)

				$last_migration_file = $migrations->find()->order(['migration_id' => 'DESC'])->first()->migration_file;
			} catch(Exception $e) {
				http_response_code(500);
				echo 'the database could not be set-up. Please check your initial migration';
				die();
			}

		}


		// check if migration file > last_migration and migrate it

		$migration_files = scandir('migrations');
		$migration_index = array_search($last_migration_file, $migration_files);
		if ($migration_index == null) {
			// file not found, just stop.
			return;
		}
		$new_file = @$migration_files[$migration_index + 1];
		if ($new_file) {
			$this->_migrate($new_file);

			$this->auto_migrate($new_file);
		} // else: no files left

	}
}