
<?php
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Geolocation.php';

/*
	meeting/seminar/key/zip/radius
	meeting/seminar/key/city/state/radius
*/


class Meeting extends BaseController
{
	public function __construct($request, $origin) {
        parent::__construct($request);
        if (count($this->locationSettings) !== 2 || 
        		!is_numeric($this->args[0]) || 
        		!is_numeric($this->args[1])) {
                    throw new Exception('Incorrect URI structure for this endpoint');
                }
    }

    protected function seminars() {

    }

    protected function events() {

    }

}

?>