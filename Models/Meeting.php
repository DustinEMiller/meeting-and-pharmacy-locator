<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the templates in the editor.
 */

/**
 * Description of ZIP
 *
 * @author dumiller
 */
class Meeting 
{
    
    protected $_connection;
    protected $_db;
    private $queryCache = array();
    private $zipCodes = array();
    private $brand;
    private $debug;

    public function __construct($pdo, $zipCodes, $brandArray)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();
        $this->debug = "no brand";
        if(count($brandArray) == 2) {
            $this->brand = $brandArray[1];
            $this->debug = "brand = " . $this->brand;
        }

        foreach ($zipCodes['zip_codes'] as $v) {
            $this->zipCodes[] = $v['zip_code'];
        } 
    }

    //Add 'order by' that is linked fto nearest to furthest zip code
    //Should add where condition dor data of meeting
    
    public function events()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }

        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT campaign_id, campaign_name, location, address, address2, city, zip, state, start_date, end_date, start_time, end_time, room_name, presentation_language
            FROM askshirley.events where zip IN ('.$inParams.') ORDER BY start_time ASC');

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results']  = $qry->fetchAll();
        return($result);  
    }

    public function seminars()
    {
        if(count($this->zipCodes) == 0) {
            $result['results'] = array();
            return $result;
        }
        
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $now = new DateTime('now');
        $upperBound = new DateTime('Oct 1');
        $lowerBound = new DateTime('Dec 31');

        if($now >= $upperBound && $now <= $lowerBound) {
            if(isset($this->brand)) {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where brand LIKE :brand AND zip IN ('.$inParams.') ORDER BY start_date, start_time ASC' );
                $qry->bindValue(":brand", $this->brand);
                $this->debug .= " 1 ";
            } else {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where zip IN ('.$inParams.') ORDER BY start_date, start_time ASC' );
                $this->debug .= " 2 ";
            }
        } else {
            if(isset($this->brand)) {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where brand LIKE :brand AND zip IN ('.$inParams.') AND month(start_date) < 10 ORDER BY start_date, start_time ASC' );
                $qry->bindValue(":brand", $this->brand);
                $this->debug .= " 3 ";
            } else {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where zip IN ('.$inParams.') AND month(start_date) < 10 ORDER BY start_date, start_time ASC' );
                $this->debug .= " 4 ";
            }
        }

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($this->debug . " /n" . $result);
    }
}