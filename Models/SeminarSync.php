<?php
	require_once __DIR__ . '/../Helpers/Cxn.php';

	session_start();

	error_reporting(E_ALL);
	ini_set('display_errors', 'On');

	function login() {
		//This needs to go into a class that then utilizes a config class
		$curl = curl_init('https://login.salesforce.com/services/oauth2/token');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
			'grant_type' => 'password', 
			'client_id' => get_cfg_var('salesforce.clientid'), 
			'client_secret' => get_cfg_var('salesforce.clientsecret'), 
			'username' => get_cfg_var('salesforce.username'), 
			'password' => get_cfg_var('salesforce.pwd') . get_cfg_var('salesforce.securitytoken'))));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/x-www-form-urlencoded'));
		$jsonResponse = json_decode(curl_exec($curl), true);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE); 

		if ( $status != 200 ) {			
			writeToLog('Error: call failed with status '.$status.', response '. $jsonResponse .', curl_error ' . curl_error($curl) . ', curl_errno ' . curl_errno($curl));
//print_r($jsonResponse);
        	die();
    	}
		curl_close($curl);
		
		$_SESSION['access_token'] = $jsonResponse['access_token'];
		$_SESSION['instance_url'] = $jsonResponse['instance_url'];

		$token = $jsonResponse['access_token'];

		if (!$_SESSION['access_token'] || $_SESSION['access_token'] == "") {
            writeToLog('Error - access token missing from session!');
        	die();
        }

        if (!$_SESSION['instance_url'] || $_SESSION['instance_url']== "") {
            writeToLog('Error - instance URL missing from session!');
        	die();
        }
	}

	function syncData() {
		$token = $_SESSION['access_token'];
		
		$curl = curl_init($_SESSION['instance_url'].'/services/data/v34.0/analytics/reports/00OU0000003HwP3');
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ". $token));
		$jsonResponse = json_decode(curl_exec($curl));
		
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                
                if ( $status != 200 ) {
                writeToLog('Error: call failed with status' .  $status . ', response '. $jsonResponse . ', curl_error ' . curl_error($curl));
                die();
        }
 
		curl_close($curl);

		$fieldClause = "";
		$valueClause = "";
		$updateClause = "";

		foreach($jsonResponse->{'reportExtendedMetadata'}->{'detailColumnInfo'} as $key => $rowHeader) {
			$rechars = array(' ', '-');
			$dbColumns[] = strtolower(str_replace($rechars, '_', $rowHeader->{'label'}));
			$fieldClause .= strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ','; 
			$valueClause .= ':' . strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ',';
			$updateClause .= strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . '=' . ':' . strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ',';
		}

		$fieldClause = rtrim($fieldClause, ',');
		$valueClause = rtrim($valueClause, ',');
		$updateClause = rtrim($updateClause, ',');

		$connection = new Cxn("shirley");
		$db = $connection->getDb();

		$qry = $db->prepare("INSERT INTO seminars (" . $fieldClause . ") VALUES (" . $valueClause . ") ON DUPLICATE KEY UPDATE " . $updateClause);
		$console = 0;
		$reportRows = $jsonResponse->{'factMap'}->{'T!T'}->{'rows'};
		foreach($reportRows as $key => $dataCells) {
			foreach($dataCells as $dataCellKey => $dataCell) {
				foreach($dbColumns as $key => $rowHeader) {
					$data = $dataCell[$key]->{'label'};
					$param = ':'.$rowHeader;

					if($rowHeader === 'start_date' || $rowHeader === 'end_date' || $rowHeader === 'created_date') {
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

		writeToLog('SUCCESS');
	}

	function writeToLog($message) {
		$file = '/var/log/sf/sf.log';
		$message = date('m/d/Y h:i:sa', time()) . ' ' . $message;
		file_put_contents($file, $message . "\r\n", FILE_APPEND | LOCK_EX);
	}

	login();
	syncData();
?>
