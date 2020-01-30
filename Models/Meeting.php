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
    private $campaignId;
    private $debug;

    public function __construct($pdo, $zipCodes, $brandArray, $campaignId)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();

        if(count($brandArray) == 2) {
            if(strtolower($brandArray[1]) != 'null') {
                $this->brand = urldecode($brandArray[1]);
            }
            $this->debug = "brand = " . $this->brand;
        }

        if($campaignId) {
            $this->campaignId = $campaignId;
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
        if(count($this->zipCodes) == 0 && !$this->campaignId) {
            $result['results'] = array();
            return $this->debug;
        }
        
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $now = new DateTime('now');
        $upperBound = new DateTime('Oct 1');
        $lowerBound = new DateTime('Dec 31');
        $index = 0;

        if($now >= $upperBound && $now <= $lowerBound) {
            if(isset($this->brand)) {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where brand LIKE ? AND zip IN (' . $inParams . ') ORDER BY start_date, start_time ASC');
                $qry->bindValue(1, $this->brand);
                $index++;
                $this->debug .= " 1 ";
            } else if(isset($this->campaignId)) {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where campaign_id LIKE ? ORDER BY start_date, start_time ASC');
                $qry->bindValue(1, $this->campaignid);
                $index++;
                $this->debug .= " 11 ";
            } else {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where zip IN ('.$inParams.') ORDER BY start_date, start_time ASC' );
                $this->debug .= " 2 ";
            }
        } else {
            if(isset($this->brand)) {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where brand LIKE ? AND zip IN ('.$inParams.') AND month(start_date) < 10 ORDER BY start_date, start_time ASC' );
                $qry->bindValue(1, $this->brand);
                $index++;
                $this->debug .= " 3 ";
            } else if(isset($this->campaignId)) {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where campaign_id LIKE ? ORDER BY start_date, start_time ASC');
                $qry->bindValue(1, $this->campaignid);
                $index++;
                $this->debug .= $this->campaignid;
            } else {
                $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where zip IN ('.$inParams.') AND month(start_date) < 10 ORDER BY start_date, start_time ASC' );
                $this->debug .= " 4 ";
            }
        }

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1+$index), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($this->debug);
    }
}