<?php

require_once __DIR__ . '/../Helpers/Login.php';
require_once __DIR__ . '/../libs/gump.class.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailerAutoload.php';

class Salesforce
{

	protected $_connection;
    protected $_db;
    protected $sf;
    protected $attendee;
    protected $gump;
    private $config; 

	public function __construct($pdo)
	{
		$this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();	
        $this->sf = new Login();
        $this->gump = new GUMP();
        $this->config = include(__DIR__ . '/../Helpers/config.php');
	}

	public function seminarSync()
    {
    	$jsonResponse = $this->sf->engageEndpoint($this->config['seminar.report']);

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

    public function passwordExpiration()
    {
    	//Do this
    	//http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-using-smtp-php.html
    	$jsonResponse = $this->sf->engageEndpoint($this->config['password.notification']);

    	$user = array();
    	$users = array();
    	$reportRows = $jsonResponse->{'factMap'}->{'T!T'}->{'rows'};

    	foreach($reportRows as $key => $dataCells) {
    		foreach($dataCells as $dataCellKey => $dataCell) {
    			$index = 0;
		    	foreach($jsonResponse->{'reportExtendedMetadata'}->{'detailColumnInfo'} as $key => $rowHeader) {
		    		$user[$rowHeader->{'label'}] = $dataCell[$index]->{'label'};
		    		$index++;
		    	}
		    	$users[] = $user;
	    	}
    	}

    	foreach($users as $key => $user) {
    		$now = strtotime(date('Y-m-d'));
    		$expire = strtotime(date("Y-m-d", strtotime($user['Password Expiration Date'])));

    		$days = floor(($expire - $now) / (60 * 60 * 24));

    		return $this->notify($days, $user);

    		if($days === 7 || $days === 2) {
    			$this->notify($days, $user);
    		}
    	}

    	return;
    }

    public function seminarRegistration($data)
    {
    	if(count($data) < 1) {
    		throw new Exception('No data entered');
    	}

    	$name = explode(' ', $data['name'], 2);

    	unset($data['name']);

    	$this->attendee = $data;
    	$this->attendee['FirstName'] = $name[0];

    	if(count($name) > 1) {
    		$this->attendee['LastName'] = $name[1];
    	}

    	$this->attendee['Company'] = 'MEDICARE';

    	try {
		  	$date = new DateTime($this->attendee['DOB__c']);
    		$this->attendee['DOB__c'] = $date->format('Y-m-d');
		}
		catch(Exception $e) {
		  	return  Array(Array(
		  		'field' => "DOB__c",
                'value' => $this->attendee['DOB__c'],
                'rule' => "validate_date",
                'param' => null
            ));
		}

    	$this->gumpValidation();

    	if ($this->attendee === false) {
			return $this->gump->errors();
		} 

		$campaignId = $this->attendee['CampaignId'];
		unset($this->attendee['CampaignId']);

		$leadId = $this->retrieveLeadId();	

		$leadMember = Array(
			"Response_Type__c" => "Online",
			"Response__c" => "Schedule seminar",
			"Status" => "Responded",
			"LeadId" => $leadId->id,
			"CampaignId" => $campaignId
		);

		$this->sf->engageEndpoint($this->config['campaign.member.url'], 'POST', json_encode($leadMember));

		return;	
    }

    private function notify($days, $user)
    {
    	$mail = new PHPMailer;
    	$mail->isSMTP(); 
    	$mail->SMTPAuth = true; 
    	$mail->SMTPSecure = "tls"; 
    	$mail->Port = 587; 
    	$mail->Host = "email-smtp.us-west-2.amazonaws.com";
		$mail->Username = $this->config['ses.user'];
		$mail->Password = $this->config['ses.password'];

		$mail->setFrom($this->config['ses.sender'], 'Sender Name'); //from (verified email address)
		$mail->Subject = 'Salesforce: '.$days . ' days left.'; //subject
		$mail->isHTML(true);   

		$body = 'Dear ' . $user['First Name'] . ' ' .  $user['Last Name'] . ', <br><br> Your Salesforce password will expire in ' . $days .' days. Please take action <a href="https://login.salesforce.com/">here.</a>';

		$mail->addAddress($user['Email'], $user['First Name'] . ' ' . $user['Last Name']); 
    	
		$mail->Body = $body;

		if(!$mail->send()) {
		    $this->writeToLog('Mailer Error: ' . $mail->ErrorInfo);
		} else {
		    $this->writeToLog('Message has been sent');
		}

		return;
    }

    private function retrieveLeadId() 
    {
    	$medicareId = $this->sf->engageEndpoint($this->config['record.type']);

		$medicareId = $medicareId->records['0']->Id;

		$this->attendee['Status'] = 'Open : Campaign Related';
		$this->attendee['RecordTypeId'] = $medicareId;

		return $this->sf->engageEndpoint($this->config['lead.url'], 'POST', json_encode($this->attendee));
    }

    private function gumpValidation() 
    {
    	$this->attendee = $this->gump->sanitize($this->attendee);

        $this->gump->validation_rules(array(
        	'FirstName' => 'required|alpha|max_len,100|min_len,3',
        	'LastName' => 'required|alpha|max_len,100|min_len,3',
        	'Street' => 'required|alpha_space|max_len,100|min_len,3',
        	'City' => 'required|alpha|max_len,100|min_len,3',
        	'State' => 'required|exact_len,2',
        	'PostalCode' => 'required|exact_len,5|numeric',
        	'DOB__c' => 'date',
        	'Attendees' => 'numeric',
        	'CampaignId' => 'required|alpha_numeric|max_len,100|min_len,3'
    	));

    	$this->gump->filter_rules(array(
		    'FirstName' => 'trim|sanitize_string',
		    'LastName' => 'trim|sanitize_string',
		    'Street' => 'trim|sanitize_string',
		    'City' => 'trim|sanitize_string',
		    'State' => 'trim|sanitize_string',
		    'PostalCode' => 'trim|sanitize_numbers',
		    'DOB__c' => 'trim|sanitize_string',
		    'Attendees' => 'trim|sanitize_numbers',
		    'CampaignId' => 'trim|sanitize_string'
		));

		$this->attendee = $this->gump->run($this->attendee);
    }

    public function writeToLog($message) 
    {
		$message = date('m/d/Y h:i:sa', time()) . ' ' . $message;
		file_put_contents($this->config['log'], $message . "\r\n", FILE_APPEND | LOCK_EX);
	}
}

?>