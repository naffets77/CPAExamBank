<?php
try{
    //stripe function
}
catch(Stripe_CardError $e) {
    // Since it's a decline, Stripe_CardError will be caught
    $returnArray['StripeException'] = $e;
    $returnArray['Reason'] = "Stripe threw a Stripe_CardError. Message: ".$e->getMessage();
}
catch (Stripe_InvalidRequestError $e) {
    // Invalid parameters were supplied to Stripe's API
    $returnArray['StripeException'] = $e;
    $returnArray['Reason'] = "Stripe threw a Stripe_InvalidRequestError. Message: ".$e->getMessage();
}
catch (Stripe_AuthenticationError $e) {
    // Authentication with Stripe's API failed
    // (maybe you changed API keys recently)
    $returnArray['StripeException'] = $e;
    $returnArray['Reason'] = "Stripe threw a Stripe_AuthenticationError. Message: ".$e->getMessage();
}
catch (Stripe_ApiConnectionError $e) {
    // Network communication with Stripe failed
    $returnArray['StripeException'] = $e;
    $returnArray['Reason'] = "Stripe threw a Stripe_ApiConnectionError. Message: ".$e->getMessage();
}
catch (Stripe_Error $e) {
    // Display a very generic error to the user
    $returnArray['StripeException'] = $e;
    $returnArray['Reason'] = "Stripe threw a Stripe_Error. Message: ".$e->getMessage();
}
catch(Exception $ex){
    $returnArray['Reason'] = "Generic exception [INSERT CONTEXT]. Message: ".$ex->getMessage();
}

?>