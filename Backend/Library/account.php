<?php
require_once(realpath(__DIR__)."/config.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_general.php");
include_once(library_configuration::$environment_librarypath."/validate.php");
include_once(library_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/stripe_config.php");
/**
 * this is for the account library.
 * WARNING: AccountUser table must be present in database and have same name fields
 */
class account
{
    /**
     * For logging into the portal
     * @param $email
     * @param $password
     * @return array
     */
    public static function login($email, $password, $inUpdateLastLoggedIn = true){

        $returnArray = array(
            'Result' => 0,
            'Reason' => ""
        );

        $userAccount = null;
        $userAccountSettings = null;
        $accountHash = null;
        $reason = "";

        $Account = self::getAccountUserByEmail($email);

        if(count($Account) == 0){
            $returnArray['Reason'] = "User name/Password combination did not match.";
            return $returnArray;
        }
        else{
            //verify password
            if($password != $Account[0]->LoginPassword){
                $returnArray['Reason'] = "User name/Password combination did not match.";
                return $returnArray;
            }
            else{

                //User password and login match, but need to check for account status
                $statusResult = self::returnAccountStatusForLogin($Account[0]);
                if($statusResult['LogUserIn']){
                    $userAccountForSession = $Account[0];
                    $userAccountForReturn = self::getAccountUserById($userAccountForSession->AccountUserId);
                    $userAccountSettings = self::getAccountUserSettingsById($userAccountForSession->AccountUserId); //duplicating call because of session overwriting issue with reference to var
                    $userAccountLicense = self::getLicensesByAccountUserId($userAccountForSession->AccountUserId, "1");
                    $accountHash = self::createAccountHash($userAccountForSession->AccountUserId); //create hash for stopping middleman attacks
                    $licenseModules = self::getLicenseToSectionTypeForUser($userAccountLicense[0]->LicenseId);
                    $promotionCodeArray = self::getActivePromotionsForUser($userAccountForSession->AccountUserId);

                    // Setup Session
                    $Timeout = library_configuration::$timeout; //in seconds
                    if (!isset($_SESSION)){
                        session_set_cookie_params($Timeout);
                        session_start();
                    }

                    $_SESSION["Account"] = $userAccountForSession;
                    $_SESSION["AccountSettings"] = $userAccountSettings[0];
                    $_SESSION["Licenses"] = $userAccountLicense[0];
                    $_SESSION["AccountHash"] = $accountHash;
                    $_SESSION['Timeout'] = $Timeout;
                    $_SESSION['LastRefreshed'] = time();
                    $_SESSION['Subscriptions'] = $licenseModules;
                    $_SESSION['StripePublicKey'] = stripe_configuration::$public_key;
                    $_SESSION['PromotionCodes'] = $promotionCodeArray;

                    //arrange for UI return
                    $returnArray = self::getLoggedInAccountDataForUI($userAccountForReturn[0], $accountHash, $returnArray['Reason']);;

                }
                else{
                    $returnArray['Reason'] = $statusResult['Reason'];
                }

            }
        }

        // Indicates the user is 'signed on'
        if(isset($_SESSION['Account']) && $inUpdateLastLoggedIn == true){
            self::updateAccountUserLastLogin($userAccountForSession->AccountUserId, __METHOD__);
        }
        return $returnArray;
    }

    /**
     * @return bool
     */
    public static function logout(){
        if (!isset($_SESSION)){
            //echo "Session was still set<br />";
            session_start();
            $_SESSION = array();
            return session_destroy();
        }
        else{
            //echo "Session was already not set<br />";
            $_SESSION = array();
            return true;
        }
    }

    /**
     * @param $inAccountUserId
     * @param $inMethodName
     * @return array
     */
    public static function updateAccountUserLastLogin($inAccountUserId, $inMethodName){

        $myArray = array(
            "Reason" => "",
            "Result" => ""
        );

        if(!validate::tryParseInt($inAccountUserId)){
            $myArray['Reason'] = "ID was not an integer";
            $myArray['Result'] = 0;

            return $myArray;
        }

        $updateArray = array(
            'DateLastLogin' => ':DateLastLogin',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':DateLastLogin' => util_datetime::getDateTimeNow(),
            ':LastModifiedBy' => $inMethodName
        );

        $whereClause = "AccountUserId = ".$inAccountUserId."";
        $myArray['Result'] = database::update("AccountUserSettings", $updateArray, $updatePrepare, $whereClause, __METHOD__);
        return $myArray;
    }


    // Registration Functions


    /**
     * @param $inEmail
     * @param $inPassword
     * @param $inReferralSource
     * @param $inLastModifiedBy
     * @return int|string
     */
    public static function insertIntoAccountUser($inEmail, $inPassword, $inReferralSource, $inLastModifiedBy){

        $myEmail = trim($inEmail);
        $myPassword = trim($inPassword);
        //Validation
        if(!validate::IsNotNullOrEmpty_String($myEmail)){
            $ErrorMessage = "Email was NULL or empty.";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }
        if(!validate::IsNotNullOrEmpty_String($myPassword)){
            $ErrorMessage = "Password was NULL or empty.";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }

        $inputArray = array(
            'LoginName' => ':LoginName',
            'LoginPassword' => ':LoginPassword',
            'ContactEmail' => ':ContactEmail',
            'ReferralSource' => ':ReferralSource',
            'LastModifiedBy' => ':LastModifiedBy',
            'DateCreated' => ':DateCreated',
            'CreatedBy' => ':CreatedBy'
        );
        $insertPrepare = array(
            ':LoginName' => $myEmail,
            ':LoginPassword' => $myPassword,
            ':ContactEmail' => $myEmail,
            ':ReferralSource' => $inReferralSource,
            ':LastModifiedBy' => $inLastModifiedBy,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inLastModifiedBy
        );
        $insertColumns = array_keys($inputArray);
        $insertValues = array_values($inputArray);

        return database::insert("AccountUser", $insertColumns, $insertValues, $insertPrepare, __METHOD__);

    }

    public static function insertIntoAccountUserSettings($inAccountUserId, $inValuesArray, $inLastModifiedBy){
        if(!validate::tryParseInt($inAccountUserId)){
            $ErrorMessage = "AccountUserId was not an integer.";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }
        if(!validate::isNotNullOrEmpty_Array($inValuesArray)){
            $ErrorMessage = "Values array was empty or null";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }

        $inputArray = array(
            'AccountUserId' => ':AccountUserId',
            'IsInterestedInFAR' => ':IsInterestedInFAR',
            'IsInterestedInAUD' => ':IsInterestedInAUD',
            'IsInterestedInBEC' => ':IsInterestedInBEC',
            'IsInterestedInREG' => ':IsInterestedInREG',
            'LastModifiedBy' => ':LastModifiedBy',
            'DateCreated' => ':DateCreated',
            'CreatedBy' => ':CreatedBy'
        );
        $insertPrepare = array(
            ':AccountUserId' => $inAccountUserId,
            ':IsInterestedInFAR' => $inValuesArray['FAR'],
            ':IsInterestedInAUD' => $inValuesArray['AUD'],
            ':IsInterestedInBEC' => $inValuesArray['BEC'],
            ':IsInterestedInREG' => $inValuesArray['REG'],
            ':LastModifiedBy' => $inLastModifiedBy,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inLastModifiedBy
        );
        $insertColumns = array_keys($inputArray);
        $insertValues = array_values($inputArray);

        return database::insert("AccountUserSettings", $insertColumns, $insertValues, $insertPrepare, __METHOD__);
    }

    public static function insertIntoLicenseDefault($inAccountUserId, $inLastModifiedBy){
        if(!validate::tryParseInt($inAccountUserId)){
            $ErrorMessage = "AccountUserId was not an integer.";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }

        $inputArray = array(
            'AccountUserId' => ':AccountUserId',
            'DateAssigned' => ':DateAssigned',
            'LastModifiedBy' => ':LastModifiedBy',
            'DateCreated' => ':DateCreated',
            'CreatedBy' => ':CreatedBy'
        );
        $insertPrepare = array(
            ':AccountUserId' => $inAccountUserId,
            ':DateAssigned' => util_datetime::getDateTimeNow(),
            ':LastModifiedBy' => $inLastModifiedBy,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inLastModifiedBy
        );
        $insertColumns = array_keys($inputArray);
        $insertValues = array_values($inputArray);

        return database::insert("License", $insertColumns, $insertValues, $insertPrepare, __METHOD__);
    }

    public static function insertIntoLicenseTransactionHistory($inLicenseId, $inLicenseTransactionTypeId, $inValuesArray, $inLastModifiedBy){
        if(!validate::tryParseInt($inLicenseId)){
            $ErrorMessage = "LicenseId was not an integer.";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }
        if(!validate::tryParseInt($inLicenseTransactionTypeId)){
            $ErrorMessage = "LicenseTransactionTypeId was not an integer.";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
            return 0;
        }
        if(!validate::isNotNullOrEmpty_Array($inValuesArray)){
            $ErrorMessage = "Values array was empty or null";
            util_errorlogging::LogGeneralError(2, $ErrorMessage, __METHOD__, __FILE__);
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
            ':LicenseTransactionTypeId' => $inLicenseTransactionTypeId,
            ':SystemNotes' => $inValuesArray['SystemNotes'],
            ':UserNotes' => $inValuesArray['UserNotes'],
            ':LastModifiedBy' => $inLastModifiedBy,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inLastModifiedBy
        );
        $insertColumns = array_keys($inputArray);
        $insertValues = array_values($inputArray);

        return database::insert("LicenseTransactionHistory", $insertColumns, $insertValues, $insertPrepare, __METHOD__);
    }

    /**
     * @param $inAccountUserId
     * @param $inEmail
     * @param $inMethodName
     * @return bool|string
     */
    public static function  updateLoginName($inAccountUserId, $inEmail, $inMethodName, $inUpdateContactEmail = true){

        $updateArray = array(
            'LoginName' => ':LoginName',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':LoginName' => $inEmail,
            ':LastModifiedBy' => $inMethodName
        );

        if($inUpdateContactEmail){
            $updateArray['ContactEmail'] = ":ContactEmail";
            $updatePrepare[':ContactEmail'] = $inEmail;
        }

        $whereClause = "AccountUserId = '".$inAccountUserId."'";

        return database::update("AccountUser", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }

    /**
     * @param $inAccountUserId
     * @param $inEmail
     * @param $inMethodName
     * @return bool|string
     */
    public static function  updateContactEmail($inAccountUserId, $inEmail, $inMethodName){

        $updateArray = array(
            'ContactEmail' => ':ContactEmail',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':ContactEmail' => $inEmail,
            ':LastModifiedBy' => $inMethodName
        );

        $whereClause = "AccountUserId = '".$inAccountUserId."'";

        return database::update("AccountUser", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }

    public static function  updateLoginPassword($inAccountUserId, $inNewMD5Password, $inMethodName, $inPasswordReset = false){

        $updateArray = array(
            'LoginPassword' => ':LoginPassword',
            'IsPasswordReset' => ':IsPasswordReset',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':LoginPassword' => $inNewMD5Password,
            ':IsPasswordReset' => $inPasswordReset,
            ':LastModifiedBy' => $inMethodName
        );

        $whereClause = "AccountUserId = '".$inAccountUserId."'";

        return database::update("AccountUser", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }



    //HELPER Functions

    /**
     * Last modified by: MAR - 5/19/2013
     * @param $inEmail
     * @return array|null
     */
    public static function getAccountUserByEmail($inEmail){

        if(validate::emailAddress($inEmail)){
            $selectArray = null;  //or array("field1", "field2"...)
            $whereClause = "LoginName = '".$inEmail."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            return database::select("AccountUser", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
        }
        else{
            return array();
        }

    }

    /**
     * @param $inAccountUserId
     * @return array|null
     */
    public static function getAccountUserById($inAccountUserId){

        if(validate::tryParseInt($inAccountUserId)){
            $selectArray = null;  //or array("field1", "field2"...)
            $whereClause = "AccountUserId = '".$inAccountUserId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            return database::select("AccountUser", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
        }
        else{
            return array();
        }

    }


    /**
     * @param $inAccountUserId
     * @return bool
     */
    public static function verifyAccountUserExistsById($inAccountUserId){
        if(validate::tryParseInt($inAccountUserId)){
            $selectArray = array('AccountUserId');  //or array("field1", "field2"...)
            $whereClause = "AccountUserId = '".$inAccountUserId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            $myAccount = database::select("AccountUser", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);

            if(count($myAccount) > 0){

                return $myAccount[0]->AccountUserId;
            }
            else{

                return 0;
            }

        }
        else{
            $errorMessage = "AccountUserId was not an integer";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            //die("Invalid input.");
            return 0;
        }

    }

    /**
     * @param $inAccountUserId
     * @return bool
     */
    public static function verifyAccountUserExistsByLoginName($inLoginName){
        if(validate::emailAddress($inLoginName)){
            $myEmail = trim($inLoginName);
            $selectArray = array('AccountUserId');  //or array("field1", "field2"...)
            $whereClause = "LoginName = '".$myEmail."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            $myAccount = database::select("AccountUser", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);

            if(count($myAccount) > 0){

                return $myAccount[0]->AccountUserId;
            }
            else{

                return 0;
            }

        }
        else{
            $errorMessage = "LoginEmail was not valid";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            //die("Invalid input.");
            return 0;
        }

    }


    /**
     * Last modified by: MAR - 5/19/2013
     * @param $inAccountObject
     */
    private static function returnAccountStatusForLogin($inAccountObject){
        $myBool = false;
        $myReason = "";

        if(!empty($inAccountObject)){
            if($inAccountObject->IsEnabled && !$inAccountObject->IsSuspended && !$inAccountObject->IsDeactivated)
            {
                $myReason = "Log them in";
                $myBool = true;
            }
            else{
                if(!$inAccountObject->IsEnabled){
                    $myReason = "Account is disabled.";
                }
                elseif($inAccountObject->IsSuspended){
                    $myReason = "Account is suspended.";
                }
                elseif($inAccountObject->IsDeactivated){
                    $myReason = "Account is permanently deactivated.";
                }
                else{
                    $myReason = "didn't reach any of above";
                }
            }
        }
        else{
            $errorMessage = "Account object was empty.";
            util_errorlogging::LogGeneralError(3, $errorMessage, __METHOD__, __FILE__);
        }

        return $result = array(
            'LogUserIn' => $myBool,
            'Reason' => $myReason
        );

    }


    /**
     * Last modified by: MAR - 5/19/2013
     * @param $inAccountUserId
     * @return array|null
     */
    public static function getAccountUserSettingsById($inAccountUserId){
        if(validate::tryParseInt($inAccountUserId)){
            $selectArray = null;  //or array("field1", "field2"...)
            $whereClause = "AccountUserId = '".$inAccountUserId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            return database::select("AccountUserSettings", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
        }
        else{
            return array();
        }

    }


    /**
     * @param $userAccount
     * @param $accountHash
     * @param $reason
     * @return array
     */
    public static function getLoggedInAccountDataForUI($inUserAccount, $inAccountHash, $inReason){

        $License = null;
        $UserSettings = null;

        // Need to load classes and topics
        if(isset($inUserAccount)){
            //User settings
            $UserSettings = self::getAccountUserSettingsById($inUserAccount->AccountUserId);
            //Get License data
            $License = self::getLicensesByAccountUserId($inUserAccount->AccountUserId, "1");
            //license module history
            $LicenseModules = self::getLicenseToSectionTypeForUser($License[0]->LicenseId);
            $LicenseModulesArray = self::returnLicenseToSectionTypeForUI($LicenseModules);

            //promotion codes
            $promotionCodes = self::getActivePromotionsForUser($inUserAccount->AccountUserId);
            $promotionCodeArray = self::returnPromotionsForUI($promotionCodes);


            return array(
                "Reason" => $inReason,
                "Result" => 1,
                "Account" => $inUserAccount,
                "Hash" => $inAccountHash,
                "Licenses" => $License[0], //should only be one right now
                "UserSettings" => $UserSettings[0],
                "Subscriptions" => $LicenseModulesArray,
                "StripePublicKey" => stripe_configuration::$public_key,
                "PromotionCodes" => $promotionCodeArray
            );

        }
        else{
            $errorMessage = "Account object was empty or not set.";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            return array(
                "Reason" => $inReason,
                "Result" => 0,
                "Account" => null,
                "Hash" => $inAccountHash,
                "Licenses" => $License,
                "UserSettings" => $UserSettings
            );
        }
    }

    public static function returnLicenseToSectionTypeForUI($inLicenseToSectionTypesArray){

        $refHelper = new ReflectionClass("enum_SectionType");
        $SectionTypeArray = $refHelper->getConstants();
        $LicenseModulesArray = array();
        foreach($inLicenseToSectionTypesArray as $key => $object){
            $key = array_search($object->SectionTypeId, $SectionTypeArray);

            $LicenseModulesArray[$key] = array(
                'FirstSubscribedDate' => $object->DateCreated,
                'CurrentSubscriptionDate' => $object->DateSubscribed,
                'ExpirationDate' => $object->DateExpiration,
                'CancellationDate' => $object->DateCancellation
            );
        }

        return $LicenseModulesArray;
    }

    public static function returnPromotionsForUI($inPromotionsArray){
        $promotionsArray = array();
        foreach($inPromotionsArray as $key => $object){
            //Only add if "active"
            if(ciborium_promotion::checkStatusOfPromotion($object)['Status'] == enum_PromotionStatus::Active){
                $duration = "";
                if($object->PromotionDuration == -1){
                    $duration = "Permanent";
                }
                if($object->PromotionDuration == 0){
                    $duration = "One-Time";
                }
                if($object->PromotionDuration > 0){
                    $duration = "Monthly";
                }

                $type = "";
                switch($object->PromotionTypeId){
                    case enum_PromotionType::PercentOff_OneTime:
                    case enum_PromotionType::PercentOff_Monthly:
                        $type = "Percent Off";
                        break;
                    case enum_PromotionType::AmountOff_OneTime:
                    case enum_PromotionType::AmountOff_Monthly:
                        $type = "Dollars Off";
                        break;
                    default:
                        $type = "N/A";
                        break;
                }

                $expirationDate = $object->DateExpiration == null ? "Never" : $object->DateExpiration;

                $promotionsArray[] = array(
                    'PromotionCode' => $object->PromotionCode,
                    'Amount' => $object->PromotionValue,
                    'Type' => $type,
                    'Duration' => $duration,
                    'ExpirationDate' => $expirationDate
                );
            }
        }

        return $promotionsArray;
    }

    /**
     * @param $inAccountUserId
     * @return array
     */
    private static function getLicensesByAccountUserId($inAccountUserId, $inLimit){

        $selectArray = null;  //or array("field1", "field2"...)
        $whereClause = "AccountUserId = '".$inAccountUserId."'";
        $orderBy = "";
        $limit = ".$inLimit.";
        $preparedArray = null;

        return database::select("License", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getSubscriptionTypeIdByLicenseId($inLicenseId){
        $selectArray = array("SubscriptionTypeId");
        $whereClause = "LicenseId = '".$inLicenseId."'";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("License", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function createAccountHash($inAccountUserId){
        $salt = library_configuration::$salt;
        return md5($inAccountUserId.$salt);
    }

    public static function getLicenseToSectionTypeForUser($inLicenseId){
        $selectArray = null;
        $whereClause = "LicenseId = ".$inLicenseId."";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("LicenseToSectionType", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getActivePromotionsForUser($inAccountUserId){

        return database::callStoredProcedure("sp_getAccountUserPromotions", array($inAccountUserId), __METHOD__);
    }

    public static function updateLicenseForNewStripeCustomer($inLicenseId, $inStripeCustomerId, $inCCBrand, $inLast4CC, $inCCExpirationDateTime, $inStripeCreditCardId, $inCaller){
        $updateArray = array(
            'StripeCustomerId' => ':StripeCustomerId',
            'StripeCreditCardId' => ':StripeCreditCardId',
            'CC_Brand' => ':CC_Brand',
            'CC_LastFour' => ':CC_LastFour',
            'DateCC_Expiration' => ':DateCC_Expiration',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':StripeCustomerId' => $inStripeCustomerId,
            ':StripeCreditCardId' => $inStripeCreditCardId,
            ':CC_Brand' => $inCCBrand,
            ':CC_LastFour' => $inLast4CC,
            ':DateCC_Expiration' => $inCCExpirationDateTime,
            ':LastModifiedBy' => $inCaller
        );

        $whereClause = "LicenseId = '".$inLicenseId."'";

        return database::update("License", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }

    public static function updateLicenseForSubscriptionChange($inLicenseId, $inSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller, $inDateCancellation = "", $inIsNewSubscriber = false){
        $updateArray = array(
            'SubscriptionTypeId' => ':SubscriptionTypeId',
            'DateSubscribed' => ':DateSubscribed',
            'DateExpiration' => ':DateExpiration',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':SubscriptionTypeId' => $inSubscriptionTypeId,
            ':DateSubscribed' => $inDateSubscribed,
            ':DateExpiration' => $inDateExpiration,
            ':LastModifiedBy' => $inCaller
        );

        if($inIsNewSubscriber){
            $updateArray['DateFirstSubscribed'] = ':DateFirstSubscribed';
            $updatePrepare[':DateFirstSubscribed'] = $inDateSubscribed;
        }

        if($inDateCancellation != ""){
            $updateArray['DateCancellation'] = ':DateCancellation';
            $updatePrepare[':DateCancellation'] = $inDateCancellation;
        }

        $whereClause = "LicenseId = '".$inLicenseId."'";

        return database::update("License", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }

    public static function updateLicenseForCreditCardRemoval($inLicenseId, $inCaller){
        $updateArray = array(
            'StripeCreditCardId' => ':StripeCreditCardId',
            'CC_Brand' => ':CC_Brand',
            'CC_LastFour' => ':CC_LastFour',
            'DateCC_Expiration' => ':DateCC_Expiration',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':StripeCreditCardId' => "",
            ':CC_Brand' => "",
            ':CC_LastFour' => "",
            ':DateCC_Expiration' => null,
            ':LastModifiedBy' => $inCaller
        );

        $whereClause = "LicenseId = '".$inLicenseId."'";

        return database::update("License", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }

    public static function updateLicenseForCreditCardAddition($inLicenseId, $inStripeCreditCardId, $inCCBrand, $inCCLastFour, $inCCExpirationDate, $inCaller){
        $updateArray = array(
            'StripeCreditCardId' => ':StripeCreditCardId',
            'CC_Brand' => ':CC_Brand',
            'CC_LastFour' => ':CC_LastFour',
            'DateCC_Expiration' => ':DateCC_Expiration',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':StripeCreditCardId' => $inStripeCreditCardId,
            ':CC_Brand' => $inCCBrand,
            ':CC_LastFour' => $inCCLastFour,
            ':DateCC_Expiration' => $inCCExpirationDate,
            ':LastModifiedBy' => $inCaller
        );

        $whereClause = "LicenseId = '".$inLicenseId."'";

        return database::update("License", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }

    public static function getAccountUserIdByLicenseId($inLicenseId){

        $selectArray = array("AccountUserId");
        $whereClause = "LicenseId = '".$inLicenseId."'";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("License", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getLicenseById($inLicenseId){
        $selectArray = null;  //or array("field1", "field2"...)
        $whereClause = "LicenseId = '".$inLicenseId."'";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("License", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function verifyLicenseExistsById($inLicenseId){
        if(validate::tryParseInt($inLicenseId)){
            $selectArray = array('LicenseId');  //or array("field1", "field2"...)
            $whereClause = "LicenseId = '".$inLicenseId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            $myAccount = database::select("License", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);

            if(count($myAccount) > 0){

                return true;
            }
            else{

                return false;
            }

        }
        else{
            $errorMessage = "LicenseId was not an integer";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            //die("Invalid input.");
            return false;
        }

    }

    public static function updateLicenseToSectionTypeForUser($inLicenseId, $inSubscriptionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller){

        $myLicenseModuleHistory = account::getLicenseToSectionTypeForUser($inLicenseId);
        $mySubscriptions = ciborium_stripe::getSubscriptionById($inSubscriptionTypeId);
        if(count($myLicenseModuleHistory) >= 0 && count($mySubscriptions) == 1){
            $mySubscription = $mySubscriptions[0];
            //to determine if the section belongs in the LTST table
            $myModuleBitArray = array(
                enum_SectionType::FAR => 0,
                enum_SectionType::AUD => 0,
                enum_SectionType::BEC => 0,
                enum_SectionType::REG => 0
            );

            //set module bit array correctly
            if($mySubscription->HasFARModule){$myModuleBitArray[enum_SectionType::FAR] = 1;}
            if($mySubscription->HasAUDModule){$myModuleBitArray[enum_SectionType::AUD] = 1;}
            if($mySubscription->HasBECModule){$myModuleBitArray[enum_SectionType::BEC] = 1;}
            if($mySubscription->HasREGModule){$myModuleBitArray[enum_SectionType::REG] = 1;}

            if(count($myLicenseModuleHistory) == 0){

                foreach ($myModuleBitArray as $SectionType => $Bool) {
                    //insert all applicable sections
                    if((bool)$Bool){
                        account::insertIntoLicenseToSectionType($inLicenseId, $SectionType, $inDateSubscribed, $inDateExpiration, $inCaller);
                    }
                }

            }
            else{

                //convert to usable array
                $ModuleToObjectArray = array();
                foreach ($myLicenseModuleHistory as $stdIndex => $LMobject) {
                    $ModuleToObjectArray[(int)$LMobject->SectionTypeId] = $LMobject;
                }


                foreach ($myModuleBitArray as $SectionType => $Bit) {
                    $myExpectedSectionTypeId = (int)$SectionType;
                    $myExpectedSection = (bool)$Bit;

                    if(array_key_exists($myExpectedSectionTypeId, $ModuleToObjectArray)){
                        if($myExpectedSection){
                            //uncancel it and update
                            //account::updateLicenseToSectionType($LMobject->LicenseToSectionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller, null );
                            account::updateLicenseToSectionType($ModuleToObjectArray[$myExpectedSectionTypeId]->LicenseToSectionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller." via ".__METHOD__, null );
                        }
                        else{
                            if($ModuleToObjectArray[$myExpectedSectionTypeId]->DateCancellation == null){
                                //cancel it and update
                                //account::updateLicenseToSectionType($LMobject->LicenseToSectionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller, util_datetime::getDateTimeNow() );
                                account::updateLicenseToSectionType($ModuleToObjectArray[$myExpectedSectionTypeId]->LicenseToSectionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller." via ".__METHOD__, util_datetime::getDateTimeNow() );
                            }
                            else{
                                //do nothing
                            }
                        }
                    }
                    else{
                        if($myExpectedSection){
                            account::insertIntoLicenseToSectionType($inLicenseId, $SectionType, $inDateSubscribed, $inDateExpiration, $inCaller." via ".__METHOD__);
                        }
                        else{
                            //do nothing
                        }
                    }
                }
            }
        }

        else{
            $ErrorMessage = "LicenseToSectionType and/or SubscriptionType invalid for LicenseID (".$inLicenseId.") and SubscriptionTypeID (".$inSubscriptionTypeId."). Called by ".$inCaller;
            util_errorlogging::LogGeneralError(3, $ErrorMessage, __METHOD__, __FILE__);
        }

    }

    public static function insertIntoLicenseToSectionType($inLicenseId, $inSectionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller){

        $inputArray = array(
            'LicenseId' => ':LicenseId',
            'SectionTypeId' => ':SectionTypeId',
            'DateSubscribed' => ':DateSubscribed',
            'DateExpiration' => ':DateExpiration',
            'LastModifiedBy' => ':LastModifiedBy',
            'DateCreated' => ':DateCreated',
            'CreatedBy' => ':CreatedBy'
        );
        $insertPrepare = array(
            ':LicenseId' => $inLicenseId,
            ':SectionTypeId' => $inSectionTypeId,
            ':DateSubscribed' => $inDateSubscribed,
            ':DateExpiration' => $inDateExpiration,
            ':LastModifiedBy' => $inCaller,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inCaller
        );
        $insertColumns = array_keys($inputArray);
        $insertValues = array_values($inputArray);

        return database::insert("LicenseToSectionType", $insertColumns, $insertValues, $insertPrepare, __METHOD__);

    }

    public static function updateLicenseToSectionType($inLicenseToSectionTypeId, $inDateSubscribed, $inDateExpiration, $inCaller, $inDateCancellation = ""){

        $updateArray = array(
            'DateSubscribed' => ':DateSubscribed',
            'DateExpiration' => ':DateExpiration',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':DateSubscribed' => $inDateSubscribed,
            ':DateExpiration' => $inDateExpiration,
            ':LastModifiedBy' => $inCaller
        );

        if($inDateCancellation == null || $inDateCancellation != ""){
            $updateArray['DateCancellation'] = ':DateCancellation';
            $updatePrepare[':DateCancellation'] = $inDateCancellation;
        }

        $whereClause = "LicenseToSectionTypeId = '".$inLicenseToSectionTypeId."'";

        return database::update("LicenseToSectionType", $updateArray, $updatePrepare, $whereClause, __METHOD__);

    }

    public static function setPasswordResetStatus($inAccountUserId, $inIsPasswordReset, $inResetHashKey, $inResetExpiresDate, $inMethodName){
        $updateArray = array(
            'IsPasswordReset' => ':IsPasswordReset',
            'ResetHashKey' => ':ResetHashKey',
            'DateResetHashExpires' => ':DateResetHashExpires',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':IsPasswordReset' => $inIsPasswordReset,
            ':ResetHashKey' => $inResetHashKey,
            ':DateResetHashExpires' => $inResetExpiresDate,
            ':LastModifiedBy' => $inMethodName
        );

        $whereClause = "AccountUserId = '".$inAccountUserId."'";

        return database::update("AccountUser", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }

    public static function getPasswordResetStatus($inAccountUserId){
        $selectArray = array('IsPasswordReset');  //or array("field1", "field2"...)
        $whereClause = "AccountUserId = '".$inAccountUserId."'";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("AccountUser", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

}
