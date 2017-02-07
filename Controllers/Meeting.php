
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
    protected $zipcodes = Array();


	public function __construct($request, $origin) {
        parent::__construct($request);

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->locationType = array_shift($this->args);
            $this->zipcodes = $this->locationVerification($this->locationType, $this->args);
        } else {
            throw new Exception('Incorrect URL Structure');
        }

    }

    protected function seminars() {
        $meetings = new Meeting(new Cxn("shirley"), $this->zipcodes);
        return $meetings->seminars();
    }

    protected function events() {
        $meetings = new Meeting(new Cxn("shirley"), $this->zipcodes);
        return $meetings->events();
    }

}

?>