<?php
include_once("/srv/lib/TPrepServices/dev/config.php");
include_once(service_configuration::$ciborium_servicepath."/service_account.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciboriumlib_account.php");
include_once(service_configuration::$ciborium_servicepath."/service_stripe.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_stripe.php");
include_once(ciborium_configuration::$environment_librarypath."/account.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/stripe_charger.php");
require_once("/srv/lib/stripe-php/Stripe.php");

echo "<h1>Stripe Unit Tests</h1>";
//which test to run?
$TestID = enum_StripeUnitTests::test_AddCreditCard;

//test one time charge on existing customer
switch($TestID){
    case enum_StripeUnitTests::test_CreateToken:
        $returnArray = stripe_charger::test_CreateToken();
        $result = $returnArray['Result'] ? "pass" : "fail";
        echo "Result of creating token is: ".$result."<br/>";
        echo "Message is: ".$returnArray['Reason']."<br/><br/>";

        echo "Printing token object...<br/>";
        echo "<pre>";
        print_r($returnArray['Token']);
        echo "</pre>";
        break;

    case enum_StripeUnitTests::test_RetrieveToken:
        $returnArray = stripe_charger::test_RetrieveToken();
        $result = $returnArray['Result'] ? "pass" : "fail";
        echo "Result of retrieving token is: ".$result."<br/>";
        echo "Message is: ".$returnArray['Reason']."<br/><br/>";

        echo "Printing token object...<br/>";
        echo "<pre>";
        print_r($returnArray['Token']);
        echo "</pre>";
        break;

    case enum_StripeUnitTests::test_CreateNewCustomer:
        $returnArray = stripe_charger::test_CreateNewCustomer();
        $result = $returnArray['Result'] ? "pass" : "fail";
        echo "Result of creating customer is: ".$result."<br/>";
        echo "Message is: ".$returnArray['Reason']."<br/><br/>";

        echo "Printing customer object...<br/>";
        echo "<pre>";
        print_r($returnArray['Customer']);
        echo "</pre>";
        break;

    case enum_StripeUnitTests::test_OneTimeCharge:
        $returnArray = stripe_charger::test_OneTimeCharge();
        $result = $returnArray['Result'] ? "pass" : "fail";
        echo "Result of creating charge for customer is: ".$result."<br/>";
        echo "Message is: ".$returnArray['Reason']."<br/><br/>";

        echo "Printing charge object...<br/>";
        echo "<pre>";
        print_r($returnArray['Charge']);
        echo "</pre>";
        break;

    case enum_StripeUnitTests::test_NewSubscriptionCharge:
        $returnArray = stripe_charger::test_NewSubscriptionCharge();
        $result = $returnArray['Result'] ? "pass" : "fail";
        echo "Result of creating new subscription for customer is: ".$result."<br/>";
        echo "Message is: ".$returnArray['Reason']."<br/><br/>";

        echo "Printing customer object...<br/>";
        echo "<pre>";
        print_r($returnArray['Customer']);
        echo "</pre>";
        break;

    case enum_StripeUnitTests::test_RemoveCreditCard:
        $returnArray = stripe_charger::test_RemoveCreditCard();
        $result = $returnArray['Result'] ? "pass" : "fail";
        echo "Result of removing a credit card for customer is: ".$result."<br/>";
        echo "Message is: ".$returnArray['Reason']."<br/><br/>";

        echo "Printing customer object...<br/>";
        echo "<pre>";
        print_r($returnArray['Customer']);
        echo "</pre>";
        break;

    case enum_StripeUnitTests::test_AddCreditCard:
        $returnArray = stripe_charger::test_AddCreditCard();
        $result = $returnArray['Result'] ? "pass" : "fail";
        echo "Result of adding a new credit card for customer is: ".$result."<br/>";
        echo "Message is: ".$returnArray['Reason']."<br/><br/>";

        echo "Printing customer object...<br/>";
        echo "<pre>";
        print_r($returnArray['Customer']);
        echo "</pre>";
        break;

    default:
        echo "Invalid stripe Unit test input.";
        break;
}


?>