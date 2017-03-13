
<?php

	class Login
	{
		private $config; 

		public function __construct() 
	    {
	    	$this->config = include(__DIR__ . '/../Helpers/config.php');
	    	$this->login();
	    }

	    private function login()
	    {
	    	session_start();
	    	$curl = curl_init($this->config['sf.loginurl']);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(
				array(
					'grant_type' => 'password', 
					'client_id' => $this->config['sf.clientid'], 
					'client_secret' => $this->config['sf.clientsecret'], 
					'username' => $this->config['sf.username'], 
					'password' => $this->config['sf.password'] . $this->config['sf.securitytoken']
				)
			));

			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/x-www-form-urlencoded'));

			$jsonResponse = json_decode(curl_exec($curl), true);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE); 

			if ($status >= 300) {	
				$this->writeToLog('Error: call failed with status ' . $status);
				$this->writeToLog(print_r($jsonResponse, true));
				$this->writeToLog('curl_error ' . curl_errno($curl));
	        	throw new Exception('curl_error ' . curl_errno($curl));
	    	}

			curl_close($curl);
			
			$_SESSION['access_token'] = $jsonResponse['access_token'];
			$_SESSION['instance_url'] = $jsonResponse['instance_url'];

			$token = $jsonResponse['access_token'];

			if (!$_SESSION['access_token'] || $_SESSION['access_token'] == "") {
	            $this->writeToLog('Error - access token missing from session!');
	        	throw new Exception('access token missing from session!');
	        }

	        if (!$_SESSION['instance_url'] || $_SESSION['instance_url'] == "") {
	            $this->writeToLog('Error - instance URL missing from session!');
	        	throw new Exception('instance URL missing from session!');
	        }
	    }

	    public function engageEndpoint($url, $method = "GET", $data = null) 
	    {
	    	$token = $_SESSION['access_token'];
		
			$curl = curl_init($_SESSION['instance_url'].$url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ". $token));
			
			if($method === "POST" && $data !== null) {
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ". $token,'Content-Type: application/json'));
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
			} else if ($method === "PATCH" && $data !== null) {
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ". $token,'Content-Type: application/json'));
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
				curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
			}

			$jsonResponse = json_decode(curl_exec($curl));
			
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	                
            if($status >=  300) {	
            	$this->writeToLog($url);
				$this->writeToLog('Error: call failed with status ' . $status);
				$this->writeToLog(print_r($jsonResponse, true));
				$this->writeToLog('curl_error ' . curl_errno($curl));
	        	throw new Exception(print_r($jsonResponse, true));
	    	}
	 
			curl_close($curl);
			
			return $jsonResponse;
	    }

	    public function writeToLog($message) 
	    {
			$message = date('m/d/Y h:i:sa', time()) . ' ' . $message;
			file_put_contents($this->config['log'], $message . "\r\n", FILE_APPEND | LOCK_EX);
		}


	}

?>