<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../Helpers/Cxn.php';
require_once __DIR__ . '/../Models/Providers.php';

class ProvidersController extends BaseController
{
    protected $zipcodes = Array();
    protected $service;

    public function __construct($args, $endpoint, $domain)
    {
        parent::__construct($args, $endpoint, $domain);
        $this->service = new Providers(new Cxn("shirley"), $this->zipcodes, $args);

    }

    protected function eavSync()
    {
        $this->setGetAccess();
        return $this->service->eavSync();
    }

    protected function markers()
    {
        $this->setPostAccess();
        return $this->service->markers($_POST);
    }

    protected function typeDependency()
    {
        $this->setGetAccess();
        return $this->service->typeDependency();
    }

}
?>