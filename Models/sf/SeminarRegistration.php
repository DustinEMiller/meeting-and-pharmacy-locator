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
    			return json_encode($gump->get_readable_errors());
    		}
    	}

    	public function addLead() 
    	{
    		$this->login();	
    		/*
    		* Query endpoint for lead type ID
    		* /services/data/v37.0/query?q=select+id,+name+from+recordtype+where+sobjecttype+='lead'+and+name+=+'Medicare'
    		*/
    	}

    	private function login() 
    	{
    		//This needs to go into a class that then utilizes a config class. See also SeminarSync
    		$curl = curl_init('https://login.salesforce.com/services/oauth2/token');
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
				'grant_type' => 'password', 
				'client_id' => get_cfg_var('salesforce.clientid'), 
				'client_secret' => get_cfg_var('salesforce.clientsecret'), 
				'username' => get_cfg_var('salesforce.username'), 
				'password' => get_cfg_var('salesforce.pwd') . get_cfg_var('salesforce.securitytoken')
			)));
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/x-www-form-urlencoded'));
			$jsonResponse = json_decode(curl_exec($curl), true);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE); 

			if ( $status !== 200 ) {
				// Process curl errors and return			
				return 'errors';
	    	}

			curl_close($curl);
			
			$_SESSION['access_token'] = $jsonResponse['access_token'];
			$_SESSION['instance_url'] = $jsonResponse['instance_url'];

			$token = $jsonResponse['access_token'];

			if (!$_SESSION['access_token'] || $_SESSION['access_token'] === "") {
	            return 'Error - access token missing from session!';
	        }

	        if (!$_SESSION['instance_url'] || $_SESSION['instance_url'] === "") {
	            return 'Error - instance URL missing from session!';
	        }
    	}

	}
?>