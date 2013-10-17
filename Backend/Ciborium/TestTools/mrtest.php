<?php

include_once("/srv/lib/TPrepServices/dev/config.php");
include_once("/srv/lib/TPrepLib/dev/config.php");
//Master library includes
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/account.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
require_once("/srv/lib/stripe-php/Stripe.php");

//Ciborium library includes
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_stripe.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciboriumlib_account.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_question.php");

//Ciborium service includes
include_once(ciborium_configuration::$environment_librarypath."/question.php");
include_once(service_configuration::$ciborium_servicepath."/service_account.php");
include_once(service_configuration::$ciborium_servicepath."/service_stripe.php");

//------------------------------------\\
//------------ Testing Mods ----------\\
//------------------------------------\\

echo "<h2>Checking if paths look correct</h2>";
echo "Library Path: ".ciborium_configuration::$environment_librarypath."<br />";
echo "dirname(__FILE__): ".dirname(__FILE__)."<br/>";
echo "realpath( dirname(../__FILE__) ): ".realpath(dirname("../__FILE__"))."<br/><br />";



echo "<h2>Creating New User</h2>";
$myEmail = "mrtest_createnewuser-".time()."@cpaexambank.com";
$myPassword = "381944f65151a4804c7c2b6e0d39aad4";
$mySectionsArray = array('FAR' => "0", 'AUD' => "1", 'BEC' => "1", 'REG' => "0");
$myResult = ciboriumlib_account::registerNewUser($myEmail, $myPassword, $mySectionsArray);
echo"<pre>";
print_r($myResult);
echo"</pre>";
echo "<h2>Creating New Stripe Customer</h2>";
//Stripe::setApiKey("sk_test_mkGsLqEW6SLnZa487HYfJVLf");

$tokenInputArray = array(
    'card' => array(
        'name' => "Prep TokenUnitTest",
        'number' => "4242424242424242",
        'exp_month' => 11,
        'exp_year' => 2031,
        'cvc' => "317"
    )
);
try{
    $myTokenCreationResponse = stripe_charger::createToken($tokenInputArray);
    $tokenArray = util_general::getProtectedValue($myTokenCreationResponse['Token'], "_values");
    $myStripeCustomerCreationResult = ciborium_stripe::createNewSubscriber($myResult['Licenses']->LicenseId, $myResult['Account']->LoginName, $tokenArray['id'], __METHOD__);
    if($myStripeCustomerCreationResult['Result']){

        $myCustomerRetrievalResult = stripe_charger::retrieveCustomer($myStripeCustomerCreationResult['CustomerId']);
        if($myCustomerRetrievalResult['Result']){
            echo "<h3>Charging the new customer</h3>";
            $customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($myCustomerRetrievalResult['Customer']);
            $moduleArray = array(
                'AUD' => 1,
                'FAR' => 0,
                'BEC' => 0,
                'REG' => 0
            );

            $chargeResult = ciborium_stripe::chargeSubscription($customerArray['id'], $moduleArray, $myResult['Licenses']->SubscriptionTypeId, $myResult['Licenses']->LicenseId, $myResult['Account']->AccountUserId, "mrtest.php");

            if($chargeResult['Result']){
                echo "Success in charging customer. Message: ".$chargeResult['Reason']."<br />";
                echo "Printing out final customer object...<br/>";
                echo"<pre>";
                print_r(stripe_charger::retrieveCustomer($myStripeCustomerCreationResult['CustomerId']));
                echo"</pre>";
            }
            else{
                echo "Failed charging customer. Message: ".$chargeResult['Reason']."<br />";
            }
        }
        else{
            echo "Failed retrieving customer. Message: ".$myCustomerRetrievalResult['Reason']."<br />";
        }

    }
    else{
        echo "Failed creating customer. Message: ".$myStripeCustomerCreationResult['Reason']."<br />";
    }

    // echo "<h3>Deleting user...</h3>";
    // $parametersArray = array($myResult['Account']->AccountUserId);
    // database::callStoredProcedure("sp_DELETEAccountUserById", $parametersArray, "mrtest.php");
}
catch(Exception $ex){
    echo "Exception message: ".$ex->getMessage();
    echo "<h3>Deleting user after exception...</h3>";
    $parametersArray = array($myResult['Account']->AccountUserId);
    database::callStoredProcedure("sp_DELETEAccountUserById", $parametersArray, "mrtest.php");
}


echo "<h2>Copy Q and A</h2>";
echo "<pre>";
//print_r(database::callStoredProcedure("sp_CopyQuestionAndAnswersById", array(1), "mrtest.php"));
//print_r(question::copyQuestionAndAnswersById(1));
$myNewQR = ciborium_question::copyQuestionResponse(1, "mrtest.php");
print_r($myNewQR);
database::callStoredProcedure("sp_DELETEQuestionById", array($myNewQR['QuestionResponse'][0]->QuestionId), "mrtest.php");
echo "</pre>";

