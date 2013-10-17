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


//test retrieving the token that was created
echo "<h1>POST page for Testing Stripe Token</h1>";
if(isset($_POST['stripeToken'])){
    $returnArray = stripe_charger::test_RetrieveToken($_POST['stripeToken']);
    $result = $returnArray['Result'] ? "pass" : "fail";
    echo "Result of retrieving token is: ".$result."<br/>";
    echo "Message is: ".$returnArray['Reason']."<br/><br/>";

    echo "Printing token object...<br/>";
    echo "<pre>";
    print_r($returnArray['Token']);
    echo "</pre>";
}
else{
    echo "Stripe Token (\$_POST['stripeToken']) was not found.";
}

//valid token: tok_2Uh79h4VkS3Uqe
?>