<?php

use Cake\Database\Schema\TableSchema;
use Cake\Validation\Validator;

/**
 * @param array $post
 * @param null|TableSchema $schema
 *
 * @return array
 *
 */
function validate_user($post, $schema = null) {
	$required_fields = [];
	$validator       = new Validator();

	// if schema is supplied, auto-check required (NOT NULL) fields

	if ($schema) {

		foreach ($schema->columns() as $column_name) {
			$column = $schema->getColumn($column_name);
			if (!$column['null'] && !@$column['autoIncrement']) {
				$required_fields[] = $column_name;
			}

		}
	}

	if ($required_fields) {
		foreach ($required_fields as $field) {
			$validator->requirePresence($field)
			          ->notEmptyString($field, "This field is required");
		}
	}
	$validator->add('email', 'validFormat',
        ['rule' => 'email',
        'message' => 'PLease enter a valid email format.'
    ]);

    // add a validator for the phone number
    $validator->add('phone_number', 'phone number check', [
        'rule' => function() {
            $number = $_POST['phone_number'];
            if (preg_match('/^\d{2}-?\d{8}$/', $number) or preg_match('/^\d{4}-?\d{6}$/', $number)){
                return true;
            }
            else {
                return 'You have not entered a good number';
                }
        }

    ]);
    //add a validator for birthdate
	$validator->add('birthdate', 'custom', [
	    'rule' => function() {
            $dateString = $_POST['birthdate'];
            if ($dateString < '1920-01-01') {
                return "Please fill in a date later than 1920";
            }
            if ($dateString > date("Y-m-d")) {
                return "PLease fill in a date that is not in the future";
            }
            else{
                return true;
            }
        },
        'message' => 'Generic error message used when `false` is returned'
    ]);


	// check for 'unique' fields
	// TODO

	// Other validations

//	$validator
//		->requirePresence('email')
//		->add('email', 'validFormat', [
//			'rule'    => 'email',
//			'message' => 'E-mail must be valid'
//		])
//		->requirePresence('name')
//		->notEmptyString('name', 'We need your name.')
//		->requirePresence('comment')
//		->notEmptyString('comment', 'You need to give a comment.');

	return $validator->errors($post);
}