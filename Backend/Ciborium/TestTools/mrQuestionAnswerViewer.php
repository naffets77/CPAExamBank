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

$questionId = 508;
$whereClause = "QuestionId = ".$questionId;
$myQuestionObjects = database::select("Question", null, $whereClause, "", "", null, "mrtest");
$myAnswerObjects = database::select("QuestionToAnswers", null, $whereClause, "", "", null, "mrtest");

echo "<h2>Question</h2>";
echo $myQuestionObjects[0]->DisplayText;

echo "<h2>Explanation</h2>";
echo $myQuestionObjects[0]->Explanation;

echo "<h2>Answers</h2>";
if($myAnswerObjects != null){
    foreach($myAnswerObjects as $key => $value){
        echo $value->DisplayText."<br />";
    }
}
else{
    echo "Answers object was null.<br />";
}

echo "<h2>JSON Data</h2>";
$QResponsesArray = ciborium_question::buildQuestionsAndAnswersArray($myQuestionObjects);
echo json_encode($QResponsesArray);

?>