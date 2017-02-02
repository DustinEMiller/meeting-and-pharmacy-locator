<?php

class Loader {
    
    private $controllerName;
    private $controllerClass;
    private $action;
    private $urlValues;
    protected $args = Array();
    
    //store the URL request values on object creation
    public function __construct($request, $domain) {

        $this->args = explode('/', rtrim($request, '/'));
        $this->args = $this->_cleanInputs($this->args);

        $this->controllerName = strtolower(array_shift($this->args));
        $this->controllerClass = ucfirst(strtolower($this->urlValues['controller'])) . "Controller";

        $this->action = strtolower(array_shift($this->args));

    }
                  
    //factory method which establishes the requested controller as an object
    public function createController() {
        //check our requested controller's class file exists and require it if so
        if (file_exists("Controllers/" . $this->controllerName . ".php")) {
            require("Controllers/" . $this->controllerName . ".php");
        } else {
            throw new Exception('Route does not exist');
        }
                
        //does the class exist?
        if (class_exists($this->controllerClass)) {
            $parents = class_parents($this->controllerClass);
            
            //does the class inherit from the BaseController class?
            if (in_array("BaseController",$parents)) {   
                //does the requested class contain the requested action as a method?
                if (method_exists($this->controllerClass, $this->action)) {
                    return new $this->controllerClass($this->args, $this->action, $domain);
                } else {
                    throw new Exception('Action does not exist');
                }
            } else {
                throw new Exception('Class does not inherit correctly.');
            }
        } else {
            throw new Exception('Controller does not exist.');
        }
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
}
?>