<?php
include_once("/PHP/config.php");
include_once("/srv/lib/TPrepServices/prod/config.php");
include_once("/srv/lib/TPrepLib/prod/config.php");
//Master library includes
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/account.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/stripe_charger.php");
include_once(ciborium_configuration::$environment_librarypath."/question.php");
require_once("/srv/lib/stripe-php/Stripe.php");

//Ciborium library includes
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_stripe.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciboriumlib_account.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_question.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_email.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_general.php");

//Ciborium service includes

include_once(service_configuration::$ciborium_servicepath."/service_account.php");
include_once(service_configuration::$ciborium_servicepath."/service_stripe.php");

$questionId = 0;
if($_GET['qid'] != null && validate::tryParseInt($_GET['qid']) && (int)$_GET['qid'] > 0 ){
    $questionId = (int)$_GET['qid'];
}

if(validate::isNotNullOrEmpty_String($_GET['qcid'])){
    $myQuestionClientId = $_GET['qcid'];
    $selectArray = array("QuestionId");
    $prepareArray = array(":QuestionClientId" => $myQuestionClientId);
    $myQuestion = database::select("Question", $selectArray, "QuestionClientId = :QuestionClientId", "", "", $prepareArray, "mrtest");
    if(!empty($myQuestion)){
        $questionId = (int)$myQuestion[0]->QuestionId;
    }
}
$whereClause = "QuestionId = ".$questionId;
$myQuestionObjects = database::select("Question", null, $whereClause, "", "", null, "mrtest");

echo "<h2>Question (id ".$questionId.")</h2>";
if($myQuestionObjects){
    $myAnswerObjects = database::select("QuestionToAnswers", null, $whereClause, "", "", null, "mrtest");
    echo $myQuestionObjects[0]->DisplayText;

    echo "<h2>Explanation</h2>";
    echo $myQuestionObjects[0]->Explanation;

    echo "<h2>Answers</h2>";
    echo "<ul>";
    if(!empty($myAnswerObjects)){
        foreach($myAnswerObjects as $key => $value){
            if($value->IsAnswerToQuestion){
                echo "<li>".$value->DisplayText."</li>";
            }
            else{
                echo "<li style='list-style-type: circle;'>".$value->DisplayText."</li>";
            }
        }
    }
    else{
        echo "Answers object was empty.<br />";
    }
    echo "</ul>";

    echo "<h2>JSON Data</h2>";
    $QResponsesArray = ciborium_question::buildQuestionsAndAnswersArray($myQuestionObjects);
    echo "<code>";
    echo str_replace(array("\\/"), "/" , htmlspecialchars(json_encode($QResponsesArray, JSON_PRETTY_PRINT), ENT_NOQUOTES));
    echo "</code>";
}
else{
    echo "No question found.";
}


?>