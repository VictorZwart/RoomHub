<?php


require __DIR__ . '/vendor/autoload.php';

include 'models.php';


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