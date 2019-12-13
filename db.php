<?php
/* connect here */

/**
 * Connects to the database using PDO
 *
 * @param string $host database host
 * @param string $db database name
 * @param string $user database user
 * @param string $pass database password
 *
 * @return pdo object
 */
function connect_db($host, $db, $user, $pass) {
	$charset = 'utf8mb4';

	$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
	$options = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	];
	try {
		$pdo = new PDO($dsn, $user, $pass, $options);
	} catch(\PDOException $e) {
		echo sprintf("Failed to connect. %s", $e->getMessage());
	}

	return $pdo;
}

/* define db models here */
