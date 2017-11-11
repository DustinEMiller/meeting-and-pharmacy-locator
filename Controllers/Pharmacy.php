<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Pharmacy.php';

class PharmacyController extends BaseController
{
	protected $zipcodes = Array();
	protected $pharmacies;

	public function __construct($args, $endpoint, $domain) 
	{
		parent::__construct($args, $endpoint, $domain);

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->locationType = array_shift($this->args);
            $this->zipcodes = $this->locationVerification();
            $this->pharmacies = new Pharmacy(new Cxn("shirley"), $this->zipcodes);
        } else {
            throw new Exception('Incorrect URL Structure');
        }

	}

	public function network() 
	{
		$this->setGetAccess();
        return $this->pharmacies->network();
	}

	public function preferred() 
	{
		$this->setGetAccess();
		return $this->pharmacies->preferred() ;	
	}

	public function preferredPlus() 
	{
		$this->setGetAccess();
		return $this->pharmacies->preferredPlus() ;	
	}

    public function commercial()
    {
        $this->setGetAccess();
        return $this->pharmacies->commercial($this->extractType());
    }

	public function medicaid() 
	{
		$this->setGetAccess();
		return $this->pharmacies->medicaid();		
	}

	public function medicare() 
	{
		$this->setGetAccess();
		return $this->pharmacies->medicare(false, $this->extractYear());		
	}

	public function medicarePreferred() 
	{
		$this->setGetAccess();
		return $this->pharmacies->medicare(true, $this->extractYear());		
	}

	private function extractYear()
	{
		if(count($this->args) === 3 && is_numeric($this->args[2])) {
			$this->setGetAccess();
			return $this->args[2];
		} else if(count($this->args) === 4 && is_numeric($this->args[3])) {
			$this->setGetAccess();
			return $this->args[3];	
		} else {
			throw new Exception('Incorrect URL Structure');	
		}
	}

    private function extractType()
    {
        return end($this->args);
    }



}
?>