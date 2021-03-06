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
class Geolocation 
{
    
    protected $_connection;
    protected $_db;
    private $queryCache = array();
    private $args = array();
    private $states = array(
        'Alabama'=>'AL',
        'Alaska'=>'AK',
        'Arizona'=>'AZ',
        'Arkansas'=>'AR',
        'California'=>'CA',
        'Colorado'=>'CO',
        'Connecticut'=>'CT',
        'Delaware'=>'DE',
        'Florida'=>'FL',
        'Georgia'=>'GA',
        'Hawaii'=>'HI',
        'Idaho'=>'ID',
        'Illinois'=>'IL',
        'Indiana'=>'IN',
        'Iowa'=>'IA',
        'Kansas'=>'KS',
        'Kentucky'=>'KY',
        'Louisiana'=>'LA',
        'Maine'=>'ME',
        'Maryland'=>'MD',
        'Massachusetts'=>'MA',
        'Michigan'=>'MI',
        'Minnesota'=>'MN',
        'Mississippi'=>'MS',
        'Missouri'=>'MO',
        'Montana'=>'MT',
        'Nebraska'=>'NE',
        'Nevada'=>'NV',
        'New Hampshire'=>'NH',
        'New Jersey'=>'NJ',
        'New Mexico'=>'NM',
        'New York'=>'NY',
        'North Carolina'=>'NC',
        'North Dakota'=>'ND',
        'Ohio'=>'OH',
        'Oklahoma'=>'OK',
        'Oregon'=>'OR',
        'Pennsylvania'=>'PA',
        'Rhode Island'=>'RI',
        'South Carolina'=>'SC',
        'South Dakota'=>'SD',
        'Tennessee'=>'TN',
        'Texas'=>'TX',
        'Utah'=>'UT',
        'Vermont'=>'VT',
        'Virginia'=>'VA',
        'Washington'=>'WA',
        'West Virginia'=>'WV',
        'Wisconsin'=>'WI',
        'Wyoming'=>'WY'
    );
    
    public function __construct($pdo, $args)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();
        $this->args = $args;
    }
    
    public function radius()
    {
        $qry = $this->_db->prepare('SELECT latitude, longitude FROM city_zips WHERE zip_code = :zip');
        $qry->bindParam(':zip', $this->args[0]);
        $qry->execute();
        $row = $qry->fetch();
        
        if(empty($row)) {
            throw new Exception('Please try a different zip code.');
        }
        
        $latitude = 0;
        $longitude = 0;
        
        do {
            $latitude += $row['latitude'];
            $longitude += $row['longitude'];

        } while ($row = $qry->fetch());

        $latitude = ($latitude/$qry->rowCount());
        $longitude = ($longitude/$qry->rowCount());
        
        $qry = $this->_db
            ->prepare('SELECT zip_code, ROUND(( 3959 * acos( cos( radians(:lat) ) * 
            cos( radians(latitude) ) * cos( radians(longitude) - radians(:lng) ) + 
            sin( radians(:lat) ) * sin(radians(latitude)) ) ), 3) AS distance, city, state 
            FROM askshirley.city_zips
            HAVING distance <= :radius 
            ORDER BY distance');

        $qry->bindParam(':lat', $latitude);
        $qry->bindParam(':lng', $longitude);
        $qry->bindParam(':radius', $this->args[1]);
        $qry->execute();
        
        $result['zip_codes'] = $qry->fetchAll();

        return($result);
    }
    
    public function geocode()
    {
        $qry = $this->_db
            ->prepare('SELECT zip_code, ROUND(( 3959 * acos( cos( radians(:lat) ) * 
            cos( radians(latitude) ) * cos( radians(longitude) - radians(:lng) ) + 
            sin( radians(:lat) ) * sin(radians(latitude)) ) ), 3) AS distance, city, state 
            FROM askshirley.city_zips
            ORDER BY distance
            LIMIT 1');

        $qry->bindParam(':lat', $this->args[0]);
        $qry->bindParam(':lng', $this->args[1]);
        $qry->execute();
        $result['zip_codes'] = $qry->fetchAll();
        
        return($result);
    }
    
    public function cityzips()
    {
        $qry = $this->_db->prepare('SELECT latitude, longitude, zip_code FROM city_zips WHERE city like :city AND state like :state');
        $qry->bindParam(':city', $this->args[0]);
        
        if(strlen($this->args[1]) > 2){
            $this->args[1] = $this->states[ucwords($this->args[1])];
        }
        
        $qry->bindParam(':state', $this->args[1]);
        $qry->execute();
        $row = $qry->fetch();
        
        if(empty($row)) {
            throw new Exception('Incorrect location combination.');
        }
        
        $latitude = 0;
        $longitude = 0;
        
        do {
            $latitude += $row['latitude'];
            $longitude += $row['longitude'];

        } while ($row = $qry->fetch());

        $latitude = ($latitude/$qry->rowCount());
        $longitude = ($longitude/$qry->rowCount());
        
        $qry = $this->_db->prepare('SELECT zip_code, ROUND(( 3959 * acos( cos( radians(:lat) ) * 
            cos( radians(latitude) ) * cos( radians(longitude) - radians(:lng) ) + 
            sin( radians(:lat) ) * sin(radians(latitude)) ) ), 3) AS distance, city, state 
            FROM askshirley.city_zips
            HAVING distance <= :radius 
            ORDER BY distance');

        $qry->bindParam(':lat', $latitude);
        $qry->bindParam(':lng', $longitude);
        $qry->bindParam(':radius', $this->args[2]);
        
        $qry->execute();
        $result['zip_codes'] = $qry->fetchAll();

        return($result);
    }

    public function getCounty($zip) 
    {
        $qry = $this->_db->prepare('SELECT county_fips.county FROM county_fips LEFT JOIN zip_county_fips ON county_fips.county_fips = zip_county_fips.county_fips WHERE zip_county_fips.zip = :zip LIMIT 1');
        $qry->bindParam(':zip', $zip);

        $qry->execute();
        $county = $qry->fetchAll();

        return($county);
    }
}