echo "<h2>Decoding sample Question Update JSON object</h2>";
$myJSON = "{
  \"QuestionId\": \"1\",
  \"CorrectAnswerIndex\": 0,
  \"QuestionClientId\": \"AUD1S1\",
  \"QuestionClientImage\": \"48\",
  \"IsApprovedForUse\": \"1\",
  \"IsDeprecated\": \"0\",
  \"IsActive\": \"1\",
  \"Explanation\": \"The CPA should make inquiries of the predecessor auditor when he is approached to perform an audit for the first time. This is required because the predecessor may provide the successor the relevant information which will assist the successor in determining\",
  \"Question\": \"<p>The  services which cannot be performed for a nonissuer test client are </p><p></p><p></p><p></p><p></p><p></p>\",
  \"Answers\": [
    {
      \"DisplayText\": \"Signing of Payroll Checks\",
      \"QuestionToAnswersId\": \"1\",
      \"IsAnswerToQuestion\": \"1\"
    },
    {
      \"DisplayText\": \"To Record transactions approved by management\",
      \"QuestionToAnswersId\": \"2\",
      \"IsAnswerToQuestion\": \"0\"
    },
    {
      \"DisplayText\": \"Performing data processing services\",
      \"QuestionToAnswersId\": \"3\",
      \"IsAnswerToQuestion\": \"0\"
    },
    {
      \"DisplayText\": \"Preparation of  Financial Statements\",
      \"QuestionToAnswersId\": \"4\",
      \"IsAnswerToQuestion\": \"0\"
    }
  ]
}";
$myJSONValid = validate::isValidJSONString($myJSON) ? "true" : "false";
echo "Is JSON valid: ".$myJSONValid;
echo "<pre>";
print_r(json_decode($myJSON, true));
echo "</pre>";

echo "<h2>Testing update with above JSON object</h2>";
echo "<pre>";
print_r(ciborium_question::updateQuestionAndAnswers($myJSON, "mrtest.php"));
echo "</pre>";

echo "<h2>Trying out ciborium_question::getAccountUserQuestionHistoryById(3)</h2>";
echo"<pre>";
print_r(ciborium_question::getAccountUserQuestionHistoryById(3, "mrtest.php"));
//print_r(database::callStoredProcedure("sp_getAccountUserQuestionHistoryById", array(3), "mrtest.php"));
echo"</pre>";

echo "<h2>Trying out ciborium_question::getAccountUserQuestionHistoryById(3, array(5, 4, 2), \"testing\")</h2>";
echo"<pre>";
print_r(ciborium_question::getAccountUserQuestionHistoryWithFiltersById(3, array('SectionTypeId' => 5, 'ResultId' => 4, 'OrderById' => 2), "mrtest.php"));
//print_r(database::callStoredProcedure("sp_getAccountUserQuestionHistoryById", array(3), "mrtest.php"));
echo"</pre>";

/*
echo "<h2>Testing Getting QAs from Question history</h2>";
echo "<pre>";
$parametersArray = array(3, "\'1, 3, 5\'");
print_r(database::callStoredProcedure("sp_getQAndAsViaQuestionHistory", $parametersArray, "mrtest.php"));
echo "</pre>";
*/

echo "<h2>Logging in as demo_account3@cpaexambank.com...</h2>";
$myAccount = ciboriumlib_account::login("demo_account3@cpaexambank.com", "ae2b1fca515949e5d54fb22b8ed95575");
$myAccountIsSet = isset($_SESSION['Account']) ? "true" : "false";
echo "Account is set: ".$myAccountIsSet;

echo "<h3>Printing session</h3>";
echo"<pre>";
print_r($_SESSION['Account']);
print_r($_SESSION['AccountSettings']);
print_r($_SESSION['AccountLicense']);
print_r($_SESSION['AccountHash']);
print_r($_SESSION['Timeout']);
print_r($_SESSION['LastRefreshed']);
echo"</pre>";

echo "<h3>Printing UI account return object</h3>";
echo"<pre>";
print_r($myAccount);
echo"</pre>";


//Testing out getting column metadata from MySql db
echo "<h2>Getting column metadata from MySQL db AccountUser</h2>";
$db = new database();
$query = "SELECT * FROM AccountUser WHERE AccountUserId in (1,2);";
//$sth= $db->dbc->prepare($query);
$sth= $db->dbc->query($query);

//check column metadata
$myColumnMetaDataArray = array();
$total_column = $sth->columnCount();
for ($counter = 0; $counter <= $total_column; $counter ++) {
    $meta = $sth->getColumnMeta($counter);
    array_push($myColumnMetaDataArray, $meta);
}
$result = $sth->fetchAll(PDO::FETCH_OBJ);

//make util function (in database.php) to get all column metadata

foreach($result as $key => $value){

    $result[$key]->IsEnabled = ord($result[$key]->IsEnabled);
}
echo "<h3>Printing column meta data</h3>";
echo"<pre>";
print_r($myColumnMetaDataArray);
echo"</pre>";

echo "<h3>Printing Select query result</h3>";
echo"<pre>";
print_r($result);
echo"</pre>";
$db->dbc = null;

?>