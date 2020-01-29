
<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Meeting.php';

/*
	meeting/seminar/key/locationType/zip/radius
	meeting/seminar/key/locationType/city/state/radius
*/


class MeetingController extends BaseController
{
    protected $zipcodes = Array();
    protected $meetings;

	public function __construct($args, $endpoint, $domain) 
	{
        parent::__construct($args, $endpoint, $domain);

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $brandKey = array_search('brand', $this->args);
            $brandArray = array();
            $campaignId = array_search('campaignid', $this->args);
            $campaignArray = array();


            if($brandKey) {
                $brandArray = array_slice($this->args, $brandKey);
                array_splice($this->args, $brandKey++, count($brandArray));
            }

            if($campaignId) {
                $campaignArray = array_slice($this->args, $campaignId);
                array_splice($this->args, $campaignId++, count($campaignArray));
            }

            if($campaignId != "") {
                $this->zipcodes = $this->locationVerification();
            }

            $this->locationType = array_shift($this->args);

            $this->meetings = new Meeting(new Cxn("shirley"), $this->zipcodes, $brandArray, $campaignId);
        } else {
            throw new Exception('Incorrect URL Structure');
        }

    }

    protected function seminars() 
    {
    	$this->setGetAccess();
        return $this->meetings->seminars();
    }

    protected function events() 
    {
    	$this->setGetAccess();
        return $this->meetings->events();
    }

}

?>