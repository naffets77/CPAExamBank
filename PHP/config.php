<?php

    


    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    
    $GLOBAL_email_enabled = false;
    
    $serviceVersion = "dev";    
    $GLOBAL_database = "ciborium_" . $serviceVersion;
    
    $CONFIG_libPath = "/srv/lib/";
    $CONFIG_servicePath = "/srv/lib/CORServices/" . $serviceVersion . "/";

    
    
    
    $mysqli = new mysqli("198.211.105.160","root","!Naffets77", $GLOBAL_database);  

    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') '
                . $mysqli->connect_error);
    }
    

?>