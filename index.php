<?php 
//error_reporting(E_ALL);
//ini_set('display_errors', 'On');

    require_once 'Helpers/Loader.php';
    // Requests from the same server don't have a HTTP_ORIGIN header
    if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
        $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
    }

    try {
        if (substr($_SERVER['HTTP_ORIGIN'], 0, 7) == 'http://') {
            $domain = substr($_SERVER['HTTP_ORIGIN'], 7);
        } else if (substr($_SERVER['HTTP_ORIGIN'], 0, 8) == 'https://') {
            $domain = substr($_SERVER['HTTP_ORIGIN'], 8);
        } else {
            $domain = $_SERVER['HTTP_ORIGIN'];
        }
        
        $loader = new Loader($_REQUEST['request'], $domain);
        $controller = $loader->createController();
        echo $controller->executeAction();
        //$API = new Endpoints($_REQUEST['request'], $domain);
       // echo $API->executeAction();
    } catch (Exception $e) {
        echo json_encode(Array('error' => $e->getMessage()));
    }
?>

