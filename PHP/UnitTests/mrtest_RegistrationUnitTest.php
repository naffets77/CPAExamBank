<?php
include_once("../config.php");
include_once("/srv/lib/TPrepServices/".$serviceVersion."/config.php");
include_once(service_configuration::$ciborium_servicepath."/service_account.php");
include_once(service_configuration::$ciborium_servicepath."/service_stripe.php");
include_once(service_configuration::$ciborium_librarypath."/ciboriumlib_account.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_stripe.php");
include_once(service_configuration::$environment_librarypath."/account.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(service_configuration::$environment_librarypath."/validate.php");
include_once(service_configuration::$environment_librarypath."/database.php");
include_once(service_configuration::$environment_librarypath."/stripe_charger.php");
require_once("/srv/lib/stripe-php/Stripe.php");

echo "<h1>Registration Unit Test</h1>";

/*$mySectionsArray = array('FAR' => "0", 'AUD' => "1", 'BEC' => "1", 'REG' => "0");
$returnArray = ciboriumlib_account::registerNewUser("marcusrico32@gmail.com", "ae2b1fca515949e5d54fb22b8ed95575", $mySectionsArray, "1", "CPA75OFFBETA", "unit_test->registerNewUser");
$result = $returnArray['Result'] ? "pass" : "fail";
echo "Result of creating user is: ".$result."<br/>";
echo "Message is: ".$returnArray['Reason']."<br/><br/>";

//Login
ciboriumlib_account::login("marcusrico32@gmail.com", "ae2b1fca515949e5d54fb22b8ed95575");

//charge a subscription
$moduleArray = array('AUD'=>'0', 'BEC'=>'1', 'FAR'=>'1', 'REG'=>'0');
$myStripeCharge = ciborium_stripe::chargeSubscription($_SESSION['Licenses']->StripeCustomerId, $moduleArray, $_SESSION['Licenses']->SubscriptionTypeId, $_SESSION['Licenses']->LicenseId, $_SESSION['Licenses']->AccountUserId, "unit_test->chargeSubscription", "CPA75OFFBETA");*/

/*echo "Printing token object...<br/>";
echo "<pre>";
print_r($returnArray['Token']);
echo "</pre>";*/

/*$promoCodeResultArray = ciborium_promotion::validatePromotionCodeForUser("CPA75OFFBETA", 233, "tester");
$result = $promoCodeResultArray['Result'] ? "pass" : "fail";
echo "Result of creating user is: ".$result."<br/>";
echo "Message is: ".$promoCodeResultArray['Reason']."<br/><br/>";
echo "PromotionId is: ".$promoCodeResultArray['PromotionId']."<br/><br/>";

echo "Printing promotion...<br/>";
echo "<pre>";
$promotion = ciborium_promotion::getPromotionById(5);
print_r($promotion);
echo "</pre>";

echo "Printing AccountusertoPromotion...<br/>";
echo "<pre>";
$accountUserToPromotion = ciborium_promotion::getAccountUserToPromotion(5, 233, "tester")[0];
print_r($accountUserToPromotion);
echo "</pre>";*/


echo "Printing PromotionArray...<br/>";
echo "<pre>";
$promotionArray = ciboriumlib_account::returnPromotionsForUI(ciborium_promotion::getPromotionArrayById(5));
print_r($promotionArray);
echo "</pre>";
echo "<br/><br/>";

echo "Printing PromotionArray as JSON object...<br/>";
echo "<pre>";
$promotionArray = ciboriumlib_account::returnPromotionsForUI(ciborium_promotion::getPromotionArrayById(5));
print_r(array('PromotionCodes' => $promotionArray));
echo "</pre>";
echo "<br/><br/>";
echo "<h3>JSON object</h3>";
echo json_encode(array('PromotionCodes' => $promotionArray));

?>