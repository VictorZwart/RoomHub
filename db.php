<?php
/* connect here */

/* define db models here */

use Cake\Datasource\ConnectionManager;

function connect_db($config) {

	$dsn = "mysql://${config['user']}:${config['pass']}@${config['host']}/${config['db']}";
	ConnectionManager::setConfig('default', ['url' => $dsn]);

	return ConnectionManager::get('default');
}