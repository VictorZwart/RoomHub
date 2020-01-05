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