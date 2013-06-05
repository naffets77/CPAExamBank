<?php
    include_once("../config.php");
    require_once($CONFIG_libPath . "simpletest/autorun.php");
    
    
    
class TestOfLogin extends UnitTestCase {
    function testLogCreatesNewFileOnFirstMessage() {
    
        BuildTestHeader("Login", "service_account", "login", "Test logging in with a username/password", null, null);
        
        $result = simulatePostRequest(array("email"=>"demo_account@cpaexambank.com", "password"=>"demo1"), "service_account","login");
    
        BuildResultViewer($result);
        
        $this->assertTrue(true);
    }
}


function BuildTestHeader($name,$service, $function, $testingDescription, $inputs, $output){

    echo "<h2>$name</h2>
          <div class='test-info'>
            <table>
                <tr>
                    <td>Service</td>
                    <td>$service</td>
                </tr>
                <tr>
                    <td>Function</td>
                    <td>$function</td>
                </tr>
                <tr>
                    <td colspan='2'>
                        $testingDescription
                    </td>
                </tr>
            </table>";

}

function BuildResultViewer($result){

    var_dump($result);

}

function simulatePostRequest($arrayPostVars, $service, $function){

    global $CONFIG_servicePath;


    // Setup Post
    foreach($arrayPostVars as $key => $value){
        $_POST[$key] = $value;
    }
    
    
    include_once($CONFIG_servicePath . $service .".php");
       
    return call_user_func("{$service}::{$function}"); 
}

?>