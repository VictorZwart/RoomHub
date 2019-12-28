<?php

// script to manually migrate. Only execute in cli

require __DIR__ . '/vendor/autoload.php';

foreach(glob("models/*.php") as $filename) {
        include $filename;
}

use Cake\ORM\{Table, TableRegistry};

$conf = new Config();

if (@$conf->get('db')['migrate'] == 'manual') {
	// only allow manual migrations when this mode is selected!

	$db = new DB($conf);

	$db->auto_migrate();
	echo 'done!';
}
else {
	echo 'manual migrations are turned off!';
}
