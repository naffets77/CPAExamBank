<?php

    include_once("config.php");
    
    $services = array("service_account","service_question");


    // Verify that we have a service call that is valid

    $validService = isset($_POST['service']) ? true : die("Service Error: No Service Specified");
    $validService = isset($_POST['call']) ? true : die("Service Error: No Function Specified");
    

    // Verify that we have a valid service 
    $validService = in_array("service_".$_POST['service'], $services) ? true : die("Service Error: Service DNE");
    
    
    
    // Verify hash within each service call? 
    
    
    
    
    if($validService){
            
        include_once($CONFIG_servicePath . "service_".$_POST['service'].".php");
    
        $return = call_user_func("service_{$_POST['service']}::{$_POST['call']}");
        echo json_encode($return);
    }
?>