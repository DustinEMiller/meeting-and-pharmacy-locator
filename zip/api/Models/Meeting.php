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
class Meeting {
    
    protected $_connection;
    protected $_db;
    private $queryCache = array();
    private $zipCodes = array();
    private $meetingType = array();

    public function __construct($pdo, $locationSettings, $zipCodes)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();
        $this->meetingType = $locationSettings;

        foreach ($zipCodes['zip_codes'] as $v) {
            $this->zipCodes[] = $v['zip_code'];
        } 
    }

    public function results()
    {
        switch ($this->meetingType[1]) {
            case 'event':
                return $this->event();
                break;
            case 'seminar':
                return $this->seminar();
                break;
            default:
                throw new Exception('Bad location sub type used.');
        }

    }

    //Add 'order by' that is linked fto nearest to furthest zip code
    private function event()
    {
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

    private function seminar()
    {
        $inParams = implode(',', array_fill(0, count($this->zipCodes), '?'));
        $qry = $this->_db->prepare('SELECT location, address, address2, city, zip, start_date, 
            time, state, campaign_id FROM askshirley.seminars where zip IN ('.$inParams.') ORDER BY start_date, time ASC' );

        foreach ($this->zipCodes as $k => $zipCode) {
            $qry->bindValue(($k+1), $zipCode);
        }

        $qry->execute();
        $result['results'] = $qry->fetchAll();
        return($result);     
    }
}