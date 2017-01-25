<?php
	require_once __DIR__ . '/../../Helpers/gump.class.php';

	class SeminarRegistration {

		protected gump = new GUMP();

		public function __construct($data)
    	{
	        $this->postData = $gump->sanitize($data);

	        $validation = array(
	        	'name' => 'required|alpha|max_len,100|min_len,6',
	        	'address' => 'required|alpha_numeric|max_len,100|min_len,3',
	        	'city' => 'required|alpha|max_len,100|min_len,3',
	        	'state' => 'required|exact_len,2',
	        	'zip' => 'required|exact_len,6|numeric',
	        	'phoneNumber' => '',
	        	'email' => 'valid_email',
	        	'birthday' => 'date',
	        	'attendees' => 'numeric'
        	);

        	$filters = array(
			    'name' => 'trim|sanitize_string',
			    'address' => 'trim|sanitize_string',
			    'city' => 'trim|sanitize_string',
			    'state' => 'trim|sanitize_string',
			    'email'    => 'trim|sanitize_email'
			);

			$this->postData = $gump->filter($this->postData, $filters);

			$this->$validated = $gump->validate($this->postData, $validation);
    	}

    	public function validated()
    	{
    		if ($this->validated) {
    			return $this->validated;
    		} else {
    			return $gump->get_readable_errors(true);
    		}
    	}

    	public function addLead() 
    	{

    	}
	}
?>