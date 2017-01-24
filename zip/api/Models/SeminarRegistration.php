<?php
	require_once __DIR__ . '/../../Helpers/gump.class.php';

	class SeminarRegistration {

		protected gump = new GUMP();

		public function __construct($data)
    	{
	        $this->postData = $gump->sanitize($data);

	        $gump->validation_rules(array(
	        	'name' => 'required|alpha|max_len,100|min_len,6',
	        	'address' => 'required|alpha_numeric|max_len,100|min_len,3',
	        	'city' => 'required|alpha|max_len,100|min_len,3',
	        	'state' => 'required|exact_len,2',
	        	'zip' => 'required|exact_len,6|numeric',
	        	'phoneNumber' => '',
	        	'email' => 'valid_email',
	        	'birthday' => '',
	        	'attendees' => 'numeric'
        	));

        	$gump->filter_rules(array(
			    'name' => 'trim|sanitize_string',
			    'address' => 'trim|sanitize_string',
			    'city' => 'trim|sanitize_string',
			    'state' => 'trim|sanitize_string',
			    'email'    => 'trim|sanitize_email',
			    'gender'   => 'trim',
			    'bio'      => 'noise_words'
			));
    	}
	}
?>