<?php

require_once __DIR__ . '/../Helpers/Login.php';
require_once __DIR__ . '/../libs/gump.class.php';

class Salesforce
{

	protected $_connection;
    protected $_db;
    protected $sf;
    protected $attendee;
    protected $gump;

	public function __construct($pdo)
	{
		$this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();	
        $this->sf = new Login();
        $this->gump = new GUMP();
	}

	public function seminarSync()
    {
    	$jsonResponse = $this->sf->engageEndpoint('/services/data/v37.0/analytics/reports/00OU0000003HwP3');

    	$fieldClause = "";
		$valueClause = "";
		$updateClause = "";

		foreach($jsonResponse->{'reportExtendedMetadata'}->{'detailColumnInfo'} as $key => $rowHeader) {
			$rechars = array(' ', '-');
			$dbColumns[] = strtolower(str_replace($rechars, '_', $rowHeader->{'label'}));
			$fieldClause .= strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ','; 
			$valueClause .= ':' . strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ',';
			$updateClause .= strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . '=' . 
				':' . strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ',';
		}

		$fieldClause = rtrim($fieldClause, ',');
		$valueClause = rtrim($valueClause, ',');
		$updateClause = rtrim($updateClause, ',');

		$qry = $this->_db->prepare("INSERT INTO seminars (" . $fieldClause . ") VALUES (" . $valueClause . ") 
			ON DUPLICATE KEY UPDATE " . $updateClause);

		$reportRows = $jsonResponse->{'factMap'}->{'T!T'}->{'rows'};

		foreach($reportRows as $key => $dataCells) {

			foreach($dataCells as $dataCellKey => $dataCell) {

				foreach($dbColumns as $key => $rowHeader) {
					$data = $dataCell[$key]->{'label'};
					$param = ':'.$rowHeader;		

					if($rowHeader === 'start_date' || 
						$rowHeader === 'end_date' || 
						$rowHeader === 'created_date') {
							$data = date('Y-m-d', strtotime(str_replace('-', '/', $data)));
					}

					if($rowHeader === 'campaign_id' || $rowHeader === 'marketing_web') {
						$data = $dataCell[$key]->{'value'};	
					}

					$qry->bindValue($param, $data);
				}

				$qry->execute();
			}
		}

		$currentDate = date('Y-m-d');

		$this->sf->writeToLog('SUCCESS');

		return 'SUCCESS';
    }

    public function seminarRegistration($data)
    {
    	if(count($data) < 1) {
    		throw new Exception('No data entered');
    	}

    	$name = explode(' ', $data['name'], 2);

    	$this->attendee['FirstName'] = $name[0];

    	if(count($name) > 1) {
    		$this->attendee['LastName'] = $name[1];
    	}

    	$this->attendee['Company'] = 'MEDICARE';
    	$this->attendee['Birthdate_Contact__c'] = $data['birthday'];
    	$this->attendee['City'] = $data['city'];
    	$this->attendee['State'] = $data['state'];
    	$this->attendee['PostalCode'] = $data['zip'];
    	$this->attendee['Street'] = $data['address'];

    	$this->gumpValidation();
    	
    	if ($this->attendee === false) {
			return json_encode($this->gump->errors());
		} 

		$medicareId = $this->sf->engageEndpoint("/services/data/v37.0/query?q=select+id+from+recordtype+where+sobjecttype+='lead'+and+name+=+'Medicare'");

		$medicareId = $medicareId->records['0']->Id;

		$leadStatus = $this->sf->engageEndpoint("/services/data/v37.0/query?q=select+id+from+leadstatus+where+apiname+=+'Open+:+Campaign+Related'");

		$leadStatus = $leadStatus->records['0']->Id;

		$this->attendee['Status'] = $leadStatus;
		$this->attendee['RecordTypeId'] = $medicareId;

		//return $this->attendee;
		return $this->sf->engageEndpoint('/services/data/v37.0/sobjects/Lead', 'POST', $this->attendee);
    }

    public function passwordExpiration()
    {
    	//Do this
    	//http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-using-smtp-php.html
    }

    private function gumpValidation() 
    {

    	$this->attendee = $this->gump->sanitize($this->attendee);

        $this->gump->validation_rules(array(
        	'address' => 'required|alpha_numeric|max_len,100|min_len,3',
        	'city' => 'required|alpha|max_len,100|min_len,3',
        	'state' => 'required|exact_len,2',
        	'zip' => 'required|exact_len,6|numeric',
        	'birthday' => 'date',
        	'attendees' => 'numeric'
    	));

    	$this->gump->filter_rules(array(
		    'name' => 'trim|sanitize_string',
		    'address' => 'trim|sanitize_string',
		    'city' => 'trim|sanitize_string',
		    'state' => 'trim|sanitize_string',
		    'zip' => 'trim|sanitize_numbers',
		    'birthday' => 'trim|sanitize_string',
		    'attendees' => 'trim|sanitize_numbers'
		));

		$this->attendee = $this->gump->run($this->attendee);
    }
}

?>