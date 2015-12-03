<?php

//Should use data mapper or data repository pattern in the models, but I 
//just want to get this up and running, not too worried about theory and agonizing
//over proper design patterns.

class APIKey {
    protected $_connection;
    protected $_db;
    private $queryCache = array();
    
    public function __construct($pdo)
    {
        $this->_connection = $pdo;
        $this->_db = $this->_connection->getDb();
    }

    public function verifyKey($key, $origin)
    {
        $qry = $this->_db->prepare('
        SELECT k.*, d.* FROM api_keys as k INNER JOIN domains as d on 
        k.id = d.key_id WHERE k.key = :key AND domain = :domain
        ');
        $qry->bindParam(':key', $key);
        $qry->bindParam(':domain', $origin);
        $qry->execute();
        $results = $qry->fetch();

        return (!empty($results));
    }
    
    //Caching
    public function find($id)
    {
        if (!isset($this->userCache[$id])) {
            $userCache[$id] = $this->dao->query('
                SELECT * FROM users WHERE id = ' . (int)$id . '
            ');
        }
        return $userCache[$id];
    }
    //End caching
}
?>