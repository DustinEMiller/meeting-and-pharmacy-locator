<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Pharmacy.php';

class TruhearingController extends BaseController
{
    protected $zipcodes = Array();
    protected $locations;

    public function __construct($args, $endpoint, $domain)
    {
        parent::__construct($args, $endpoint, $domain);

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->locationType = array_shift($this->args);
            $this->zipcodes = $this->locationVerification();
            $this->locations= new Truhearing(new Cxn("shirley"), $this->zipcodes);
        } else {
            throw new Exception('Incorrect URL Structure');
        }

    }

    public function all()
    {
        $this->setGetAccess();
        return $this->locations->all();
    }

    public function his()
    {
        $this->setGetAccess();
        return $this->locations->his();
    }

    public function aud()
    {
        $this->setGetAccess();
        return $this->locations->aud();
    }

}
?>