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
class Pharmacy 
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
    public function network()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

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

    public function preferred()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT nabp, npi, pharmacy_name, address, address_2, city, state, zip, phone, fax 
            FROM askshirley.preferred_pharmacies where zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }

    public function preferredPlus()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT nabp, npi, pharmacy_name, address, address_2, city, state, zip, phone, fax 
            FROM askshirley.preferred_plus_pharmacies where zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }

    public function commercial($type)
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

        $type = strtolower($type);

        $preferred = '%';
        $preferredPlus = '%';

        if($type == 'preferred') {
            $preferred = 'Yes';
        } else if($type == 'preferredplus') {
            $preferredPlus = 'Yes';
        }

        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT nabp, npi, pharmacy_name, address, address2, city, state, zip, phone, fax 
            FROM askshirley.commercial_pharmacies where preferred LIKE ? AND preferred_plus LIKE ? AND zip IN ('.$inParams.')');

        $qry->bindValue(1, $preferred);
        $qry->bindValue(2, $preferredPlus);

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+3), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);
    }

    public function medicaid()
    {
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT npi, pharmacy_name, address, address_2, city, state, zip, type, county 
            FROM askshirley.medicaid_pharmacies where zip IN ('.$inParams.')');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }

    public function medicare($preferred, $year)
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }
        
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT nabp, npi, pharmacy_name, address, address_2, city, state, zip, phone, fax 
            FROM askshirley.medicare_pharmacies year = ? AND zip IN ('.$inParams.')');

        $qry->bindValue(1, $preferred ? 'Yes' : 'No');
        $qry->bindValue(2, $year);

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+3), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }
}
