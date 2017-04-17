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

    public function __construct($pdo, $zipCodes)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();

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
        $qry = $this->_db->prepare('SELECT event_name, start_time, end_time, venue_name, room_name, address, city, state, zip 
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
            $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where zip IN ('.$inParams.') ORDER BY start_date, start_time ASC' );
        } else {
            $qry = $this->_db->prepare('SELECT location, campaign_name, address, address2, city, zip, start_date, 
                start_time, state, campaign_id, presentation_language FROM askshirley.seminars where zip IN ('.$inParams.') AND month(start_date) < 10 ORDER BY start_date, start_time ASC' );    
        }

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }
}