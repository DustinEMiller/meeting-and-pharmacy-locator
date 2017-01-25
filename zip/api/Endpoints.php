<?php
    require_once 'API.class.php';
    require_once 'Models/APIKey.php';
    require_once 'Models/ZIP.php';
    require_once 'Models/Pharmacy.php';
    require_once 'Models/Meeting.php';
    require_once 'Models/SeminarRegistration.php';
    require_once __DIR__ . '/../../Helpers/Cxn.php';
    
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
         * First do the external api. If it fails or is rejected then we revert to our
         * custom api. Collect the zip codes then ship off to the appropriate model.
         * Should we just use our custom api?
         */

        protected function seminarRegistration() {
            if ($this->method === 'POST') {
                $register = new SeminarRegistration();

                if ($register->validated()) {
                    //Do Add Lead
                } else {
                    //return json errors
                }
            }
        }

        protected function radius() {
            if ($this->method == 'GET') {
                if (count($this->args) != 2) {
                    throw new Exception('Incorrect URI structure for this endpoint');
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
                throw new Exception('Bad method type');
            }
        }
        
        protected function cityzips() {
            if ($this->method == 'GET') {
                if (count($this->args) != 2) {
                    throw new Exception('Incorrect URI structure for this endpoint');
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
                throw new Exception('Bad method type');
            }
        }
        
        protected function geocode() {
            if ($this->method == 'GET') {
                if (count($this->args) != 2) {
                    throw new Exception('Incorrect URI structure for this endpoint');
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
                throw new Exception('Bad method type');
            }
        }

        protected function zipcode() {
            if ($this->method == 'GET') {
                if (count($this->locationSettings) !== 2 || !is_numeric($this->args[0]) || !is_numeric($this->args[1])) {
                    throw new Exception('Incorrect URI structure for this endpoint');
                } else {
                    $zip = new ZIP(new Cxn("shirley"),$this->args);
                    $zipcodes = $zip->radius();

                    if(array_key_exists(0, $this->locationSettings) && $this->locationSettings[0] === 'pharmacy') {
                        if($this->locationSettings[1] === 'medicare' || $this->locationSettings[1] === 'medicare-preferred'){
                            if(count($this->args) !== 3 || !is_numeric($this->args[2])) {
                                throw new Exception('Incorrect URI structure for this endpoint');   
                            } else {
                                $location = new Pharmacy(new Cxn("shirley"), $this->locationSettings, $zipcodes, $this->args[2]);    
                            }
                        } else {
                            if(count($this->args) !== 2) {
                                throw new Exception('Incorrect URI structure for this endpoint');   
                            } else {
                                $location = new Pharmacy(new Cxn("shirley"), $this->locationSettings, $zipcodes);    
                            }    
                        }
                    }
                    else if(array_key_exists(0, $this->locationSettings) && $this->locationSettings[0] === 'meeting') {
                        $location = new Meeting(new Cxn("shirley"), $this->locationSettings, $zipcodes);
                    }
                    else {
                        throw new Exception('Bad location request');
                    }

                    if(array_key_exists('callback', $this->args)) {
                        return $this->args['callback'].'('.$location->results().')';
                    }
                    else {
                        return $location->results();
                    }
                    
                }
            } else {
                throw new Exception('Bad method type');
            }
        }
        
        protected function cityState() {
            if ($this->method == 'GET') {
                if (count($this->locationSettings) !== 2 || !is_numeric($this->args[2])) {
                    throw new Exception('Incorrect URI structure for this endpoint');
                } else {
                    $zip = new ZIP(new Cxn("shirley"),$this->args);
                    $zipcodes = $zip->cityzips();

                    if(array_key_exists(0, $this->locationSettings) && $this->locationSettings[0] === 'pharmacy') {
                        if($this->locationSettings[1] === 'medicare' || $this->locationSettings[1] === 'medicare-preferred'){
                            if(count($this->args) !== 4 || !is_numeric($this->args[3])) {
                                throw new Exception('Incorrect URI structure for this endpoint');   
                            } else {
                                $location = new Pharmacy(new Cxn("shirley"), $this->locationSettings, $zipcodes, $this->args[3]);    
                            }
                        } else {
                            if(count($this->args) !== 3) {
                                throw new Exception('Incorrect URI structure for this endpoint');   
                            } else {
                                $location = new Pharmacy(new Cxn("shirley"), $this->locationSettings, $zipcodes);    
                            }    
                        }
                    }
                    else if(array_key_exists(0, $this->locationSettings) && $this->locationSettings[0] === 'meeting') {
                        $location = new Meeting(new Cxn("shirley"), $this->locationSettings, $zipcodes);
                    }
                    else {
                        throw new Exception('Bad location request');
                    }

                    if(array_key_exists('callback', $this->args)) {
                        return $this->args['callback'].'('.$location->results().')';
                    }
                    else {
                        return $location->results();
                    }
                    
                }
            } else {
                throw new Exception('Bad method type');
            }
        }
    }
?>
