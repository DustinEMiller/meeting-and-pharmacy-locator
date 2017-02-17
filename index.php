<?php
    //Dev settings
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    
    require_once 'Helpers/Loader.php';
    // Requests from the same server don't have a HTTP_ORIGIN header
    if (!array_key_exists('HTTP_REFERER', $_SERVER)) {
        $_SERVER['HTTP_REFERER'] = $_SERVER['SERVER_NAME'];
    }

    try {
        if (substr($_SERVER['HTTP_REFERER'], 0, 7) == 'http://') {
            $domain = substr($_SERVER['HTTP_REFERER'], 7);
        } else if (substr($_SERVER['HTTP_REFERER'], 0, 8) == 'https://') {
            $domain = substr($_SERVER['HTTP_REFERER'], 8);
        } else {
            $domain = $_SERVER['HTTP_REFERER'];
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

