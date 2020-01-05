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
	$validator->add('email', 'validFormat',
        ['rule' => 'email',
        'message' => 'PLease enter a valid email format.'
    ]);

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