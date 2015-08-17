<?php
/**
 * PDO Connector class
 * Modifies the DSN to parse username and password individually.
 * Optionally, gets the DSN directly from php.ini.
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.1
 */
class Cxn {
    protected $db;
     
    public function __construct( $dbh ) {
        $this->connect( $dbh );
    }
     
    public function getDb() {   
        if ( is_object( $this->db ) ) {
            return $this->db;
        } else {
            die('There was a database problem');
        }
    }
     
    public function __destruct() {
        $this->db = null;
    }
 
    private function connect( $dbh ) {
        if ( !empty( $this->db ) && is_object( $this->db ) ) {
            return;
        }
         
        try {
            $settings = array(
                PDO::ATTR_TIMEOUT       => "5",
                //PDO::ATTR_EMULATE_PERPARES    => false,
                PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT        => false
            );
             
            $this->_dsn( $dbh, $username, $password );
            $this->db = new PDO( $dbh, $username, $password, $settings );
        } catch ( PDOException $e ) {
            exit( $e->getMessage() );
        }
    }
     
    /**
     * Extract the username and password from the DSN and rebuild
     */
    private function _dsn( &$dsn, &$username = '', &$password = '' ) {
         
        /**
         * No host name with ':' would mean this is a DSN name in php.ini
         */
        if ( false === strrpos( $dsn, ':' ) ) {
             
            /**
             * We need get_cfg_var() here because ini_get doesn't work
             * https://bugs.php.net/bug.php?id=54276
             */
            $dsn = get_cfg_var( "php.dsn.$dsn" );
        }
         
        /**
         * Some people use spaces to separate parameters in
         * DSN strings and this is NOT standard
         */
        $d = explode( ';', $dsn );
        $m = count( $d );
        $s = '';
         
        for( $i = 0; $i < $m; $i++ ) {
            $n = explode( '=', $d[$i] );
 
            // Empty parameter? Continue
            if ( count( $n ) <= 1 ) {
                $s .= implode( '', $n ) . ';';
                continue;
            }
             
            switch( trim( $n[0] ) ) {
                case 'uid':
                case 'user':
                case 'username':
                    $username = trim( $n[1] );
                    break;
                 
                case 'pwd':
                case 'pass':
                case 'password':
                    $password = trim( $n[1] );
                    break;
                 
                default: // Some other parameter? Leave as-is
                    $s .= implode( '=', $n ) . ';';
            }
        }
        $dsn = $s;
    }
}

