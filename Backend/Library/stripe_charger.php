<?php
require_once('stripe_config.php');

class stripe_charger{

    public static function createToken($inTokenInputArray){

        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Token' => null,
            'StripeException' => null
        );

        try{
            $token  = Stripe_Token::create($inTokenInputArray);
            if($token != null){
                $returnArray['Token'] = $token;
                $returnArray['Result'] = 1;
                $returnArray['Reason'] = "Token created successfully.";
            }
            else{
                $returnArray['Reason'] = "Token not created successfully.";
            }
        }
        catch(Stripe_CardError $e) {
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
            $returnArray['Reason'] = "Generic exception creating token. Message: ".$ex->getMessage();
        }

        return $returnArray;

    }

    public static function retrieveToken($inStripeTokenID){

        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Token' => null,
            'StripeException' => null
        );

        try{
            $token = Stripe_Token::retrieve($inStripeTokenID);

            if($token != null){
                $returnArray['Token'] = $token;
                $returnArray['Result'] = 1;
                $returnArray['Reason'] = "Token retrieved successfully.";
            }
            else{
                $returnArray['Reason'] = "Token was not found for Token ID ".$inStripeTokenID;
            }

        }
        catch(Stripe_CardError $e) {
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
        catch (Exception $ex){
            $returnArray['Reason'] = "Generic exception retrieving token. Message: ".$ex->getMessage();
        }

        return $returnArray;
    }

    public static function createCustomer($inEmail, $inStripeTokenID){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Customer' => null,
            'StripeException' => null
        );

        $myCustomerArray = array(
            'email' => $inEmail,
            'card' => $inStripeTokenID
        );

        try{
            $customer = Stripe_Customer::create($myCustomerArray);

            if($customer != null){
                $returnArray['Customer'] = $customer;
                $customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($customer);
                //check customer object
                if($customerArray['email'] == $inEmail){
                    $cardArray = stripe_charger::getCardArrayFromStripeCustomerObject($customer);
                    if($cardArray != null){
                        $returnArray['Result'] = 1;
                        $returnArray['Reason'] = "Creation of new customer successful.";
                    }
                    else{
                        $returnArray['Reason'] = "Card object not found.";
                    }
                }
                else{
                    $returnArray['Reason'] = "Customer object email (".$customerArray['email'].") did not match ".$inEmail." .";
                }
            }
            else{
                $returnArray['Reason'] = "Failed to create the customer.";
            }
        }
        catch(Stripe_CardError $e) {
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
            $returnArray['Reason'] = "Had an error creating a new customer. Exception message: ".$ex->getMessage();
        }

        return $returnArray;
    }

    public static function retrieveCustomer($inStripeCustomerID){

        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Customer' => null,
            'StripeException' => null
        );

        try{
            $customer = Stripe_Customer::retrieve($inStripeCustomerID);

            if($customer != null){
                $returnArray['Customer'] = $customer;
                $returnArray['Result'] = 1;
                $returnArray['Reason'] = "Customer retrieved successfully.";
            }
            else{
                $returnArray['Reason'] = "Customer was not found for Customer ID ".$inStripeCustomerID;
            }

        }
        catch(Stripe_CardError $e) {
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
        catch (Exception $ex){
            $returnArray['Reason'] = "Generic exception retrieving token. Message: ".$ex->getMessage();
        }

        return $returnArray;
    }

    public static function deleteCustomer($inStripeCustomerID){

        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'AlreadyDeleted' => 0,
            'Customer' => null,
            'StripeException' => null
        );

        try{
            $customer = Stripe_Customer::retrieve($inStripeCustomerID);

            if($customer != null){
                $customerInitialArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($customer);
                if(!array_key_exists('deleted', $customerInitialArray)){
                    $customer->delete();
                    $deletedCustomer = Stripe_Customer::retrieve($inStripeCustomerID);
                    $deletedCustomerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($deletedCustomer);
                    if(array_key_exists('deleted', $deletedCustomerArray) && (bool)$deletedCustomerArray['deleted']){
                        $returnArray['Customer'] = $deletedCustomer;
                        $returnArray['Result'] = 1;
                        $returnArray['Reason'] = "Customer deleted successfully.";
                    }
                    else{
                        $returnArray['Reason'] = "Customer was not deleted correctly for Customer ID ".$inStripeCustomerID;
                    }
                }
                else{
                    $returnArray['Result'] = 1;
                    $returnArray['AlreadyDeleted'] = 1;
                    $returnArray['Reason'] = "Customer was already deleted for Customer ID ".$inStripeCustomerID;
                }
            }
            else{
                $returnArray['Reason'] = "Customer was not found for Customer ID ".$inStripeCustomerID;
            }

        }
        catch(Stripe_CardError $e) {
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
        catch (Exception $ex){
            $returnArray['Reason'] = "Generic exception retrieving token. Message: ".$ex->getMessage();
        }

        return $returnArray;
    }


    public static function chargeOneTimePurchaseByInput($inChargeInputArray){

        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Charge' => null,
            'StripeException' => null
        );

        try{
            $charge = Stripe_Charge::create($inChargeInputArray);

            //check if successful
            if($charge != null){
                $returnArray['Charge'] = $charge;
                $returnArray['Result'] = 1;
                $returnArray['Reason'] = "Charge test was successful";

                if($charge['failure_code'] == null){
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = "Charge test was successful";
                }
                else{
                    $returnArray['Reason'] = "Charge was not successful from Stripe. Code was ".$charge['failure_code']." with Message of ".$charge['failure_message']." .";
                }
            }
            else{
                $returnArray['Reason'] = "Charge object was null.";
            }
        }
        catch(Stripe_CardError $e) {
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
        catch (Exception $ex){
            $returnArray['Reason'] = "Generic exception doing one time charge: ".$ex->getMessage();
        }

        return $returnArray;

    }

    public static function chargeOneTimePurchase($inStripeCustomerID, $inChargeAmount, $inCurrencyCode = "usd"){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Charge' => null,
            'StripeException' => null
        );

        $chargeInputArray = array(
            'customer' => $inStripeCustomerID,
            'amount'   => $inChargeAmount,
            'currency' => $inCurrencyCode
        );

        try{
            $charge = Stripe_Charge::create($chargeInputArray);

            //check if successful
            if($charge != null){
                $returnArray['Charge'] = $charge;
                $chargeArray = util_general::getProtectedValue($charge, "_values");

                if($chargeArray['failure_code'] == null){
                    //check minimal details of charge
                    $cardArray = util_general::getProtectedValue($chargeArray['card'], "_values");

                    if($chargeArray['customer'] == $chargeInputArray['customer']){
                        if($chargeArray['amount'] == $chargeInputArray['amount']){
                            if($cardArray['customer'] == $chargeInputArray['customer']){
                                $returnArray['Result'] = 1;
                                $returnArray['Reason'] = "Charge test was successful";
                            }
                            else{
                                $returnArray['Reason'] = "Card object's customer field (".$cardArray['customer'].") did not match ".$chargeInputArray['customer']." .";
                            }
                        }
                        else{
                            $returnArray['Reason'] = "Charge object's amount field (".$chargeArray['amount'].") did not match ".$chargeInputArray['amount']." .";
                        }
                    }
                    else{
                        $returnArray['Reason'] = "Charge object's customer field (".$chargeArray['customer'].") did not match ".$chargeInputArray['customer']." .";
                    }
                }
                else{
                    $returnArray['Reason'] = "Charge was not successful from Stripe. Code was ".$chargeArray['failure_code']." with Message of ".$chargeArray['failure_message']." .";
                }
            }
            else{
                $returnArray['Reason'] = "Charge object was null.";
            }
        }
        catch(Stripe_CardError $e) {
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
        catch (Exception $ex){
            $returnArray['Reason'] = "Generic exception doing one time charge: ".$ex->getMessage();
        }

        return $returnArray;
    }

    public static function chargeSubscription($inStripeCustomerID, $inStripePlanID, $inProrate = true){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Customer' => null,
            'StripeException' => null
        );

        try{

            //create subscription
            $customer = Stripe_Customer::retrieve($inStripeCustomerID);
            if($customer != null){
                $returnArray['Customer'] = $customer;

                $prorate = is_bool($inProrate) ? $inProrate : true;

                $subscriptionInputArray = array(
                    'plan' => $inStripePlanID,
                    'prorate' => $prorate
                );

                $customer->updateSubscription($subscriptionInputArray);
                $customerArray = util_general::getProtectedValue($returnArray['Customer'], "_values");
                $subscriptionArray = util_general::getProtectedValue($customerArray['subscription'], "_values");
                $subscriptionPlanArray = util_general::getProtectedValue($subscriptionArray['plan'], "_values");

                //check subscription
                if($subscriptionArray != null){
                    if($subscriptionPlanArray != null){
                        if($subscriptionPlanArray['id'] == $subscriptionInputArray['plan']){
                            $returnArray['Result'] = 1;
                            $returnArray['Reason'] = "Subscription created successfully.";
                            $returnArray['Customer'] = $customer;
                        }
                        else{
                            $returnArray['Reason'] = "Subscription Plan Id (".$subscriptionPlanArray['id'].") did not match ".$subscriptionInputArray['plan']." .";
                        }
                    }
                    else{
                        $returnArray['Reason'] = "Subscription plan object not found in Subscription object.";
                    }
                }
                else{
                    $returnArray['Reason'] = "Subscription object not found in Customer object.";
                }
            }
            else{
                $returnArray['Reason'] = "Stripe Customer not found.";
            }
        }
        catch(Stripe_CardError $e) {
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
        catch (Exception $ex){
            $returnArray['Reason'] = "Generic exception doing creating new subscription: ".$ex->getMessage();
        }

        return $returnArray;

    }

    public static function cancelSubscription($inStripeCustomerID){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Customer' => null,
            'StripeException' => null
        );

        try{
            $customer = Stripe_Customer::retrieve($inStripeCustomerID);
            if($customer != null){
                $customer->cancelSubscription();
                $returnArray['Result'] = 1;
                $returnArray['Reason'] = "Subscription cancelled.";
                $returnArray['Customer'] = $customer;
            }
            else{
                $returnArray['Reason'] = "Stripe customer not found.";
            }
        }
        catch(Stripe_CardError $e) {
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
            $returnArray['Reason'] = "Generic exception cancelling subscription for StripeCustomerID ".$inStripeCustomerID.": ".$ex->getMessage();
        }

        return $returnArray;
    }

    public static function removeCreditCard($inStripeCustomerID, $inStripeCreditCardID){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Customer' => null,
            'StripeException' => null
        );

        try{
            $customer = Stripe_Customer::retrieve($inStripeCustomerID);
            if($customer != null){
                $creditCard = $customer->cards->retrieve($inStripeCreditCardID);
                if($creditCard != null){
                    $ccLastFour = $creditCard->last4;
                    $creditCard->delete();
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = "Credit card ".$ccLastFour." removed";
                    $returnArray['Customer'] = Stripe_Customer::retrieve($inStripeCustomerID);
                }
                else{
                    $returnArray['Reason'] = "Stripe credit card not found.";
                }
            }
            else{
                $returnArray['Reason'] = "Stripe customer not found.";
            }
        }
        catch(Stripe_CardError $e) {
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
            $returnArray['Reason'] = "Generic exception removing card for StripeCustomerID ".$inStripeCustomerID.": ".$ex->getMessage();
        }

        return $returnArray;
    }

    public static function addCreditCard($inStripeCustomerID, $inStripeCCToken){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Customer' => null,
            'StripeException' => null
        );

        try{
            $customer = Stripe_Customer::retrieve($inStripeCustomerID);
            if($customer != null){
                $customer->cards->create(array("card" => $inStripeCCToken));
                $returnArray['Customer'] = Stripe_Customer::retrieve($inStripeCustomerID);

                $cardArray = stripe_charger::getCardArrayFromStripeCustomerObject($returnArray['Customer']);
                if(!empty($cardArray)){
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = "Credit card added successfully.";
                }
                else{
                    $returnArray['Reason'] = "Credit card was not added for CustomerID ".$inStripeCustomerID.". TokenID ".$inStripeCCToken;
                }
            }
            else{
                $returnArray['Reason'] = "Stripe customer not found.";
            }
        }
        catch(Stripe_CardError $e) {
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
            $returnArray['Reason'] = "Generic exception adding card for StripeCustomerID ".$inStripeCustomerID.": ".$ex->getMessage();
        }

        return $returnArray;
    }

    /*
     * Helper functions
     *
     */

    public static function verifySubscriptionExistsById($inSubscriptionTypeId){
        if(validate::tryParseInt($inSubscriptionTypeId)){
            $selectArray = array('SubscriptionTypeId');  //or array("field1", "field2"...)
            $whereClause = "SubscriptionTypeId = '".$inSubscriptionTypeId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            $myAccount = database::select("SubscriptionType", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);

            if(count($myAccount) > 0){

                return true;
            }
            else{

                return false;
            }

        }
        else{
            $errorMessage = "SubscriptionTypeId was not an integer";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            //die("Invalid input.");
            return false;
        }

    }

    public static function getCustomerArrayFromStripeCustomerObject($inStripeCustomerObject){
        $customerArray = util_general::getProtectedValue($inStripeCustomerObject, "_values");

        return $customerArray;
    }

    public static function getSubscriptionArrayFromStripeCustomerObject($inStripeCustomerObject){
        $customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($inStripeCustomerObject);
        $subscriptionArray = util_general::getProtectedValue($customerArray['subscription'], "_values");

        return $subscriptionArray;
    }

    public static function getPlanArrayFromStripeCustomerObject($inStripeCustomerObject){
        $subscriptionArray = stripe_charger::getSubscriptionArrayFromStripeCustomerObject($inStripeCustomerObject);
        $planArray = util_general::getProtectedValue($subscriptionArray['plan'], "_values");

        return $planArray;
    }

    public static function getCardArrayFromStripeCustomerObject($inStripeCustomerObject){
        $customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($inStripeCustomerObject);
        $cardObject = util_general::getProtectedValue($customerArray['cards'], "_values");
        $cardArray = util_general::getProtectedValue($cardObject['data'][0], "_values");

        return $cardArray;
    }

    /*
     * Test functions
     */

    public static function test_CreateToken(){

        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");

        $tokenInputArray = array(
            'card' => array(
                'name' => "Prep TokenUnitTest",
                'number' => "4242424242424242",
                'exp_month' => 10,
                'exp_year' => 2030,
                'cvc' => "316"
            )
        );

        $tokenCreationResponse = stripe_charger::createToken($tokenInputArray);

        return $tokenCreationResponse;
    }

    public static function test_RetrieveToken(){

        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");
        $inStripeTokenID = "tok_2Uiw4sdKVCELj0";

        $retrievalResponse = stripe_charger::retrieveToken($inStripeTokenID);

        return $retrievalResponse;
    }

    public static function test_CreateNewCustomer($inEmail = null){

        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");

        $tokenInputArray = array(
            'card' => array(
                'name' => "Prep UnitTest",
                'number' => "4242424242424242",
                'exp_month' => 7,
                'exp_year' => 2016,
                'cvc' => "314"
            )
        );
        $timestamp = (string)time();
        $email = ($inEmail != null && validate::emailAddress($inEmail)) ? $inEmail : "customer10139-".$timestamp."@example.com";

        $tokenCreationResponse = stripe_charger::createToken($tokenInputArray);

        if($tokenCreationResponse['Result']){
            $tokenArray = util_general::getProtectedValue($tokenCreationResponse['Token'], "_values");
            $customerCreationResponse = stripe_charger::createCustomer($email, $tokenArray['id']);

            return $customerCreationResponse;
        }
        else{

            return array(
                'Result' => 0,
                'Reason' => "There was an issue creating the token.",
                'Customer' => null
            );
        }



        return $returnArray;
    }

    public static function test_OneTimeCharge(){

        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");
        $myStripeCustomerID = "cus_2U6IvqZvGj8txU"; //email: customer10139@example.com
        $myChargeAmount = 101;

        $chargeOneTimeResponse = stripe_charger::chargeOneTimePurchase($myStripeCustomerID, $myChargeAmount);

        return $chargeOneTimeResponse;

    }

    public static function test_NewSubscriptionCharge($inEmail = null){
        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");

        $timestamp = (string)time();
        $email = ($inEmail != null && validate::emailAddress($inEmail)) ? $inEmail : "customer10139-".$timestamp."@example.com";
        $stripePlanID = "prep_gold_10139";

        //create new customer first
        $createCustomerResponse = stripe_charger::test_CreateNewCustomer($email);

        if($createCustomerResponse['Result']){
            $customerArray = util_general::getProtectedValue($createCustomerResponse['Customer'], "_values");
            $subscriptionResponse = stripe_charger::chargeSubscription($customerArray['id'], $stripePlanID);

            return $subscriptionResponse;
        }
        else{
            return array(
                'Result' => 0,
                'Reason' => "Unable to create test customer. ".$createCustomerResponse['Reason'],
                'Customer' => null
            );
        }
    }

    public static function test_CardDeclined(){
        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");

        $returnArray =  array(
            'Result' => 0,
            'Reason' => "",
            'StripeException' => null
        );

        $tokenInputArray = array(
            'card' => array(
                'name' => "Prep TokenUnitTest",
                'number' => "4000000000000002",
                'exp_month' => 4,
                'exp_year' => 2020,
                'cvc' => "317"
            )
        );

        $tokenCreationResponse = stripe_charger::createToken($tokenInputArray);

        if($tokenCreationResponse['Result']){
            $tokenArray = util_general::getProtectedValue($tokenCreationResponse['Token'], "_values");

            $chargeInputArray = array(
                'card' => $tokenArray['id'],
                'amount'   => 750,
                'currency' => "usd"
            );

            $chargeResponse = stripe_charger::chargeOneTimePurchaseByInput($chargeInputArray);

            //expecting a Stripe_CardError exception, so result should be false
            if(!$chargeResponse['Result']){
                $stripeException = $chargeResponse['StripeException'];
                $HTTP_errorCode = $stripeException->http_status;
                $errorArray = $stripeException->json_body['error'];

                if($errorArray['type'] == "card_error" && $errorArray['code'] == "card_declined"){
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = "Charge was not completed due to decline reason as expected.";
                    $returnArray['StripeException'] = $stripeException;
                }
                else{
                    $returnArray['Reason'] = "Charge was not completed, but for some other reason. HTTP status was ".$HTTP_errorCode." and error code was ".$errorArray['code']." .";
                    $returnArray['StripeException'] = $stripeException;
                }
            }
            else{
                $returnArray['Reason'] = "Charge was not declined.";
            }
        }
        else{
            $returnArray['Reason'] = "Decline token was not created. ".$tokenCreationResponse['Reason'];
        }

        return $returnArray;
    }

    public static function test_RemoveCreditCard($inEmail = null){
        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");

        $tokenInputArray = array(
            'card' => array(
                'name' => "Prep UnitTest",
                'number' => "4242424242424242",
                'exp_month' => 7,
                'exp_year' => 2016,
                'cvc' => "314"
            )
        );
        $timestamp = (string)time();
        $email = ($inEmail != null && validate::emailAddress($inEmail)) ? $inEmail : "customer10139-".$timestamp."@example.com";

        $tokenCreationResponse = stripe_charger::createToken($tokenInputArray);

        if($tokenCreationResponse['Result']){
            $tokenArray = util_general::getProtectedValue($tokenCreationResponse['Token'], "_values");
            $customerCreationResponse = stripe_charger::createCustomer($email, $tokenArray['id']);
            $customerArray = util_general::getProtectedValue($customerCreationResponse['Customer'], "_values");
            if(validate::isNotNullOrEmpty_String($customerArray['default_card'])){
                return stripe_charger::removeCreditCard($customerArray['id'], $customerArray['default_card']);
            }
            else{
                return array(
                    'Result' => 0,
                    'Reason' => "There was an issue creating the customer object.",
                    'Customer' => null
                );
            }
        }
        else{

            return array(
                'Result' => 0,
                'Reason' => "There was an issue creating the token.",
                'Customer' => null
            );
        }

        return $returnArray;
    }

    public static function test_AddCreditCard($inEmail = null){
        Stripe::setApiKey("sk_test_nZvhxHClm4cR4fA9rv4Um4aU");

        $tokenInputArray = array(
            'card' => array(
                'name' => "Prep UnitTestAdd",
                'number' => "4242424242424242",
                'exp_month' => 7,
                'exp_year' => 2016,
                'cvc' => "314"
            )
        );
        $timestamp = (string)time();
        $email = ($inEmail != null && validate::emailAddress($inEmail)) ? $inEmail : "customer10139-".$timestamp."@example.com";

        $tokenCreationResponse = stripe_charger::createToken($tokenInputArray);

        if($tokenCreationResponse['Result']){
            $tokenArray = util_general::getProtectedValue($tokenCreationResponse['Token'], "_values");
            $customerInputArray = array(
                'email' => $email
            );
            $customer = Stripe_Customer::create($customerInputArray);
            if($customer != null){
                $customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($customer);

                if(!validate::isNotNullOrEmpty_String($customerArray['default_card'])){
                    return stripe_charger::addCreditCard($customerArray['id'], $tokenArray['id']);
                }
                else{
                    return array(
                        'Result' => 0,
                        'Reason' => "There was an issue creating the customer object. Credit card was set already.",
                        'Customer' => null
                    );
                }
            }
            else{
                return array(
                    'Result' => 0,
                    'Reason' => "There was an issue creating the customer object. Customer was null.",
                    'Customer' => null
                );
            }
        }
        else{

            return array(
                'Result' => 0,
                'Reason' => "There was an issue creating the token.",
                'Customer' => null
            );
        }

        return $returnArray;
    }

}



?>