<?php
    session_start();
    include_once("../config.php");
    require_once($CONFIG_libPath . "simpletest/autorun.php");
    
    
    
class TestAccount extends UnitTestCase {

    public $hash;

    function testLogin() {
    
        BuildTestHeader("Login", "service_account", "login", "Test logging in with a username/password", null, null);
        
        $result = simulatePostRequest(array("email"=>"demo_account@cpaexambank.com", "password"=>"e368b9938746fa090d6afd3628355133"), "service_account","login");
    
        $this->hash = $result['Hash'];
    
        BuildResultViewer($result, "service_account :: login");
                
        $this->assertTrue(true);
    }
    
    function testGetQuestions(){

        BuildTestHeader("Get Questions", "service_question", "getAllQuestionsAndAnswers", "Test getting questions", null, null);

        $result = simulatePostRequest(array("Hash"=> $this->hash), "service_question","getAllQuestionsAndAnswers");
    
        BuildResultViewer($result, "service_question :: getAllQuestionsAndAnswersForUI");
                
        $this->assertTrue(true);

    
    }
    
    function testFilteredQuestions(){

        BuildTestHeader("Get Questions", "service_question", "getQuestionsAndAnswers", "Test getting questions", null, null);

        $result = simulatePostRequest(array("Hash"=> $this->hash, "SectionTypeId" => 1, "QuestionAmount" => 5), "service_question","getQuestionsAndAnswers");
        
        BuildResultViewer($result, "service_question :: getQuestionsAndAnswers");
        
        $this->assertTrue(true);

        
    }
    
    function testGetUserQuestionHistory(){

        BuildTestHeader("Get Question History", "service_question", "getAccountUserQuestionHistory", "Test getting question History", null, null);

        $result = simulatePostRequest(array("Hash"=> $this->hash, "AccountUserId" => 3, "QuestionAmount" => 20, "SectionTypeId" => 1), "service_question","getAccountUserQuestionHistory");
        
        BuildResultViewer($result, "service_question :: getAccountUserQuestionHistory");
        
        $this->assertTrue(true);

        
    }
    
    function testCopyQuestion(){

        BuildTestHeader("Copy Question", "service_question", "copyQuestion", "Test coppying a question", null, null);

        $result = simulatePostRequest(array("Hash"=> $this->hash, "QuestionId" => 1), "service_question","copyQuestion");
        
        BuildResultViewer($result, "service_question :: copyQuestion");
        
        $this->assertTrue(true);

        
    }
    

    
    function testLogout(){
        BuildTestHeader("Logout", "service_account", "logout", "Test logging out", null, null);
                
        $result = simulatePostRequest(null, "service_account","logout");
    
        BuildResultViewer($result,"service_account :: logout");
 
        // Check that there isn't a valid log
            
        $result = simulatePostRequest(null, "service_account","refreshLogin");
        BuildResultViewer($result,"service_account :: refreshLogin");
        
        $this->assertNull($result['Account']);
    }

    
}
 




function BuildTestHeader($name,$service, $function, $testingDescription, $inputs, $output){

    echo "<h2 style='font-size:14px;'>$name</h2>
          <div class='test-info'>
            <table style='font-size:12px; margin-left:10px;'>
                <tr>
                    <td>Service</td>
                    <td style='color:#333;'>$service</td>
                </tr>
                <tr>
                    <td>Function</td>
                    <td style='color:#333;'>$function</td>
                </tr>
                <tr>
                    <td>Description</td>
                    <td style='color:#333;'>
                        $testingDescription
                    </td>
                </tr>
            </table>";

}

function BuildResultViewer($result, $name){

    $varDumpResult =  var_export($result, true);

    echo "<div class='result-viewer'>
            <div class='result-viewer-toggle' toggleattr='off' style='cursor:pointer;'>+ Result ($name)</div>
            <pre style='display:none; padding:2px; background-color:#efefef;'>" . $varDumpResult  . "</pre>
          </div>";
}

function simulatePostRequest($arrayPostVars, $service, $function){

    global $CONFIG_servicePath;

    $_POST = array();

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