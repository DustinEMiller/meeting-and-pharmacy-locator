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
class Truhearing
{
    
    protected $_connection;
    protected $_db;
    private $queryCache = array();
    private $zipCodes = array();

    public function __construct($pdo, $zipCodes)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();

        foreach ($zipCodes['zip_codes'] as $v) {
            $this->zipCodes[] = $v['zip_code'];
        }  
    }

    //Add 'order by' that is linked fto nearest to furthest zip code
    public function all()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT provider_name, specialty, clinic_name, address, city, state, zip, phone
            FROM askshirley.truhearing where zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);  
    }

    public function his()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT provider_name, specialty, clinic_name, address, city, state, zip, phone
            FROM askshirley.truhearing where specialty = "Hearing Instrument Specialist" AND zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);
    }

    public function aud()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT provider_name, specialty, clinic_name, address, city, state, zip, phone
            FROM askshirley.truhearing where specialty = "Audiologist" AND zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);
    }
}