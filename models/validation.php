<?php

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Add validation to all required fields in the schema
 *
 * @param Validator $validator
 * @param TableSchema $schema
 */
function _validate_required_fields($validator, $schema) {
	$required_fields = [];

	foreach($schema->columns() as $column_name) {
		$column = $schema->getColumn($column_name);
		if (!$column['null'] && !@$column['autoIncrement']) {
			$required_fields[] = $column_name;
		}

	}


	if ($required_fields) {
		foreach($required_fields as $field) {
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
 * Validate input for the user table
 *
 * @param array $post
 * @param null|Table $table (user)
 *
 * @return array of errors
 *
 */
function validate_user($post, $table) {
	$validator = new Validator();

	_validate_required_fields($validator, $table->getSchema());

	$validator
		->add('email', 'validFormat',
			[
				'rule'    => 'email',
				'message' => 'Please enter a valid email format.'
			])
		// add a validator for the phone number
		->add('phone_number', 'phone number check', [
			'rule' => function() {
				$number = $_POST['phone_number'];
				if (preg_match('/^\d{2}-?\d{8}$/', $number) or preg_match('/^\d{4}-?\d{6}$/', $number)) {
					return true;
				} else {
					return 'You have not entered a good number';
				}
			}

		])
		//add a validator for birthdate
		->add('birthdate', 'custom', [
			'rule' => function() use ($post) {
				$dateString = $post['birthdate'];
				if ($dateString < '1920-01-01') {
					return "Please fill in a date later than 1920";
				}
				if ($dateString > date("Y-m-d")) {
					return "Please fill in a date that is not in the future";
				} else {
					return true;
				}
			}
		])
		->add('username', 'custom', [
			// username must not exist!
			'rule' => function() use ($post, $table) {

				return unique($table, 'username', $post['username']);

			}
		])

		->add('email', 'custom', [
			// email must not exist
			'rule' => function() use ($post, $table) {
				return unique($table, 'email', $post['email']);
			}
		])

		->add('password', 'custom', [
			// password must match validation
			'rule'    => function() use ($post) {
				return $post['password'] == $post['password2'];
			},
			'message' => 'The password does not match'
		]);

	return $validator->errors($post);
}

/**
 * Validate input for the room table
 *
 * @param array $post
 * @param null|Table $table (room)
 *
 * @return array of errors
 *
 */
function validate_room($post, $table) {
	$validator = new Validator();

	_validate_required_fields($validator, $table->getSchema());

	return $validator->errors($post);
}