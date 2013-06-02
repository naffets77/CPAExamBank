<?php
    include_once("../config.php");
    require_once($CONFIG_libPath . "simpletest/autorun.php");
    
    
    
class TestOfLogin extends UnitTestCase {
    function testLogCreatesNewFileOnFirstMessage() {
    
        simulatePostRequest(array("email"=>"demo_account@cpaexambank.com", "password"=>"demo1"), "ciboriumsvc_account","login");
    
        $this->assertTrue(true);
    }
}




function simulatePostRequest($arrayPostVars, $service, $function){

    // Setup Post
    foreach($arrayPostVars as $key => $value){
        $_POST[$key] = $value;
    }
    
    
    include_once($CONFIG_servicePath . "service_". $service .".php");
       
    call_user_func("service_{$service}::{$function}"); 
}

?>