<?php

use Cake\Validation\Validator;

function validate_user($post, $required_fields = null) {
	// check if user form is filled in correctly

	$validator = new Validator();

	if ($required_fields) {
		foreach ($required_fields as $field) {

			$validator->requirePresence($field)
			          ->notEmptyString($field, "This field is required");

		}
	}

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