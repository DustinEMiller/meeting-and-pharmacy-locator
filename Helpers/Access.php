<?php

//Should use data mapper or data repository pattern in the models
class Access {
    protected $_connection;
    protected $_db;
    private $queryCache = array();
    
    public function __construct($pdo)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();
    }

    public function verifyDomain($domain)
    {
        $qry = $this->_db->prepare('
            SELECT d.* FROM domains as d WHERE d.domain = :domain
        ');

        $qry->bindParam(':domain', $domain);
        $qry->execute();

        $results = $qry->fetch();

        return (!empty($results));
    }

    public function verifyKey($key, $origin)
    {
        $qry = $this->_db
            ->prepare('SELECT k.*, d.* FROM api_keys as k INNER JOIN 
                domains as d on k.id = d.key_id WHERE k.key = :key AND domain = :domain');

        $qry->bindParam(':key', $key);
        $qry->bindParam(':domain', $origin);
        $qry->execute();
        
        $results = $qry->fetch();

        return (!empty($results));
    }
}
?>
