<?php
    include_once("../config.php");
    require_once($CONFIG_libPath . "simpletest/autorun.php");
    
    
    
class TestOfLogin extends UnitTestCase {
    function testLoging() {
    
        BuildTestHeader("Login", "service_account", "login", "Test logging in with a username/password", null, null);
        
        $result = simulatePostRequest(array("email"=>"demo_account@cpaexambank.com", "password"=>"e368b9938746fa090d6afd3628355133"), "service_account","login");
    
        BuildResultViewer($result);
        
        $this->assertTrue(true);
    }
    
    function testLogout() {
    
        
        BuildTestHeader("Logout", "service_account", "logout", "Test logging out", null, null);
        
        $result = simulatePostRequest(null, "service_account","logout");
    
        BuildResultViewer($result);
        
        // Check that there isn't a valid log
            
        $result = simulatePostRequest(null, "service_account","checkValidLogin");
        
        $this->assertFalse($result);
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

    $varDumpResult =  var_export($result, true);

    echo "<div class='result-viewer'>
            <div class='result-viewer-toggle' toggleattr='off' style='cursor:pointer;'>+ Result</div>
            <pre style='display:none;'>" . $varDumpResult  . "</pre>
          </div>";
}

function simulatePostRequest($arrayPostVars, $service, $function){

    global $CONFIG_servicePath;

    if($arrayPostVars != null){
        // Setup Post
        foreach($arrayPostVars as $key => $value){
            $_POST[$key] = $value;
        }
    }
    
    include_once($CONFIG_servicePath . $service .".php");
       
    return call_user_func("{$service}::{$function}"); 
}

?>



<script src="/Scripts/jquery-2.0.2.min.js"></script>
<script>
    $(document).on("ready", function(){

        $(".result-viewer-toggle").on("click", function(){
            var toggleState = $(this).attr("toggleattr");
            
            if(toggleState == "off"){
                $(this).parent().children("pre").show();
                $(this).html("- Hide Result").attr("toggleattr","on");
            }
            else{
                $(this).parent().children("pre").hide();
                $(this).html("+ Result").attr("toggleattr","off");            
            
            }
         
        });


    });
</script>