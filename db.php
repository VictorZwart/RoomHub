<?php
/* connect here */

/* define db models here */

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;


/**
 * @property object $user database table for users
 */
class DB {
	private $conn;

	function __construct($config) {
		try {
			$dsn = "mysql://${config['user']}:${config['pass']}@${config['host']}/${config['db']}";
			ConnectionManager::setConfig('default', ['url' => $dsn]);

			$this->conn = ConnectionManager::get('default');
		} catch(Exception $e) {
			echo 'something went wrong connecting to the database.';
			die();
		}

	}

	function __get($name) {
		return TableRegistry::getTableLocator()->get($name);
	}
}