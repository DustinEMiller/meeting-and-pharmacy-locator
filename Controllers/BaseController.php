<?php
require_once __DIR__ . '/../Helpers/Access.php';
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Geolocation.php';

abstract class BaseController 
{

    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';

	/**
     * Property: key
     * API Key to connect
     */
    protected $key = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint and key have been removed, in our
     * eg: /<endpoint>/<verb>/<arg0>/<arg1> or /<endpoint>/<arg0>
     */
    protected $args = Array();

    protected $locationType = '';

	public function __construct($args, $endpoint, $domain) 
    {
        $this->args = $args;
        $this->endpoint = $endpoint;
        $this->method = $_SERVER['REQUEST_METHOD'];

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->key = array_shift($this->args);
        }

		$verification = new Access(new Cxn("shirley"));

        if($verification->verifyDomain($domain)) {
            header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_REFERER']);
            header("Content-Type: application/json charset=utf-8");    
        }
		
		if (!$this->key) {
            throw new Exception('No API Key provided');
        } else if (!$verification->verifyKey($this->key, $domain)) {
            throw new Exception('Invalid API Key');
        }

        $this->method = $_SERVER['REQUEST_METHOD'];

        if($this->method !== 'POST' && $this->method !== 'GET') {
            $this->_response('Invalid Method', 405);
            throw new Exception('Method not allowed');
        }

	}

	public function executeAction() 
    {
        if (method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        throw new Exception("No Endpoint: $this->endpoint");
    }

    public function setGetAccess() {
        if($this->method !== 'GET') {
            $this->_response('Invalid Method', 405);
        } else {

        }
        header("Access-Control-Allow-Methods: GET");
    }

    public function setPostAccess() {
        if($this->method !== 'POST') {
            $this->_response('Invalid Method', 405);
        } else {
            header("Access-Control-Allow-Methods: POST");    
        }
        
    }

    protected function locationVerification() 
    {

        if (strtolower($this->locationType) === 'zipcode') {
            if (count($this->args) < 2 || 
                !is_numeric($this->args[0]) || 
                !is_numeric($this->args[1])) {
                    throw new Exception('Incorrect URI structure for this endpoint');
            } else {
                $zip = new Geolocation(new Cxn("shirley"),$this->args);
                return $zip->radius();
            }
        } else if (strtolower($this->locationType) === 'citystate') {
            if (count($this->args) < 3 || !is_numeric($this->args[2])) {
                throw new Exception('Incorrect URI structure for this endpoint');
            } else {
                $zip = new Geolocation(new Cxn("shirley"),$this->args);
                return $zip->cityzips();       
            }

        } else {
            throw new Exception('Incorrect URI structure for this endpoint');
        }
    
    }

    private function _response($data, $status = 200) 
    {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    private function _requestStatus($code) 
    {
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