<?php
require_once(realpath(__DIR__)."/config.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_question.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_promotion.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/account.php");
include_once(ciborium_configuration::$environment_librarypath."/stripe_charger.php");

/*
 * This file is for handling stripe transactions
 *
 */

class ciborium_stripe{

    public $subscriptionTypes; //array
    //public $subscriptionPrices; //array
    //public $subscriptionTerms; //array

    function ciborium_stripe(){
        $this->subscriptionTypes = self::getPublicSubscriptionOptions();
    }

    /**
     * Library: createNewSubscriber()
     * Creates a new Stripe Customer object
     *
     * @param $inLicenseId
     * @param $inEmail
     * @param $inStripeTokenID
     * @param $inCaller
     * @return array
     *      Reason
     *      Result
     *      CustomerId (stripe)
     */
    public static function createNewSubscriber($inLicenseId, $inEmail, $inStripeTokenID, $inCaller){
        $myArray = array(
            'Reason' => "",
            'Result' => 0,
            'CustomerId' => null
        );

        //Verify inputs
        if(!validate::tryParseInt($inLicenseId)){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for StripeToken ".$inStripeTokenID.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inStripeTokenID))){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for StripeToken ".$inStripeTokenID.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!validate::emailAddress($inEmail)){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for email address ".$inEmail.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!account::verifyLicenseExistsById($inLicenseId)){
            $myArray['Reason'] = "License does not exist ";
            $errorMessage = $myArray['Reason']." for LicenseId ".$inLicenseId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }

        $accountUserLicense = account::getLicenseById($inLicenseId)[0];

        if(!validate::isNotNullOrEmpty_String($accountUserLicense->StripeCustomerId)){
            $createCustomerResponse = stripe_charger::createCustomer($inEmail, $inStripeTokenID);

            if($createCustomerResponse['Result']){
                $myArray['Reason'] = "Customer created successfully.";
                $myArray['Result'] = 1;
                $customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($createCustomerResponse['Customer']);
                $cardArray = stripe_charger::getCardArrayFromStripeCustomerObject($createCustomerResponse['Customer']);
                $myArray['CustomerId'] = $customerArray['id'];

                $updateResult = account::updateLicenseForNewStripeCustomer($inLicenseId, $customerArray['id'], $cardArray['type'], $cardArray['last4'], util_datetime::getDateStringToDateTime($cardArray['exp_month']."/1/".$cardArray['exp_year']), $cardArray['id'], __METHOD__);
            }
            else{
                $myArray['Reason'] = "Error creating customer.";
                if($createCustomerResponse['StripeException'] != null){
                    $errorMessage = "Error creating customer for email ".$inEmail.", Stripe threw an exception. Message: ".$createCustomerResponse['Reason']." . Called by ".$inCaller;
                }
                else{
                    $errorMessage = "Error creating customer for email ".$inEmail.", the cancellation was not completed. Message: ".$createCustomerResponse['Reason']." . Called by ".$inCaller;
                }
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $myArray['Reason'] = "User is already a subscriber";
            $errorMessage = $myArray['Reason']." for LicenseId ".$inLicenseId;
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
        }



        return $myArray;
    }

    /**
     * Library: chargeSubscription()
     * Charges/updates subscription for a user
     *
     * @param $inStripeCustomerID
     * @param $inModuleArray
     * @param $inSubscriptionTypeId
     * @param $inLicenseId
     * @param $inAccountUserId
     * @param $inCaller
     * @param $inPromotionCode
     * @return array
     *      Reason
     *      Result
     *      ConfirmationNumber
     *      CancelledSubscription
     */
    public static function chargeSubscription($inStripeCustomerID, $inModuleArray, $inSubscriptionTypeId, $inLicenseId, $inAccountUserId, $inCaller, $inPromotionCode = null){
        $returnArray = array(
            'Reason' => "",
            'Result' => 0,
            'ConfirmationNumber' => null,
            'CancelledSubscription' => 0
        );

        //if promotion code was passed in
        $promotionId = null;
        $promotion = null;
        $accountUserToPromotion = null;
        if(validate::isNotNullOrEmpty_String($inPromotionCode)){
            $promoCodeResultArray = ciborium_promotion::validatePromotionCodeForUser($inPromotionCode, $inAccountUserId, $inCaller);
            if($promoCodeResultArray['Result']){
                //TODO: may have to check against subscription array one day
                $promotionId = $promoCodeResultArray['PromotionId'];
                $promotion = ciborium_promotion::getPromotionById($promotionId);
                $accountUserToPromotion = ciborium_promotion::getAccountUserToPromotion($promotionId, $inAccountUserId, $inCaller)[0];
            }
            else{
                $returnArray['Reason'] = "Promo code invalid";
                return $returnArray;
            }
        }

        $isNewSubscriber = ciborium_stripe::isSubscriberNew($inLicenseId);

        //Verify inputs
        if(!validate::isNotNullOrEmpty_String(trim($inStripeCustomerID))){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inStripeCustomerID ".$inStripeCustomerID." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(2, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        $checkModuleSelectionResponse = ciborium_stripe::checkValidModuleSelectionArray($inModuleArray);
        if(!$checkModuleSelectionResponse['Result']){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inModuleArray from caller ".$inCaller.". ".$checkModuleSelectionResponse['Reason'].".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inSubscriptionTypeId)) || !validate::tryParseInt($inSubscriptionTypeId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inSubscriptionTypeId ".$inSubscriptionTypeId." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inLicenseId)) || !validate::tryParseInt($inLicenseId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inLicenseId ".$inLicenseId." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inAccountUserId)) || !validate::tryParseInt($inAccountUserId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inAccountUserId ".$inAccountUserId." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }


        //query for subscription
        $inDBModuleArray = array(
            'HasFARModule' => 0,
            'HasAUDModule' => 0,
            'HasBECModule' => 0,
            'HasREGModule' => 0
        );
        foreach($inModuleArray as $key => $value){
            $value = (int)(bool)$value;
            $key1 = "Has".$key.'Module';
            $inDBModuleArray[$key1] = $value;
        }
        $mySubscriptions = ciborium_stripe::findStandardSubscriptionByModuleSelection($inDBModuleArray);
        $myCurrentSubscriptionTypeId = (int)$inSubscriptionTypeId; //old subscription id

        if(count($mySubscriptions) == 1){
            //attempt the charge if current plan is different from desired plan

            if($myCurrentSubscriptionTypeId != $mySubscriptions[0]->SubscriptionTypeId){

                //apply promotion code if applicable
                if($promotion != null){
                    $applyPromotionResponse = ciborium_stripe::applyCouponToCustomer($inStripeCustomerID, $promotion->StripeCouponId, $inCaller);
                    if($applyPromotionResponse['Result']){
                        ciborium_promotion::redeemPromotionForUser($promotion->PromotionId, $accountUserToPromotion->AccountUserToPromotionId, $inCaller);
                    }
                    else{
                        $errorMessage = "Tried to apply promo code id ".$promotion->StripeCouponId." to user ".$inAccountUserId." (StripeCustomerId ".$inStripeCustomerID.") and failed. See error logs for details.";
                        util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
                    }
                }

                //charge it
                $chargeResponse = stripe_charger::chargeSubscription($inStripeCustomerID, $mySubscriptions[0]->StripePlanId);
                if($chargeResponse['Result']){
                    $returnArray['Result'] = 1;
                    $returnArray['Reason'] = "Charge completed successfully.";
                    $LicenseTransactionValuesArray = array(
                        'LicenseTransactionTypeId' => enum_LicenseTransactionType::Subscribed,
                        'SystemNotes' => "New subscription (".$mySubscriptions[0]->StripePlanId.") charged via ".$inCaller."."
                    );

                    $customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($chargeResponse['Customer']);
                    $subscriptionArray = stripe_charger::getSubscriptionArrayFromStripeCustomerObject($chargeResponse['Customer']);

                    //significant datetimes
                    $mySubscribedDate = util_datetime::getPHPTimeToDateTime($subscriptionArray['current_period_start']);
                    $myExpirationDate = util_datetime::getPHPTimeToDateTime($subscriptionArray['current_period_end']);

                    //if old id wasn't free one
                    if($myCurrentSubscriptionTypeId != enum_SubscriptionType::Free){
                        $returnArray['Reason'] = "Update completed successfully.";
                        $LicenseTransactionValuesArray['LicenseTransactionTypeId'] = enum_LicenseTransactionType::Changed;
                        $LicenseTransactionValuesArray['SystemNotes'] = "Subscription plan updated from ".$myCurrentSubscriptionTypeId." to ".$mySubscriptions[0]->SubscriptionTypeId." via ".$inCaller.".";
                        ciborium_stripe::updateForSubscriptionChange($inLicenseId, $mySubscriptions[0]->SubscriptionTypeId, $mySubscribedDate, $myExpirationDate, $inCaller, null, false);
                    }
                    else{
                        ciborium_stripe::updateForSubscriptionChange($inLicenseId, $mySubscriptions[0]->SubscriptionTypeId, $mySubscribedDate, $myExpirationDate, $inCaller, null, true);
                    }

                    ciborium_stripe::LogLicenseTransactionFromSystem($inLicenseId, $LicenseTransactionValuesArray, __METHOD__);

                    //$myArray['ConfirmationNumber'] = rand(1000000, 9000000); //TODO: finish this
                }
                else{
                    $returnArray['Reason'] = "Invalid input";
                    $errorMessage = "Failed to charge subscription for StripeCustomerID (".$inStripeCustomerID.") and StripePlanID (".$mySubscriptions[0]->StripePlanId."). Message: ".$chargeResponse['Reason']." Called by ".$inCaller;
                    util_errorlogging::LogGeneralError(enum_LogType::Blocker, $errorMessage, __METHOD__, __FILE__);
                }

            }
            else{
                $returnArray['Reason'] = "Subscription already active for module selection.";
            }
        }
        elseif(!$isNewSubscriber && ciborium_stripe::isCancelSubscription($inModuleArray)){
            //cancel subscription
            $cancelResponse = stripe_charger::cancelSubscription($inStripeCustomerID);
            if($cancelResponse['Result']){
                $LicenseTransactionValuesArray = array(
                    'LicenseTransactionTypeId' => enum_LicenseTransactionType::Cancelled,
                    'SystemNotes' => "Subscription cancelled."
                );

                $customerArray = util_general::getProtectedValue($cancelResponse['Customer'], "_values");
                $subscriptionArray = util_general::getProtectedValue($customerArray['subscription'], "_values");

                //significant datetimes
                $mySubscribedDate = util_datetime::getPHPTimeToDateTime($subscriptionArray['current_period_start']);
                $myExpirationDate = util_datetime::getPHPTimeToDateTime($subscriptionArray['current_period_end']);
                $myCancelledDate = util_datetime::getPHPTimeToDateTime($subscriptionArray['canceled_at']);

                ciborium_stripe::updateForSubscriptionChange($inLicenseId, enum_SubscriptionType::Free, $mySubscribedDate, $myExpirationDate, $inCaller, $myCancelledDate, false);
                ciborium_stripe::LogLicenseTransactionFromSystem($inLicenseId, $LicenseTransactionValuesArray, __METHOD__);

                $returnArray['Result'] = 1;
                $returnArray['CancelledSubscription'] = 1;

                //TODO: maybe send email one day...
            }
            else{
                $returnArray['Reason'] = "Tried to cancel, but there was response was not completed.";
                if($cancelResponse['StripeException'] !== null){
                    $errorMessage = "While cancelling subscription for StripeCustomerID ".$inStripeCustomerID.", Stripe threw an exception. Message: ".$cancelResponse['Reason']." . Called by ".$inCaller;
                }
                else{
                    $errorMessage = "While cancelling for StripeCustomerID ".$inStripeCustomerID.", the cancellation was not completed. Message: ".$cancelResponse['Reason']." . Called by ".$inCaller;
                }
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $returnArray['Reason'] = "Invalid input";
            $errorMessageAppend = array();
            foreach($inModuleArray as $key => $value){
                array_push($errorMessageAppend, $key."==".$value);
            }
            $errorMessage = "";
            if (count($mySubscriptions) > 1){
                $errorMessage = "Multiple subscriptions found for inModuleArray (".implode(", ", $errorMessageAppend)."). Called by ".$inCaller;
            }
            else{
                $errorMessage = "Subscription not found for inModuleArray (".implode(", ", $errorMessageAppend)."). Called by ".$inCaller;
            }
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
        }

        return $returnArray;
    }

    /**
     * Library: chargePerpetualSubscription()
     * Charges/updates perpetual subscription for a user
     *
     * @param $inStripeCustomerID
     * @param $inLicenseId
     * @param $inAccountUserId
     * @param $inAmountToCharge
     * @param $inCaller
     * @param null $inPromotionCode
     * @return array
     *      Reason
     *      Result
     */
    public static function chargePerpetualSubscription($inStripeCustomerID, $inModuleArray, $inSubscriptionTypeId, $inLicenseId, $inAccountUserId, $inCaller, $inAmountToCharge = null, $inPromotionCode = null){
        $returnArray = array(
            'Reason' => "",
            'Result' => 0
        );

        //Verify inputs
        if(!validate::isNotNullOrEmpty_String(trim($inStripeCustomerID))){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inStripeCustomerID ".$inStripeCustomerID." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(2, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        /*$checkModuleSelectionResponse = ciborium_stripe::checkValidModuleSelectionArray($inModuleArray);
        if(!$checkModuleSelectionResponse['Result']){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inModuleArray from caller ".$inCaller.". ".$checkModuleSelectionResponse['Reason'].".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }*/
        if(!validate::isNotNullOrEmpty_String(trim($inSubscriptionTypeId)) || !validate::tryParseInt($inSubscriptionTypeId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inSubscriptionTypeId ".$inSubscriptionTypeId." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inLicenseId)) || !validate::tryParseInt($inLicenseId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inLicenseId ".$inLicenseId." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inAccountUserId)) || !validate::tryParseInt($inAccountUserId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inAccountUserId ".$inAccountUserId." from caller ".$inCaller.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        //Set amount to charge
        $amountToCharge = 0; //in cents
        if(validate::isNotNullOrEmpty_String(trim($inAmountToCharge)) && validate::tryParseInt($inAmountToCharge) && (int)$inAmountToCharge >= 0 ){
            $amountToCharge = (int)$inAmountToCharge;
        }

        //If we are going to have different types of perpetual charges, will have to make this be looked up
        $subscriptionTypeId = enum_SubscriptionType::FAR_AUD_BEC_REG_Perpetual;
        $stripePlanId = "Perpetual - All Four Sections";
        $amountToCharge = 5000;


        $response = ciborium_stripe::chargeOneTimePurchase($inStripeCustomerID, $inLicenseId, $inAccountUserId, $amountToCharge, $inCaller, $inPromotionCode);

        if($response['Result']){

            //base notes to log
            $LicenseTransactionValuesArray = array(
                'LicenseTransactionTypeId' => enum_LicenseTransactionType::Subscribed,
                'SystemNotes' => "New subscription (".$stripePlanId.") subscribed via ".$inCaller."."
            );

            $currentSubscriptionTypeId = (int)$inSubscriptionTypeId;

            //significant datetimes
            $mySubscribedDate = util_datetime::getDateTimeNow();
            $myExpirationDate = util_datetime::getDateStringToDateTime("2037-12-31");

            //Check to see if they are a first time subscriber
            if($currentSubscriptionTypeId != enum_SubscriptionType::Free){
                $returnArray['Reason'] = "Update completed successfully.";
                $LicenseTransactionValuesArray['LicenseTransactionTypeId'] = enum_LicenseTransactionType::Changed;
                $LicenseTransactionValuesArray['SystemNotes'] = "Subscription plan updated from ".$currentSubscriptionTypeId." to ".$subscriptionTypeId." via ".$inCaller.".";
                ciborium_stripe::updateForSubscriptionChange($inLicenseId, $subscriptionTypeId, $mySubscribedDate, $myExpirationDate, $inCaller, null, false);
            }
            else{
                ciborium_stripe::updateForSubscriptionChange($inLicenseId, $subscriptionTypeId, $mySubscribedDate, $myExpirationDate, $inCaller, null, true);
            }

            //log notes
            ciborium_stripe::LogLicenseTransactionFromSystem($inLicenseId, $LicenseTransactionValuesArray, __METHOD__);
        }
        else{
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = "Failed to charge invoice amount (".$inAmountToCharge." cents) for StripeCustomerID (".$inStripeCustomerID."). Message: ".$response['Reason']." Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Critical, $errorMessage, __METHOD__, __FILE__);
        }

        return $returnArray;

    }

    /**
     * @param $inStripeCustomerID
     * @param $inLicenseId
     * @param $inAccountUserId
     * @param $inAmount; int (cents for USD)
     * @param $inCaller
     * @param null $inPromotionCode
     * @return array
     */
    public static function chargeOneTimePurchase($inStripeCustomerID, $inLicenseId, $inAccountUserId, $inAmount, $inCaller, $inPromotionCode = null)
    {
        $returnArray = array(
            'Reason' => "",
            'Result' => 0
        );

        //if promotion code was passed in
        $promotionId = null;
        $promotion = null;
        $accountUserToPromotion = null;
        if(validate::isNotNullOrEmpty_String($inPromotionCode)){
            $promoCodeResultArray = ciborium_promotion::validatePromotionCodeForUser($inPromotionCode, $inAccountUserId, $inCaller);
            if($promoCodeResultArray['Result']){
                //TODO: may have to check against subscription array one day
                $promotionId = $promoCodeResultArray['PromotionId'];
                $promotion = ciborium_promotion::getPromotionById($promotionId);
                $accountUserToPromotion = ciborium_promotion::getAccountUserToPromotion($promotionId, $inAccountUserId, $inCaller)[0];
            }
            else{
                $returnArray['Reason'] = "Promo code invalid";
                return $returnArray;
            }
        }



        //Verify inputs
        if(!validate::isNotNullOrEmpty_String(trim($inStripeCustomerID))){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inStripeCustomerID ".$inStripeCustomerID.".";
            util_errorlogging::LogBrowserError(2, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        if(!validate::isNotNullOrEmpty_String(trim($inLicenseId)) || !validate::tryParseInt($inLicenseId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inLicenseId ".$inLicenseId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        if(!validate::isNotNullOrEmpty_String(trim($inAccountUserId)) || !validate::tryParseInt($inAccountUserId) ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inAccountUserId ".$inAccountUserId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        if(!validate::isNotNullOrEmpty_String(trim($inAmount)) || !validate::tryParseInt($inAmount) || (int)$inAmount <= 0 ){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inAmount ".$inAmount.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        //charge it
        $amountToCharge = (int)$inAmount;
        $chargeResponse = stripe_charger::chargeOneTimePurchase($inStripeCustomerID, $amountToCharge, "usd");
        if($chargeResponse['Result']){
            $returnArray['Result'] = 1;
            $returnArray['Reason'] = "Charge completed successfully.";

            $chargeArray = util_general::getProtectedValue($chargeResponse['Charge'], "_values");
            $cardArray = util_general::getProtectedValue($chargeArray['card'], "_values");

            //log it
            $LicenseTransactionValuesArray['LicenseTransactionTypeId'] = enum_LicenseTransactionType::OneTimeCharge;
            $LicenseTransactionValuesArray['SystemNotes'] = "Credit card ".$cardArray['type']." ".$cardArray['last4']." charged for amount ".$chargeArray['amount']." (cents) via ".$inCaller.". StripeChargeId ".$chargeArray['id'];
            ciborium_stripe::LogLicenseTransactionFromSystem($inLicenseId, $LicenseTransactionValuesArray, __METHOD__);
        }
        else{
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = "Failed to charge for StripeCustomerID (".$inStripeCustomerID.") and Amount (".$inAmount."). Message: ".$chargeResponse['Reason']." Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
        }

        return $returnArray;
    }

    /**
     * Library: removeCreditCard()
     * Removes credit card for the license in system and in Stripe
     *
     * @param $inLicenseId
     * @param $inStripeCustomerId
     * @param $inStripeCreditCardId
     * @param $inCaller
     * @return array
     */
    public static function removeCreditCard($inLicenseId, $inStripeCustomerId, $inStripeCreditCardId, $inCaller){
        $myArray = array(
            'Reason' => "",
            'Result' => 0
        );

        //Verify inputs
        if(!validate::tryParseInt($inLicenseId)){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for LicenseId ".(string)$inLicenseId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inStripeCustomerId))){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for StripeCustomerId ".$inStripeCustomerId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inStripeCreditCardId))){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for StripeCreditCardId ".$inStripeCreditCardId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }

        if(!account::verifyLicenseExistsById($inLicenseId)){
            $myArray['Reason'] = "License does not exist for user.";
            $errorMessage = $myArray['Reason']." LicenseID was ".(string)$inLicenseId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }

        $removeCardResponse = stripe_charger::removeCreditCard($inStripeCustomerId, $inStripeCreditCardId);

        if($removeCardResponse['Result']){
            $myArray['Reason'] = $removeCardResponse['Reason'];
            $updateResult = account::updateLicenseForCreditCardRemoval($inLicenseId, __METHOD__);

            if($updateResult){
                $myArray['Result'] = 1;
                $ValuesArray = array(
                    'SystemNotes' => $removeCardResponse['Reason'],
                    'UserNotes' => "-None Entered-"
                );
                account::insertIntoLicenseTransactionHistory($inLicenseId, enum_LicenseTransactionType::Changed, $ValuesArray, $inCaller);
            }
            else{
                $myArray['Reason'] .= " However, credit card data was not removed from system.";
                $errorMessage = $myArray['Reason']." . Called by ".$inCaller;
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $myArray['Reason'] = "Error removing credit card.";
            if($removeCardResponse['StripeException'] != null){
                $errorMessage = "Error removing credit card. Stripe threw an exception. Message: ".$removeCardResponse['Reason']." . Called by ".$inCaller;
            }
            else{
                $errorMessage = "Error removing credit card. The removal was not completed. Message: ".$removeCardResponse['Reason']." . Called by ".$inCaller;
            }
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
        }


        return $myArray;
    }

    /**
     * Library: addCreditCard()
     * Adds credit card for the license in system and in Stripe
     *
     * @param $inLicenseId
     * @param $inStripeCustomerId
     * @param $inStripeCCToken
     * @param $inCaller
     * @return array
     */
    public static function addCreditCard($inLicenseId, $inStripeCustomerId, $inStripeCCToken, $inCaller){
        $myArray = array(
            'Reason' => "",
            'Result' => 0
        );

        //Verify inputs
        if(!validate::tryParseInt($inLicenseId)){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for LicenseId ".(string)$inLicenseId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inStripeCustomerId))){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for StripeCustomerId ".$inStripeCustomerId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inStripeCCToken))){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for StripeCreditCardId ".$inStripeCCToken.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }
        if(!account::verifyLicenseExistsById($inLicenseId)){
            $myArray['Reason'] = "License does not exist for user.";
            $errorMessage = $myArray['Reason']." LicenseID was ".(string)$inLicenseId.".";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }

        $myLicense = account::getLicenseById($inLicenseId);
        if(!validate::isNotNullOrEmpty_String($myLicense[0]->StripeCreditCardId)){
            $addCreditCardResponse = stripe_charger::addCreditCard($inStripeCustomerId, $inStripeCCToken);

            if($addCreditCardResponse['Result']){
                $myArray['Reason'] = $addCreditCardResponse['Reason'];
                //$customerArray = stripe_charger::getCustomerArrayFromStripeCustomerObject($addCreditCardResponse['Customer']);
                $cardArray = stripe_charger::getCardArrayFromStripeCustomerObject($addCreditCardResponse['Customer']);

                $updateResult = account::updateLicenseForCreditCardAddition($inLicenseId, $cardArray['id'], $cardArray['type'], $cardArray['last4'], util_datetime::getDateStringToDateTime($cardArray['exp_month']."/1/".$cardArray['exp_year']), $inCaller);

                if($updateResult){
                    $myArray['Result'] = 1;
                    $ValuesArray = array(
                        'SystemNotes' => "Credit card added.",
                        'UserNotes' => "-None Entered-"
                    );
                    account::insertIntoLicenseTransactionHistory($inLicenseId, enum_LicenseTransactionType::Changed, $ValuesArray, $inCaller);
                }
                else{
                    $myArray['Reason'] .= " However, credit card data was not added to our system for LicenseId ".(string)$inLicenseId.". StripeCardID was ".$cardArray['id'];
                    $errorMessage = $myArray['Reason']." . Called by ".$inCaller;
                    util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
                }
            }
            else{
                $myArray['Reason'] = "Error adding credit card.";
                if($addCreditCardResponse['StripeException'] != null){
                    $errorMessage = "Error adding credit card. Stripe threw an exception. Message: ".$addCreditCardResponse['Reason']." . Called by ".$inCaller;
                }
                else{
                    $errorMessage = "Error adding credit card. The removal was not completed. Message: ".$addCreditCardResponse['Reason']." . Called by ".$inCaller;
                }
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $errorMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $myArray['Reason'] = "There is a credit card on file already.";
        }

        return $myArray;
    }

    /**
     * Ciborium Library: getPublicSubscriptionOptions()
     * Gets valid subscription options for charging users
     *
     * @return array
     */
    public static function getPublicSubscriptionOptions(){

        $returnArray = ciborium_question::returnValidPublicSubscriptionTypeArray();
        unset($returnArray['Free']);

        return $returnArray;
    }

    public static function checkValidModuleSelectionArray($inArray){
        $returnArray = array(
            'Result' => false,
            'Reason' => ""
        );

        $myBool = true;
        if(validate::isNotNullOrEmpty_Array($inArray)){
            //get enums to compare against
            //$refHelper = new ReflectionClass("enum_SectionType");
            //$ValidSectionTypeIds =  $refHelper->getConstants();

            //AUD
            if(isset($inArray['AUD'])){
                if(validate::isNotNullOrEmpty_String($inArray['AUD']) || validate::tryParseInt((string)$inArray['AUD'])){
                    if(!validate::isValidBool((string)$inArray['AUD'])){
                        $returnArray['Reason'] .= "AUD was not a valid boolean. ";
                        $myBool = false;
                    }
                }
                else{
                    $returnArray['Reason'] .= "AUD was null or empty string. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "AUD was not set. ";
                $myBool = false;
            }

            //BEC
            if(isset($inArray['BEC'])){
                if(validate::isNotNullOrEmpty_String($inArray['BEC']) || validate::tryParseInt((string)$inArray['BEC'])){
                    if(!validate::isValidBool((string)$inArray['BEC'])){
                        $returnArray['Reason'] .= "BEC was not a valid boolean. ";
                        $myBool = false;
                    }
                }
                else{
                    $returnArray['Reason'] .= "BEC was null or empty string. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "BEC was not set. ";
                $myBool = false;
            }

            //REG
            if(isset($inArray['REG'])){
                if(validate::isNotNullOrEmpty_String($inArray['REG']) || validate::tryParseInt((string)$inArray['REG'])){
                    if(!validate::isValidBool((string)$inArray['REG'])){
                        $returnArray['Reason'] .= "REG was not a valid boolean. ";
                        $myBool = false;
                    }
                }
                else{
                    $returnArray['Reason'] .= "REG was null or empty string. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "REG was not set. ";
                $myBool = false;
            }

            //FAR
            if(isset($inArray['FAR'])){
                if(validate::isNotNullOrEmpty_String($inArray['FAR']) || validate::tryParseInt((string)$inArray['FAR'])){
                    if(!validate::isValidBool((string)$inArray['FAR'])){
                        $returnArray['Reason'] .= "FAR was not a valid boolean. ";
                        $myBool = false;
                    }
                }
                else{
                    $returnArray['Reason'] .= "FAR was null or empty string. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "FAR was not set. ";
                $myBool = false;
            }

        }
        else{
            $returnArray['Reason'] .= "Filter array was empty. ";
            $myBool = false;
        }


        $returnArray['Result'] = $myBool;

        return $returnArray;

    }

    public static function isCancelSubscription($inModuleArray){
        $myBool = false;

        if(!in_array(1, $inModuleArray)){
            $myBool = true;
        }

        return $myBool;
    }

    public static function isSubscriberNew($inLicenseId){
        $myHistory = account::getLicenseToSectionTypeForUser($inLicenseId);

        if(count($myHistory) == 0){
            return true;
        }
        else{
            return false;
        }
    }

    public static function findStandardSubscriptionByModuleSelection($inModuleArray){
        $selectArray = array("SubscriptionTypeId", "HasFARModule", "HasAUDModule", "HasBECModule", "HasREGModule", "StripePlanId");
        $whereClause = "IsSubscription = 1 AND IsPublic = 1 AND IsActive = 1 AND SubscriptionTypeId NOT IN (1, 15) AND ";
        $whereArray = array();

        //remove key(s)
        unset($inModuleArray['HasIsOneTimeSubscription']);
        unset($inModuleArray['IsOneTimeSubscription']);

        foreach($inModuleArray as $key => $value){
            array_push($whereArray, $key."=".$value);
        }
        $whereClause .= implode(" AND ", $whereArray);
        $orderBy = "SubscriptionTypeId ASC";
        $limit = "1";
        $preparedArray = null;

        return database::select("SubscriptionType", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getSubscriptionById($inSubscriptionTypeId){
        $selectArray = null;
        $whereClause = "SubscriptionTypeId = '".$inSubscriptionTypeId."'";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("SubscriptionType", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function LogLicenseTransactionFromSystem($inLicenseId, $inValuesArray, $inLastModifiedBy){
        if(!validate::tryParseInt($inLicenseId)){
            $ErrorMessage = "LicenseId was not an integer.";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }
        if(!validate::isNotNullOrEmpty_Array($inValuesArray)){
            $ErrorMessage = "Values array was empty or null";
            util_errorlogging::LogGeneralError(enum_LogType::Critical, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }

        $inputArray = array(
            'LicenseId' => ':LicenseId',
            'LicenseTransactionTypeId' => ':LicenseTransactionTypeId',
            'SystemNotes' => ':SystemNotes',
            'UserNotes' => ':UserNotes',
            'LastModifiedBy' => ':LastModifiedBy',
            'DateCreated' => ':DateCreated',
            'CreatedBy' => ':CreatedBy'
        );
        $insertPrepare = array(
            ':LicenseId' => $inLicenseId,
            ':LicenseTransactionTypeId' => $inValuesArray['LicenseTransactionTypeId'],
            ':SystemNotes' => $inValuesArray['SystemNotes'],
            ':UserNotes' => '-None Entered-',
            ':LastModifiedBy' => $inLastModifiedBy,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inLastModifiedBy
        );
        $insertColumns = array_keys($inputArray);
        $insertValues = array_values($inputArray);

        return database::insert("LicenseTransactionHistory", $insertColumns, $insertValues, $insertPrepare, __METHOD__);

    }

    public static function updateForSubscriptionChange($inLicenseId, $inNewSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inLastModifiedBy, $inDateCancellation = "", $inIsNewSubscriber = false){

        $returnArray = array(
            'Result' => 1,
            'Reason' => ""
        );

        //check if License and subscription exists first
        if(account::verifyLicenseExistsById($inLicenseId) && stripe_charger::verifySubscriptionExistsById($inNewSubscriptionTypeId)){
            //License
            if($inIsNewSubscriber){
                account::updateLicenseForSubscriptionChange($inLicenseId, $inNewSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inLastModifiedBy, null, true);
                //License to section type
                account::updateLicenseToSectionTypeForUser($inLicenseId, $inNewSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inLastModifiedBy, null);

            }
            elseif(validate::isNotNullOrEmpty_String($inDateCancellation)){
                account::updateLicenseForSubscriptionChange($inLicenseId, $inNewSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inLastModifiedBy, $inDateCancellation, false);
                account::updateLicenseToSectionTypeForUser($inLicenseId, $inNewSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inLastModifiedBy, $inDateCancellation);
            }
            else{
                account::updateLicenseForSubscriptionChange($inLicenseId, $inNewSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inLastModifiedBy, null, false);
                account::updateLicenseToSectionTypeForUser($inLicenseId, $inNewSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inLastModifiedBy, null);
            }

//            if(!$LTSTUpdateResult || !$LicenseUpdateResult){
//                $returnArray['Result'] = 0;
//                $returnArray['Reason'] = "License or subscription update failed.";
//                $ErrorMessage = "LicenseUpdate (".$inLicenseId.") or SubscriptionTypeID (".$inNewSubscriptionTypeId.") was/were not found.";
//                util_errorlogging::LogGeneralError(enum_LogType::Normal, $ErrorMessage, __METHOD__, __FILE__);
//            }

        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "License or subscription not found.";
            $ErrorMessage = "LicenseID (".$inLicenseId.") or SubscriptionTypeID (".$inNewSubscriptionTypeId.") was/were not found.";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $ErrorMessage, __METHOD__, __FILE__);
        }

        return $returnArray;
    }

    public static function applyCouponToCustomer($inStripeCustomerId, $inStripeCouponId, $inCaller){

        $returnArray = array(
            'Result' => 0,
            'Reason' => ""
        );

        $applyDiscountResponse = stripe_charger::applyCouponToCustomer($inStripeCustomerId, $inStripeCouponId, $inCaller);
        if($applyDiscountResponse['Result']){
            $returnArray['Result'] = 1;
        }
        else{
            $returnArray['Reason'] = "There was an issue applying the promo code.";
            if($applyDiscountResponse['StripeException'] != null){
                $stripeException = $applyDiscountResponse['StripeException'];
                $HTTP_errorCode = $stripeException->http_status;
                $errorArray = $stripeException->json_body['error'];
                $message = $applyDiscountResponse['Reason']." HTTP status was ".$HTTP_errorCode." and error code was ".$errorArray['code'].".";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $message, __METHOD__, __FILE__);
            }
            else{
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $applyDiscountResponse['Reason'], __METHOD__, __FILE__);
            }
        }


        return $returnArray;
    }
}


?>