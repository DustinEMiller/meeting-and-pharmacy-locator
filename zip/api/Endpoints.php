<?php
    require_once 'API.class.php';
    require_once 'Models\APIKey.php';
    require_once 'Models\ZIP.php';
    require_once 'Helpers\Cxn.php';
    
    class Endpoints extends API
    {

        public function __construct($request, $origin) {
            parent::__construct($request);

            $APIKey = new APIKey(new Cxn("shirley"));
            
            //Old way using query string
            /*if (!array_key_exists('apiKey', $this->request)) {
                throw new Exception('No API Key provided');
            } else if (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
                throw new Exception('Invalid API Key');
            }*/
            
            if (!$this->key) {
                throw new Exception('No API Key provided');
            } else if (!$APIKey->verifyKey($this->key, $origin)) {
                throw new Exception('Invalid API Key');
            }
        }
        
        /* 
         * Endpoints
         */
        protected function radius() {
            if ($this->method == 'GET') {
                if (count($this->args) != 2) {
                    return "Error: Incorrect URI structure for this endpoint";
                } else {
                    $zip = new ZIP(new Cxn("shirley"),$this->args);
                    if(array_key_exists('callback', $this->args)) {
                        return $this->args['callback'].'('.$zip->radius().')';
                    }
                    else {
                        return $zip->radius();
                    }
                    
                }
            } else {
                return "Error: Bad method type";
            }
        }
        
        protected function cityzips() {
            if ($this->method == 'GET') {
                if (count($this->args) != 2) {
                    return "Error: Incorrect URI structure for this endpoint";
                } else {
                    $zip = new ZIP(new Cxn("shirley"),$this->args);
                    if(array_key_exists('callback', $this->args)) {
                        return $this->args['callback'].'('.$zip->cityzips().')';
                    }
                    else {
                        return $zip->cityzips();   
                    }
                    
                }
            } else {
                return "Error: Bad method type";
            }
        }
        
        protected function geocode() {
            if ($this->method == 'GET') {
                if (count($this->args) != 2) {
                    return "Error: Incorrect URI structure for this endpoint";
                } else {
                    $zip = new ZIP(new Cxn("shirley"),$this->args);
                    if(array_key_exists('callback', $this->args)) {
                        return $this->args['callback'].'('.$zip->geocode().')';
                    }
                    else {
                        return $zip->geocode();
                    }
                    
                }
            } else {
                return "Error: Bad method type";
            }
        }
    }
?>