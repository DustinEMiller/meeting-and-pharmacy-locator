<?php

require_once __DIR__ . '/../Helpers/Login.php';
require_once __DIR__ . '/../libs/gump.class.php';

class Salesforce
{

	protected $_connection;
    protected $_db;
    protected $sf;

	public function __construct($pdo)
	{
		$this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();	
        $this->sf = new Login();
	}

	public function seminarSync()
    {
    	$jsonResponse = $this->sf->engageEndpoint('/services/data/v37.0/analytics/reports/00OU0000003HwP3');

    	$fieldClause = "";
		$valueClause = "";
		$updateClause = "";

		foreach($jsonResponse->{'reportExtendedMetadata'}->{'detailColumnInfo'} as $key => $rowHeader) {
			$rechars = array(' ', '-');
			$dbColumns[] = strtolower(str_replace($rechars, '_', $rowHeader->{'label'}));
			$fieldClause .= strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ','; 
			$valueClause .= ':' . strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ',';
			$updateClause .= strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . '=' . 
				':' . strtolower(str_replace($rechars, '_', $rowHeader->{'label'})) . ',';
		}

		$fieldClause = rtrim($fieldClause, ',');
		$valueClause = rtrim($valueClause, ',');
		$updateClause = rtrim($updateClause, ',');

		$qry = $this->_db->prepare("INSERT INTO seminars (" . $fieldClause . ") VALUES (" . $valueClause . ") 
			ON DUPLICATE KEY UPDATE " . $updateClause);

		$reportRows = $jsonResponse->{'factMap'}->{'T!T'}->{'rows'};

		foreach($reportRows as $key => $dataCells) {

			foreach($dataCells as $dataCellKey => $dataCell) {

				foreach($dbColumns as $key => $rowHeader) {
					$data = $dataCell[$key]->{'label'};
					$param = ':'.$rowHeader;		

					if($rowHeader === 'start_date' || 
						$rowHeader === 'end_date' || 
						$rowHeader === 'created_date') {
							$data = date('Y-m-d', strtotime(str_replace('-', '/', $data)));
					}

					if($rowHeader === 'campaign_id' || $rowHeader === 'marketing_web') {
						$data = $dataCell[$key]->{'value'};	
					}

					$qry->bindValue($param, $data);
				}

				$qry->execute();
			}
		}
		$currentDate = date('Y-m-d');

		$this->sf->writeToLog('SUCCESS');

		return 'SUCCESS';
    }

    public function seminarRegistration($data)
    {
    	
    }

    public function passwordExpiration()
    {

    }
}

?>