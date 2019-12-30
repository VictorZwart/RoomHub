<?php

use Cake\ORM\Table;


/**
 * create an HTML form based on a table
 *
 * WIP:
 * anything with '_id' in it should be hidden for edit and not included for add
 * primary keys should be uneditable
 * types should me mapped to the right HTML input type
 * types should be checked (after submit)
 * save will be done (always POST) to the current endpoint
 *
 * @param Table $table any database table (from ORM)
 * @param array|null $fields fields you want to display/custom options for fields
 *
 */
function crud($table, $fields = null) {
	$schema = $table->getSchema();

	$all_fields = $schema->typeMap();

	foreach($all_fields as $field_name => $field_type){
		echo ucfirst(str_replace('_', ' ', $field_name));
		echo ': ';
		echo $field_type;
		echo '<br/>';
	}

	pprint($all_fields);
}
