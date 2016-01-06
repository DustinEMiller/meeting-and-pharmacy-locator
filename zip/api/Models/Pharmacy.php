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
class Pharmacy {
    
    protected $_connection;
    protected $_db;
    private $queryCache = array();
    private $zipCodes = array();
    private $pharmacyType = array();

    public function __construct($pdo, $locationSettings, $zipCodes)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();
        $this->pharmacyType = $locationSettings;

        foreach ($zipCodes['zip_codes'] as $v) {
            $this->zipCodes[] = $v['zip_code'];
        }  
    }

    public function results()
    {
        switch ($this->pharmacyType[1]) {
            case 'network':
                return $this->network();
                break;
            case 'preferred':
                return $this->preferred();
                break;
            case 'preferred-plus':
                return $this->preferredPlus();
                break;
            default:
                throw new Exception('Bad location sub type used.');
        }

    }

    //Add 'order by' that is linked fto nearest to furthest zip code
    private function network()
    {
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT nabp, pharmacy_name, address, city, state, zip, phone, fax, npi 
            FROM askshirley.network_pharmacies where zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);  
    }

    private function preferred()
    {
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT nabp, pharmacy_name, address, city, state, zip 
            FROM askshirley.preferred_pharmacies where zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }

    private function preferredPlus()
    {
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT nabp, pharmacy_name, address, city, state, zip 
            FROM askshirley.preferred_plus_pharmacies where zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }
}