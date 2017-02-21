<?php
	require_once __DIR__ . '/../../Helpers/gump.class.php';

	class SeminarRegistration {

		protected gump = new GUMP();

		public function __construct($data)
    	{
	        
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
    		`/services/data/v37.0/query?q=select+id,+name+from+recordtype+where+sobjecttype+='lead'+and+name+=+'Medicare' `

			`{
			    "FirstName": "Steve",
			    "LastName": "Nulwicki",
			    "Company": "MEDICARE",
			    "RecordTypeId": "012U0000000UGjoIAG",
			    "Birthdate_Contact__c": "1981-5-4",
			    "Marital_Status__c": "U - Unknown",
			    "Phone": "555-555-5555",
			    "Email": "myaddress@email.com",
			    "City": "Tuskiohla"
			    "State": "IL",
			    "PostalCode": "61802",
			    "Street": "1900 19th Street"
			}`
    		*/
    	}

	}
?>