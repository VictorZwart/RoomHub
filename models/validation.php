<?php namespace RoomHub;

use Cake\Database\Schema\TableSchema;
use Cake\Datasource\Exception\RecordNotFoundException;
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

function valid_dates($dateString, $allow_future = false) {
	// re-used validator
	if ($dateString < '1920-01-01') {
		return "Please fill in a date later than 1920";
	}
	if (!$allow_future && $dateString > date("Y-m-d")) {
		return "Please fill in a date that is not in the future";
	} elseif ($allow_future && $dateString > '2050-01-01') {
		return "Please fill in a date that is not that far in the future";
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
 * @param bool $new
 *
 * @return array of errors
 */
function validate_user($post, $table, $new = true) {
	$validator = new Validator();

	$skip = [];
	if (!$new) {
		$skip = ['username', 'password'];
	}

	_validate_required_fields($validator, $table->getSchema(), $skip);

	$validator
		->add('email', 'validFormat',
			[
				'rule'    => 'email',
				'message' => 'Please enter a valid email format.'
			])
		// add a validator for the phone number
		->add('phone_number', 'phone number check', [
			'rule'    => function($number) {
				return (preg_match('/^\d{2}-?\d{8}$/', $number) or preg_match('/^\d{4}-?\d{6}$/', $number));
			},
			'message' => 'You have not entered a good number'

		])
		//add a validator for birthdate
		->add('birthdate', 'legal ages', [
			'rule' => function($dateString) {

				return valid_dates($dateString);
			}
		]);

	if ($new) {
		$validator
			->add('username', 'unique username', [
				// username must not exist!
				'rule' => function($username) use ($table) {

					return unique($table, 'username', $username);

				}
			])
			->add('username', 'valid username', [
				// username can only have letters and numbers
				'rule'    => function($username) {
					return (bool) preg_match('/^\w+$/', $username);
				},
				'message' => 'Invalid username, please use only alphanumerical characters'
			])
			->add('email', 'unique email', [
				// email must not exist
				'rule' => function($email) use ($table) {
					return unique($table, 'email', $email);
				}
			])
			->add('password', 'matching password and validation', [
				// password must match validation
				'rule'    => function() use ($post) {
					return isset($post['password']) and
					       isset($post['password2']) and
					       $post['password'] == $post['password2'];
				},
				'message' => 'The password does not match'
			]);

	}

	return $validator->errors($post);
}

/**
 * Validate input for the room table
 *
 * @param array $post
 * @param null|Table $table (room)
 * @param bool $new
 *
 * @return array of errors
 */
function validate_room($post, $table, $new = false) {
	$validator = new Validator();

	if (!$new) {
		$skip = ['owner_id'];
	} else {
		$skip = [];
	}

	_validate_required_fields($validator, $table->getSchema(), $skip);

	$validator
		->add('zipcode', 'valid zipcode', [
			//zipcode must have format of 0000AA
			'rule'    => function($zipcode) {
				return (bool) preg_match('/^\d{4}[a-zA-Z]{2}$/', $zipcode);
			},
			'message' => 'You have entered a wrong zipcode format'
		])
		->add('number', 'valid housenumber', [
			//streetnumber must contain minimum of 1 number
			'rule'    => function($number) {
				return (bool) preg_match('/^\d\w*/', $number);
			},
			'message' => 'Please enter a number starting with a digit.'
		])
		->add('size', 'valid room area', [
			//streetnumber must contain minimum of 1 number
			'rule' => function($size) {
				if ($size > 1) {
					return true;
				} else {
					return 'Please enter a size which is larger than 1.';
				}
			}
		])
		->add('street_name', 'valid street name', [
			// street name can only be letters, apostrophe, dash and space
			'rule'    => function($street_name) {
				return (bool) preg_match("/^[a-zA-Z\-' ]+$/", $street_name);
			},
			'message' => 'Please enter a street name with only letters.'
		])
		->add('city', 'valid city name', [
			//streetnumber must contain minimum of 1 number
			'rule'    => function($city) {
				return (bool) preg_match('/^[a-zA-Z]+$/', $city);
			},
			'message' => 'Please enter a city name with only letters.'
		]);

	return $validator->errors($post);
}

/**
 * Validator for the 'listing' table
 *
 * @param array $post
 * @param Table $table
 *
 * @return array of errors
 */
function validate_listing($post, $table) {
	$validator = new Validator();
	_validate_required_fields($validator, $table->getSchema());

	$validator
		->add('room_id', 'existing room', [
			'rule'    => function($room_id) {
				try {
					$_SERVER['db']->room->get($room_id);

					return true;
				} catch (RecordNotFoundException $e) {
					return false;
				}
			},
			'message' => 'This room does not exist.'
		])
		->add('room_id', 'no active listings', [
			'rule'    => function($room_id) use ($table) {
				return $table->find()->where(['room_id' => $room_id])->count() == 0;
			},
			'message' => 'This room is already listed!'
		])
		->add('available_from', 'valid date', [
			'rule' => function($date) {
				return valid_dates($date, true);
			}
		])->add('available_to', 'valid date', [
			'rule' => function($date) {
				return valid_dates($date, true);
			}
		]);

	return $validator->errors($post);
}