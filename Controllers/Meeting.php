
<?php
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Geolocation.php';
/*
	meeting/seminar/key/locationType/zip/radius
	meeting/seminar/key/locationType/city/state/radius
*/


class Meeting extends BaseController
{
    protected $locationType = '';


	public function __construct($request, $origin) {
        parent::__construct($request);

        $this->locationType = 

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->locationType = array_shift($this->args);
        }

        $this->locationsVerification();

    }

    protected function seminars() {

    }

    protected function events() {

    }

}

?>