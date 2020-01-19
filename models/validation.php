<?php namespace RoomHub;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Add validation to all required fields in the schema
 *
 * @param Validator $validator
 * @param TableSchema $schema
 * @param array $skip not-required fields (for edit)
 */
function _validate_required_fields($validator, $schema, $skip = []) {
	if ($skip == null) {
		// skip has to be an array
		$skip = [];
	}
	$required_fields = [];

	foreach ($schema->columns() as $column_name) {
		$column = $schema->getColumn($column_name);
		if (!$column['null'] && !@$column['autoIncrement'] && !in_array($column_name, $skip)) {
			$required_fields[] = $column_name;
		}

	}


	if ($required_fields) {
		foreach ($required_fields as $field) {
			$validator->requirePresence($field)
			          ->notEmptyString($field, "This field is required");
		}
	}
}

/**
 * @param Table $table what table to look in
 * @param string $field what field to look at
 * @param string $value what value must be unique
 *
 * @return bool|string whether it is valid or not
 */
function unique($table, $field, $value) {
	if ($table->find('all')->where([
		$field => $value
	])->count()) {
		return "This $field is taken.";
	} else {
		return true;
	}
}

/**
 * Check if a date is in a legal range
 *
 * @param string $date datetime object or string
 * @param bool $allow_future whether dates after today are okay
 *
 * @return bool|string if the date is legal or not (true if legal, feedback string if not)
 */
function valid_dates($date, $allow_future = false) {
	// re-used validator
	if ($date < '1920-01-01') {
		return "Please fill in a date later than 1920";
	}
	if (!$allow_future && $date > date("Y-m-d")) {
		return "Please fill in a date that is not in the future";
	} elseif ($allow_future && $date > '2050-01-01') {
		return "Please fill in a date that is not that far in the future";
	} else {
		return true;
	}
}
