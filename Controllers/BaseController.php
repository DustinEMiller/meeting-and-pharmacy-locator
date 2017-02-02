<?php

abstract class BaseController {

	/**
     * Property: key
     * API Key to connect
     */
    protected $key = '';

	public function __construct($args, $action, $domain) {
		$verification = new Access(new Cxn("shirley"));
		
		if (!$this->key) {
            throw new Exception('No API Key provided');
        } else if (!$APIKey->verifyKey($this->key, $origin)) {
            throw new Exception('Invalid API Key');
        }	
	}

	public function executeAction() {

        if (method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response("Error: No Endpoint: $this->endpoint", 404);

    }

    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    private function _requestStatus($code) {
        $status = array(  
            200 => 'OK',
            404 => 'Not Found',   
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ); 
        return ($status[$code])?$status[$code]:$status[500]; 
    }
	
}

?>