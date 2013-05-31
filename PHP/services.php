<?php

?><?php

    error_reporting(-1);

    $serviceVersion = "dev";
    $servicePath = "/srv/lib/CORServices/" . $serviceVersion . "/";
    
    $services = array("service_account","service_class", "service_classPlan", "service_chat", "service_practice");


    // Verify that we have a service call that is valid

    $validService = isset($_POST['service']) ? true : die("Service Error: No Service Specified");
    $validService = isset($_POST['call']) ? true : die("Service Error: No Function Specified");
    

    // Verify that we have a valid service 
    $validService = in_array("service_".$_POST['service'], $services) ? true : die("Service Error: Service DNE");
    
    
    
    // Verify hash within each service call? 
    
    
    
    
    if($validService){
            
        include_once($servicePath . "service_".$_POST['service'].".php");
    
        
        
        call_user_func("service_{$_POST['service']}::{$_POST['call']}");
       
    }
?>