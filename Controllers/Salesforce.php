<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Salesforce.php';

class SalesforceController extends BaseController
{
	protected $sf;

	public function __construct($args, $endpoint, $domain) 
	{
		parent::__construct($args, $endpoint, $domain);

		$this->sf= new Salesforce(new Cxn("shirley"));
	}

	protected function seminarSync() 
    {
    	$this->setGetAccess();
        return $this->sf->seminarSync();
    }

    protected function passwordExpiration() 
    {
    	$this->setGetAccess();
        return $this->sf->passwordExpiration();
    }

    protected function seminarRegistration() 
    {
    	$this->setPostAccess();
        return $this->sf->seminarRegistration($_POST);
    }

}
?>