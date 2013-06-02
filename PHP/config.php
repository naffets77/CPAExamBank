<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    
    
    $GLOBAL_database = "pubty_dev";
    $GLOBAL_email_enabled = false;
    
    $mysqli = new mysqli("198.211.105.160","root","!Naffets77", $GLOBAL_database);  

    $libPath = "/srv/lib/";

    
    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') '
                . $mysqli->connect_error);
    }
    

?>