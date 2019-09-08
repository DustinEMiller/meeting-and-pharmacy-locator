<?php

require_once __DIR__ . '/../Helpers/Login.php';
require_once __DIR__ . '/../libs/gump.class.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailerAutoload.php';
require_once __DIR__ . '/../Models/Geolocation.php';

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

	public function reportSync($type)
    {
    	switch ($type) {
		    case "seminars":
		        $jsonResponse = $this->sf->engageEndpoint($this->config['sf.seminar.report']);
		        $table = "seminars";
		        break;
		    case "events":
		        $jsonResponse = $this->sf->engageEndpoint($this->config['sf.event.report']);
		        $table = "events";
		        break;
		    default:
		    	return;
		}

		$qry = $this->_db->prepare("TRUNCATE ". $table .";");
		$qry->execute();

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

		$qry = $this->_db->prepare("INSERT INTO ". $table ." (" . $fieldClause . ") VALUES (" . $valueClause . ") 
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

		$this->sf->writeToLog($type.' sync successful');

		return 'SUCCESS';
    }

    public function passwordExpiration()
    {
    	//Do this
    	//http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-using-smtp-php.html
    	$jsonResponse = $this->sf->engageEndpoint($this->config['sf.password.notification']);

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

    		if($days == 7 || $days == 2) {
    			$this->notify($days, $user);
    		}
    	}

    	return;
    }

    public function seminarRegistration($data)
    {
    	$errors = array();
    	if(count($data) < 1) {
    		throw new Exception('No data entered');
    	}

    	/*if(array_key_exists('g-recaptcha-response', $data)) {
    		if (!$this->recaptchaCheck($data['g-recaptcha-response'])) {
    			$errors['g-recaptcha-response'] = 'Incorrect reCaptcha response';
    		}
    	} else {
    		$errors['g-recaptcha-response'] = 'There was no recaptcha field.';	
    	}

    	unset($data['g-recaptcha-response']);*/

    	if($data['birthday'] !== '') {
    		try {
			  	$date = new DateTime($data['birthday']);
	    		$data['birthday'] = $date->format('Y-m-d');
			}
			catch(Exception $e) {
				$errors['birthday'] = 'Birthday must be in the form of MM/DD/YYYY';
			}
    	} else {
    		unset($data['birthday']);	
    	}

    	if ($this->gumpValidation($data) === false) {
    		$errors = array_merge($errors, $this->gump->get_errors_array());
		} 

		if (sizeof($errors) > 0) {
			return json_encode($errors);
		}

		$geo = new Geolocation($this->_connection, null);
    	$county = $geo->getCounty($data['zip']);

		$this->attendee['City'] = $data['city'];
    	$this->attendee['State'] = $data['state'];
    	$this->attendee['Street'] = $data['address'];
    	$this->attendee['PostalCode'] = $data['zip'];
    	$this->attendee['CampaignId'] = $data['CampaignId'];
    	$this->attendee['FirstName'] = $data['firstName'];
    	$this->attendee['LastName'] = $data['lastName'];
    	$this->attendee['County__c'] = strtoupper($county[0]['county']);
    	$this->attendee['Marital_Status__c'] = 'U - Unknown';

    	if(array_key_exists('birthday', $data)) {
    		$this->attendee['DOB__c'] = $data['birthday'];	
    	}
    	
    	$this->attendee['Company'] = 'MEDICARE';
    	
		$campaignId = $this->attendee['CampaignId'];
		unset($this->attendee['CampaignId']);

		try {
			$leadId = $this->retrieveLeadId();
		} catch(Exception $e) {
			$errors['api'] = 'There was an issue obtaining lead memeber.'; 
			return json_encode($errors);
		}
				

		$leadMember = Array(
			"Response_Type__c" => "Online",
			"Response__c" => "Schedule seminar",
			"Status" => "Responded",
			"LeadId" => $leadId->id,
			"CampaignId" => $campaignId
		);

		try {
			$this->sf->engageEndpoint($this->config['sf.campaign.member.url'], 'POST', json_encode($leadMember));
		} catch(Exception $e) {
			$errors['api'] = 'There was an issue inserting the member.'; 
			return json_encode($errors);
		}

		return;	
    }

    //Should probably move to a helper file
    private function recaptchaCheck($response) {
    	try {

	        $url = 'https://www.google.com/recaptcha/api/siteverify';
	        $data = ['secret'   => $this->config['google.re'],
	                 'response' => $response
                 	];

	        $options = [
	            'http' => [
	                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	                'method'  => 'POST',
	                'content' => http_build_query($data) 
	            ]
	        ];

	        $context  = stream_context_create($options);
	        $result = file_get_contents($url, false, $context);

	        return json_decode($result)->success;
	    }
	    catch (Exception $e) {
	        return null;
    	}	
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
    	$medicareId = $this->sf->engageEndpoint($this->config['sf.record.type']);

		$medicareId = $medicareId->records['0']->Id;

		$this->attendee['Status'] = 'Open : Campaign Related';
		$this->attendee['RecordTypeId'] = $medicareId;

		$leadID = $this->sf->engageEndpoint($this->config['sf.lead.url'], 'POST', json_encode($this->attendee));
		$this->sf->engageEndpoint($this->config['sf.lead.url'].'/'.$leadID->id, 'PATCH', json_encode($this->attendee));

		return $leadID;
    }

    private function gumpValidation($data) 
    {
    	$data = $this->gump->sanitize($data);

        $this->gump->validation_rules(array(
        	'firstName' => 'required|alpha_space|max_len,100',
        	'lastName' => 'required|alpha_space|max_len,100',
        	'address' => 'required|max_len,100',
        	'city' => 'required|alpha|max_len,100',
        	'state' => 'required|exact_len,2',
        	'zip' => 'required|exact_len,5|numeric',
        	'birthday' => 'date',
        	'CampaignId' => 'required|alpha_numeric|max_len,100|min_len,3'
    	));

    	$this->gump->filter_rules(array(
		    'firstName' => 'trim|sanitize_string',
		    'lastName' => 'trim|sanitize_string',
		    'address' => 'trim|sanitize_string',
		    'city' => 'trim|sanitize_string',
		    'state' => 'trim|sanitize_string',
		    'zip' => 'trim|sanitize_numbers',
		    'birthday' => 'trim|sanitize_string',
		    'CampaignId' => 'trim|sanitize_string'
		));

		return $this->gump->run($data);
    }

    public function writeToLog($message) 
    {
		$message = date('m/d/Y h:i:sa', time()) . ' ' . $message;
		file_put_contents($this->config['log'], $message . "\r\n", FILE_APPEND | LOCK_EX);
	}
}

?>