<?php

abstract class API
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
     * Property: locationSettings
     * Holds the location type and location subtype
     */
    protected $locationSettings = Array();
    /**
     * Property: args
     * Any additional URI components after the endpoint and key have been removed, in our
     * eg: /<endpoint>/<verb>/<arg0>/<arg1> or /<endpoint>/<arg0>
     */
    protected $args = Array();
    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = Null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
        
    public function __construct($request) {
        $http_origin = $_SERVER['HTTP_ORIGIN'];
        $allowedOrigins = array("http://medicare.healthalliance.org", "http://healthalliance.org", "http://www.healthalliance.org", "http://medicaid.healthalliance.org", "https://www.healthalliance.org", "http://devhealthalliance", "http://devmedicare", "http://devmedicaid", "http://testmedicare", "http://testhealthalliance", "http://devmedicaid", "http://52.11.174.73");
        if(in_array($http_origin, $allowedOrigins)) {
                header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
                header("Access-Control-Allow-Methods: GET");
                header("Content-Type: application/json charset=utf-8");
        }

        $this->args = explode('/', rtrim($request, '/'));
        $this->args = $this->_cleanInputs($this->args);
        $this->endpoint = array_shift($this->args);

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->key = array_shift($this->args);
        }

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0]) && 
            ($this->args[0] === 'pharmacy' || $this->args[0] === 'meeting')) {
            $this->locationSettings = array_slice($this->args, 0, 2);
            array_splice($this->args, 0, 2);
        }

        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        //This api is only for retrieving data
        switch($this->method) {
        //case 'DELETE':
        case 'POST':
            $this->request = $this->_cleanInputs($_POST);
            break;
        case 'GET':
            $this->request = $this->_cleanInputs($_GET);
            break;
        //case 'PUT':
            //$this->request = $this->_cleanInputs($_GET);
            //$this->file = file_get_contents("php://input");
            //break;
        default:
            $this->_response('Invalid Method', 405);
            break;
        }
    }
    
     public function processAPI() {
        if (method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response("Error: No Endpoint: $this->endpoint", 404);
    }

    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }

        return $clean_input;
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
