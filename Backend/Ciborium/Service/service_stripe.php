<?php
require_once(realpath(__DIR__)."/config.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(service_configuration::$environment_librarypath."/validate.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(service_configuration::$ciborium_librarypath."/ciboriumlib_account.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_stripe.php");


class service_stripe{

    //service name
    static $service = "service_stripe";

    /**
     * Service: chargeSubscription()
     * Charges the user selected subscription
     *
     * POST Input:
     *      moduleSelection: Array
     *
     * @return array
     *      Reason
     *      Result
     *      CreateNewCustomer
     */
    static function chargeSubscription(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0,
            "CreateNewCustomer" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){

            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);
            $moduleSelection = validate::requirePostField('moduleSelection', self::$service, __FUNCTION__);
            $promoCode = isset($_POST['promoCode']) ? $_POST['promoCode'] : null;

            $checkValueArray = array(
                "moduleSelection" => $moduleSelection,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){

                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);

                $myArray['Reason'] = "Missing requried variable(s)";
                return $myArray;
            }
            $isNewSubscriber = validate::isNotNullOrEmpty_String($_SESSION['Licenses']->StripeCustomerId) ? false : true;

            if($isNewSubscriber){
                $myArray['Reason'] = "Cannot charge a new user.";
                $myArray['CreateNewCustomer'] = 1;
                return $myArray;
            }
            else{

                $myJSONString = trim($moduleSelection);
                if(validate::isValidJSONString($myJSONString)){
                    $moduleArray = json_decode($myJSONString, true);

                    return $myStripeCharge = ciborium_stripe::chargeSubscription($_SESSION['Licenses']->StripeCustomerId, $moduleArray, $_SESSION['Licenses']->SubscriptionTypeId, $_SESSION['Licenses']->LicenseId, $_SESSION['Licenses']->AccountUserId, __METHOD__, $promoCode);
                }
                else{
                    $myArray['Reason'] = "Invalid variable(s)";
                    return $myArray;
                }

            }


        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }


    }

    static function createNewSubscriber(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
//            $ccNumber = validate::requirePostField('ccNumber', self::$service, __FUNCTION__);
//            $ccExpirationMonth = validate::requirePostField('ccExpirationMonth', self::$service, __FUNCTION__);
//            $ccExpirationYear = validate::requirePostField('ccExpirationYear', self::$service, __FUNCTION__);
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);
            $stripeToken = validate::requirePostField('stripeToken', self::$service, __FUNCTION__);

            $checkValueArray = array(
                "stripeToken" => $stripeToken,
//                "ccNumber" => $ccNumber,
//                "ccExpirationMonth" => $ccExpirationMonth,
//                "ccExpirationYear" => $ccExpirationYear,
                "email" => $_SESSION['Account']->LoginName,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){
                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);

                $myArray['Reason'] = "Missing requried variable(s)";
                return $myArray;
            }

            $myCustomerResponse = ciborium_stripe::createNewSubscriber($_SESSION['Licenses']->LicenseId, $_SESSION['Account']->LoginName, $stripeToken, __METHOD__);

            return $myCustomerResponse;

        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }
    }

    static function removeCreditCard(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){

            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){

                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);

                $myArray['Reason'] = "Missing required variable(s)";
                return $myArray;
            }

            return ciborium_stripe::removeCreditCard($_SESSION['Licenses']->LicenseId, $_SESSION['Licenses']->StripeCustomerId, $_SESSION['Licenses']->StripeCreditCardId, __METHOD__);

        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }

    }

    static function addCreditCard(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){

            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);
            $stripeToken = validate::requirePostField('stripeToken', self::$service, __FUNCTION__);

            $checkValueArray = array(
                "hashCheckResult" => (bool)$hashCheckReturn['Result'],
                "stripeToken" => $stripeToken
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){

                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);

                $myArray['Reason'] = "Missing required variable(s)";
                return $myArray;
            }

            return ciborium_stripe::addCreditCard($_SESSION['Licenses']->LicenseId, $_SESSION['Licenses']->StripeCustomerId, $stripeToken, __METHOD__);

        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }

    }

    static function updateNewCreditCard(){

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){

            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);
            $stripeToken = validate::requirePostField('stripeToken', self::$service, __FUNCTION__);

            $checkValueArray = array(
                "hashCheckResult" => (bool)$hashCheckReturn['Result'],
                "stripeToken" => $stripeToken
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){

                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);

                $myArray['Reason'] = "Missing required variable(s)";
                return $myArray;
            }

            $removeCardResponse = ciborium_stripe::removeCreditCard($_SESSION['Licenses']->LicenseId, $_SESSION['Licenses']->StripeCustomerId, $_SESSION['Licenses']->StripeCreditCardId, __METHOD__);

            if($removeCardResponse['Result']){
                $addCardResponse = ciborium_stripe::addCreditCard($_SESSION['Licenses']->LicenseId, $_SESSION['Licenses']->StripeCustomerId, $stripeToken, __METHOD__);

                if($addCardResponse['Result']){
                    return $addCardResponse;
                }
                else{
                    $myArray['Reason'] = "Error updating credit card";
                    return $myArray;
                }
            }
            else{
                $myArray['Reason'] = "Error updating credit card";
                return $myArray;
            }

        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }
    }

}
?>