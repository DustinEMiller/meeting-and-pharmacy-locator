<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ZIP
 *
 * @author dumiller
 */
class Providers
{
    
    protected $_connection;
    protected $_db;
    private $zipCodes = array();
    private $arguments = array();

    public function __construct($pdo, $zipCodes = array(), $args)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();
        $this->arguments = $args;

        if(!empty($zipCodes)) {
            foreach ($zipCodes['zip_codes'] as $v) {
                $this->zipCodes[] = $v['zip_code'];
            }
        }
    }

    //Add 'order by' that is linked fto nearest to furthest zip code
    public function eavSync()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $this->_db->beginTransaction();
        $stmt = $this->_db->prepare('TRUNCATE TABLE askshirley.providers_type_specialty');
        $stmt->execute();

        $this->_db->commit();

        $qry = $this->_db->prepare('SELECT DISTINCT specialty_list_all, provider_type FROM askshirley.providers ORDER BY provider_type');

        $qry->execute();

        $type = '';
        $specialties = array();
        $rowCount = 0;

        while ($row = $qry->fetch(PDO::FETCH_ASSOC)) {
            $rowCount++;
            if($type != $row['provider_type'] || $rowCount == $qry->rowCount()) {
                if($type != '') {

                    $stmt = $this->_db->prepare('INSERT INTO askshirley.providers_type_specialty (type, specialty) VALUES (:val1, :val2)');

                    $this->_db->beginTransaction();

                    // The queries are not executed yet, but pushed to a transaction "stack"
                    foreach ($specialties as $special) {
                        $stmt->execute([
                            ':val1' => $type,
                            ':val2' => $special,
                        ]);
                    }

                    // Executes all the queries "at once"
                    $this->_db->commit();

                    $specialties = array();
                }
                $type = $row['provider_type'];

            }

            $tempSpecialties = explode(',', $row['specialty_list_all']);

            foreach($tempSpecialties as $specialty) {
                if(!in_array(trim($specialty), $specialties)) {
                    $specialties[] =  trim($specialty);
                }
            }
        }

        $this->_db->beginTransaction();
        $stmt = $this->_db->prepare('TRUNCATE TABLE askshirley.providers_providers_specialty');
        $stmt->execute();

        $this->_db->commit();

        $qry = $this->_db->prepare('SELECT id, specialty_list_all FROM askshirley.providers ORDER BY provider_type');

        $qry->execute();

        while ($row = $qry->fetch(PDO::FETCH_ASSOC)) {
            $stmt = $this->_db->prepare('INSERT INTO askshirley.providers_providers_specialty (providers_id, specialty) VALUES (:val1, :val2)');

            $this->_db->beginTransaction();

            $specsList = explode(',', $row['specialty_list_all']);

            // The queries are not executed yet, but pushed to a transaction "stack"
            foreach ($specsList as $spec) {
                $stmt->execute([
                    ':val1' => $row['id'],
                    ':val2' => trim($spec),
                ]);
            }

            // Executes all the queries "at once"
            $this->_db->commit();
        }

        return;
    }

    //Add 'order by' that is linked fto nearest to furthest zip code
    public function markers($data)
    {

        if(count($this->arguments) != 1) {
            $result['error']  = 'Incorrect number of params';
            return($result);
        } else {

            if(!array_key_exists('minlat', $data) || !array_key_exists('maxlat', $data) ||
                !array_key_exists('minlong', $data) || !array_key_exists('maxlong', $data) ||
                !array_key_exists('type', $data) || !array_key_exists('specialty', $data)) {

                $result['error']  = 'Incorrect params';
                return($result);
            }
        }

        if(strtolower($data['type']) == 'all') {
            $data['type'] = '%';
        }

        if(strtolower($data['specialty']) == 'all') {
            $data['specialty'] = '%';
        }

        if(strtolower($data['minlat']) == 'all') {
            $qry = $this->_db->prepare('SELECT full_name, gender, eprescribe, wheelchair_accessible, language_list, provider_type, address, 
          address2, city, state, zip, lat, providers.long, phone_number, fax_number, hours_operation, providers_providers_specialty.specialty
          FROM askshirley.providers inner join providers_providers_specialty on providers.id = providers_providers_specialty.providers_id 
          where providers.provider_type LIKE :type AND providers_providers_specialty.specialty LIKE :specialty');

            $qry->bindParam(':type', $data['type']);
            $qry->bindParam(':specialty', $data['specialty']);
        } else {
            $qry = $this->_db->prepare('SELECT full_name, gender, eprescribe, wheelchair_accessible, language_list, provider_type, address, 
          address2, city, state, zip, lat, providers.long, phone_number, fax_number, hours_operation, providers_providers_specialty.specialty
          FROM askshirley.providers inner join providers_providers_specialty on providers.id = providers_providers_specialty.providers_id 
          where providers.provider_type LIKE :type AND providers_providers_specialty.specialty LIKE :specialty AND 
          providers.lat BETWEEN :minlat AND :maxlat AND providers.long BETWEEN :minlong AND :maxlong LIMIT 50000');

            $qry->bindParam(':type', $data['type']);
            $qry->bindParam(':specialty', $data['specialty']);
            $qry->bindParam(':minlat', $data['minlat']);
            $qry->bindParam(':maxlat', $data['maxlat']);
            $qry->bindParam(':minlong', $data['minlong']);
            $qry->bindParam(':maxlong', $data['maxlong']);
        }

        $qry->execute();

        $result['results']  = $qry->fetchAll(PDO::FETCH_ASSOC);
        return($result);
    }

    public function typeDependency() {

        $qry = $this->_db->prepare('SELECT * from providers_type_specialty');

        $qry->execute();

        $result['results']  = $qry->fetchAll(PDO::FETCH_ASSOC);
        return($result);
    }
}