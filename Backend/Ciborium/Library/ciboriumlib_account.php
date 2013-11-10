<?php
require_once(realpath(__DIR__)."/config.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/account.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_email.php");
/**
 * Account library for Ciborium project
 */

class ciboriumlib_account{

    /**
     * @param $inEmail
     * @param $inMD5Password
     * @return array
     */
    public static function login($inEmail, $inMD5Password){
        if(validate::emailAddress($inEmail)){
            $myAccountObjectArray = account::login($inEmail, $inMD5Password, true);
            if($myAccountObjectArray['Result']){
                //add/remove entries
                $myAccountObjectArray['Account'] = self::returnAccountDTO($myAccountObjectArray['Account']);
                $myAccountObjectArray['Licenses'] = self::returnLicenseDTO($myAccountObjectArray['Licenses']);
                $myAccountObjectArray['UserSettings'] = self::returnAccountSettingsDTO($myAccountObjectArray['UserSettings']);
            }

            return $myAccountObjectArray;
        }
        else{
            return array();
        }
    }


    /**
     * @return bool
     */
    public static function logout(){

        return account::logout();

    }


    /**
     * @param $inLoginName
     * @param $inMD5Password
     * @param $inAccountUserId
     * @param $inAccountHash
     * @return array
     */
    public static function refreshLogin($inLoginName, $inMD5Password, $inAccountUserId, $inAccountHash){

        if (!isset($_SESSION)){
            session_start();
        }

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        $myAccountHash = account::createAccountHash($inAccountUserId); //see account::login() for hash logic

        if($myAccountHash == $inAccountHash){
            $myAccount = self::login($inLoginName, $inMD5Password);
            return $myAccount;
        }
        else{
            $myArray['Reason'] = "Variable mismatch";
            return $myArray;
        }

    }


    /**
     * @param $inService
     * @param $inFunction
     * @return bool
     */
    public static function checkValidLogin($inService, $inFunction, $inDebug = false){

        if (!isset($_SESSION)){
            session_start();
        }

        if(validate::requireSessionField('Account', $inService, $inFunction, false)){
            if(validate::requireSessionField('AccountHash', $inService, $inFunction, false)){
                if(validate::requireSessionField('AccountSettings', $inService, $inFunction, false) ){
                    //Licenses check
                    if(validate::requireSessionField('Licenses', $inService, $inFunction, false)){
                        //Timeout check
                        if(validate::requireSessionField('Timeout', $inService, $inFunction, false) && validate::requireSessionField('LastRefreshed', $inService, $inFunction, false) ){
                            $SessionTimeout = (int)$_SESSION['LastRefreshed'] + (int)$_SESSION['Timeout'];
                            if(time() < $SessionTimeout){

                                //update last refreshed and timeout
                                $_SESSION['LastRefreshed'] = time();
                                $_SESSION['Timeout'] = ciborium_configuration::$timeout;

                                return true;
                            }
                            else{
                                if($inDebug){
                                    $Message = "Session was timed out.";
                                    util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
                                }
                                return false;
                            }
                        }
                        else{
                            if($inDebug){
                                $Message = "Session Timeout or LastRefreshed was not set.";
                                util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
                            }
                            return false;
                        }
                    }
                    else{
                        if($inDebug){
                            $Message = "Session Licenses was not set.";
                            util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
                        }
                        return false;
                    }
                }
                else{
                    if($inDebug){
                        $Message = "Session AccountSettings was not set.";
                        util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
                    }
                    return false;
                }
            }
            else{
                if($inDebug){
                    $Message = "Session AccountHash was not set.";
                    util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
                }
                return false;
            }
        }
        else{
            if($inDebug){
                $Message = "Session Account was not set.";
                util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
            }
            return false;
        }

    }

    /**
     * @param $inAccountUserId
     * @param $inNewEmail
     * @return array
     */
    public static function updateLoginEmail($inAccountUserId, $inNewEmail, $inUpdateContactEmail = true){

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        $myEmail = trim($inNewEmail);
        if(validate::tryParseInt($inAccountUserId) && validate::isNotNullOrEmpty_String($myEmail)){
            //lookup email first
            if(!account::verifyAccountUserExistsByLoginName($myEmail)){
                //check if user exists now
                if(account::verifyAccountUserExistsById($inAccountUserId)){
                    $updateContactEmail = (bool)$inUpdateContactEmail;
                    $result = account::updateLoginName($inAccountUserId, $myEmail, __METHOD__, $updateContactEmail);
                    $myArray['Reason'] = $result === true ? "" : "Error updating entry";
                    $myArray['Result'] = $result === true ? 1 : 0;
                    //update session
                    if($result && isset($_SESSION['Account'])){
                        $_SESSION['Account']->LoginName = $myEmail;
                        if($updateContactEmail){
                            $_SESSION['Account']->ContactEmail = $myEmail;
                        }
                    }
                }
                else{
                    $myArray['Reason'] = "User not found";
                }
            }
            else{
                $myArray['Reason'] = "Email already in use.";
            }
        }
        else{
            $myArray['Reason'] = "User or new email was invalid.";
        }

        return $myArray;
    }

    /**
     * @param $inAccountUserId
     * @param $inNewEmail
     * @return array
     */
    public static function updateContactEmail($inAccountUserId, $inNewEmail){

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        //Verify inputs
        if(!validate::isNotNullOrEmpty_String(trim($inNewEmail))){
            $myArray['Reason'] = "Invalid input";
            $errorMessage = $myArray['Reason']." for Email address. Was null or empty.";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $myArray;
        }

        $myEmail = trim($inNewEmail);
        if(validate::tryParseInt($inAccountUserId) && validate::emailAddress($myEmail)){
            //lookup email first
            if(!account::verifyAccountUserExistsByLoginName($myEmail)){
                //check if user exists now
                if(account::verifyAccountUserExistsById($inAccountUserId)){
                    $result = account::updateContactEmail($inAccountUserId, $myEmail, __METHOD__);
                    $myArray['Reason'] = $result === true ? "" : "Error updating entry";
                    $myArray['Result'] = $result === true ? 1 : 0;
                    //update session
                    if($result && isset($_SESSION['Account'])){
                        $_SESSION['Account']->ContactEmail = $myEmail;
                    }
                }
                else{
                    $myArray['Reason'] = "User not found";
                }
            }
            else{
                $myArray['Reason'] = "Email already in use.";
            }
        }
        else{
            $myArray['Reason'] = "User or new email was invalid.";
        }

        return $myArray;
    }


    public static function updateLoginPassword($inAccountUserId, $inNewMD5Password, $inCaller){

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        $myPassword = trim($inNewMD5Password);
        if(validate::tryParseInt($inAccountUserId) && validate::isNotNullOrEmpty_String($myPassword)){
            //check md5 hash
            if(validate::isValidMd5($myPassword)){
                //check if user exists now
                if(account::verifyAccountUserExistsById($inAccountUserId)){
                    $result = account::updateLoginPassword($inAccountUserId, $myPassword, $inCaller);
                    $myArray['Reason'] = $result === true ? "" : "Error updating entry";
                    $myArray['Result'] = $result === true ? 1 : 0;
                    //update session
                    if($myArray['Result'] && isset($_SESSION['Account'])){
                        $_SESSION['Account']->LoginPassword = $myPassword;
                    }
                }
                else{
                    $myArray['Reason'] = "User not found";
                }
            }
            else{
                $myArray['Reason'] = "Password is invalid";
            }
        }
        else{
            $myArray['Reason'] = "User or new password was invalid.";
        }

        return $myArray;
    }

    /**
     * @param $inEmail
     * @param $inMD5Password
     * @param $inSectionsArray
     * @param $inReferralSource
     * @param $inCaller
     * @return array
     */
    public static function registerNewUser($inEmail, $inMD5Password, $inSectionsArray, $inReferralSource, $inPromoCode, $inCaller){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'Login' => ""
        );

        //Verify inputs
        if(!validate::isNotNullOrEmpty_String($inEmail)){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for Email address. Was null or empty.";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String(trim($inMD5Password))){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for password. Was null or empty";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_Array($inSectionsArray)){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for Sections array. Was null or empty.";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String($inReferralSource)){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for referral source. Was null or empty.";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        if(!validate::isNotNullOrEmpty_String($inPromoCode)){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for referral source. Was null or empty.";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }
        //logout to clear any errant session
        ciboriumlib_account::logout();
        $myEmail = trim($inEmail);
        $myPassword = trim($inMD5Password);
        if(validate::emailAddress($myEmail) && validate::isValidMd5($inMD5Password)){
            //lookup email first
            if(!account::verifyAccountUserExistsByLoginName($myEmail)){
                //check if sections array is valid
                $sectionsResult = self::checkValidSectionsPreferenceArray($inSectionsArray);
                if($sectionsResult['Result']){
                    $IsInterestedInFAR = $inSectionsArray['FAR'] == "1" ? true : false;
                    $IsInterestedInAUD = $inSectionsArray['AUD'] == "1" ? true : false;
                    $IsInterestedInBEC = $inSectionsArray['BEC'] == "1" ? true : false;
                    $IsInterestedInREG = $inSectionsArray['REG'] == "1" ? true : false;

                    $AccountUserId = account::insertIntoAccountUser($myEmail, $myPassword, $inReferralSource, $inCaller);
                    if($AccountUserId > 0){
                        $ValuesArray = array(
                            'FAR' => $IsInterestedInFAR,
                            'AUD' => $IsInterestedInAUD,
                            'BEC' => $IsInterestedInBEC,
                            'REG' => $IsInterestedInREG
                        );

                        $AccountUserSettingsId = account::insertIntoAccountUserSettings($AccountUserId, $ValuesArray, $inCaller);
                        if($AccountUserSettingsId > 0){

                            $AccountUserLicenseId = account::insertIntoLicenseDefault($AccountUserId, $inCaller);
                            if($AccountUserLicenseId > 0){
                                $ValuesArray = array(
                                    'SystemNotes' => "Auto Created when user was registered by ".$inCaller."()",
                                    'UserNotes' => "-None Entered-"
                                );
                                $AccountUserLicenseTransactionHistoryId = account::insertIntoLicenseTransactionHistory($AccountUserLicenseId, enum_LicenseTransactionType::Assigned, $ValuesArray, $inCaller);
                                if($AccountUserLicenseTransactionHistoryId > 0){
                                    $returnArray['Result'] = 1;
                                    $returnArray['Login'] = $myEmail;
                                    //email new user registration email
                                    ciborium_email::sendEmail_NewUserRegistered($AccountUserId, $inCaller, true);

                                    return account::login($myEmail, $myPassword, true);
                                }
                                else{
                                    $returnArray['Reason'] = "Error creating new user";
                                }
                            }
                            else{
                                $returnArray['Reason'] = "Error creating new user";
                            }

                        }
                        else{
                            $returnArray['Reason'] = "Error creating new user";
                        }
                    }
                    else{
                        $returnArray['Reason'] = "Error creating new user";
                    }
                }
                else{
                    $returnArray['Reason'] = "Some data was invalid";
                }
            }
            else{
                $returnArray['Reason'] = "Email already in use.";
            }
        }
        else{
            $returnArray['Reason'] = "Email and/or password was invalid.";
        }


        return $returnArray;
    }

    /**
     * @param $inEmail
     * @param $inCaller
     * @return array
     */
    public static function sendResetPasswordEmail($inEmail, $inCaller){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        $myEmail = trim($inEmail);
        if(validate::isNotNullOrEmpty_String($myEmail) && validate::emailAddress($myEmail)){

            if(account::verifyAccountUserExistsByLoginName($myEmail)){
                $myAccountUser = account::getAccountUserByEmail($myEmail)[0];

                //TODO: check against UTC time
                if($myAccountUser->DateResetHashExpires == null || time() > util_datetime::getDateTimeToPHPTime($myAccountUser->DateResetHashExpires)){
                    $currentTime = time();
                    $myNewPassword = md5("ciborium".(string)$myAccountUser->AccountUserId.(string)$currentTime);
                    $myHashKey = md5($myNewPassword);

                    $resetHashDuration = ciborium_configuration::$hashexpiration;
                    $resetStatusSet = self::setPasswordResetStatus($myAccountUser->AccountUserId, true, $myHashKey, util_datetime::getPHPTimeToDateTime(time() + $resetHashDuration), $inCaller." via ".__METHOD__);

                    if($resetStatusSet['Result']){
                        $passwordUpdated = account::updateLoginPassword($myAccountUser->AccountUserId, $myNewPassword, $inCaller." via ".__METHOD__, true);
                        if($passwordUpdated){
                            //send email
                            $EmailSent = ciborium_email::sendEmail_PasswordReset($myAccountUser->AccountUserId, $inCaller);
                            if($EmailSent){
                                $myArray['Result'] = 1;
                                $myArray['Reason'] = "Password reset successfully";
                            }
                            else{
                                $myArray['Reason'] = "Email not sent, but password was reset.";
                            }
                        }
                        else{
                            $Message = "Login password was not updated for reset password call. Called by ".$inCaller.".";
                            util_errorlogging::LogGeneralError(enum_LogType::Normal, $Message, __METHOD__, __FILE__);
                            $myArray['Reason'] = "Password not reset";
                        }
                    }
                    else{
                        $Message = "Reset status was not updated for reset password call. Called by ".$inCaller.".";
                        util_errorlogging::LogGeneralError(enum_LogType::Normal, $Message, __METHOD__, __FILE__);
                        $myArray['Reason'] = "Password not reset";
                    }
                }
                else{
                    $myArray['Reason'] = "Password reset has already been requested recently.";
                }
            }
            else{
                $myArray['Reason'] = "User not found.";
            }
        }
        else{
            $myArray['Reason'] = "Invalid input.";
        }

        return $myArray;
    }


    public static function setPasswordResetStatus($inAccountUserId, $inIsPasswordReset, $inResetHashKey, $inResetHashExpirationDateTime, $inCaller){
        $returnArray = array(
            'Result' => false,
            'Reason' => ""
        );

        $result = account::setPasswordResetStatus($inAccountUserId, $inIsPasswordReset, $inResetHashKey, $inResetHashExpirationDateTime, $inCaller);

        if($result){
            $returnArray['Result'] = true;
        }
        else{
            $Message = "Password reset status was not set for AccountUserId ".$inAccountUserId.". Called by ".$inCaller.".";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $Message, __METHOD__, __FILE__);
            $returnArray['Reason'] = "Status not set.";
        }

        return $returnArray;
    }

    public static function getAccountUserByLoginName($inLoginName, $inCaller){

        $returnArray = array(
            "Reason" => "",
            "Result" => 0,
            'Account' => null
        );

        if(validate::emailAddress($inLoginName) && account::verifyAccountUserExistsByLoginName($inLoginName)){
            $myAccount = account::getAccountUserByEmail($inLoginName)[0];
            $returnArray['Result'] = 1;
            $returnArray['Account'] = $myAccount;
        }
        else{
            $Message = "AccountUser not found for LoginName ".$inLoginName.". Called by ".$inCaller.".";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $Message, __METHOD__, __FILE__);
            $returnArray['Reason'] = "User name invalid or does not exist.";
        }
        return $returnArray;
    }

    public static function getLicenseToSectionTypeForUser($inLicenseId, $inCaller){
        $returnArray = array(
            "Reason" => "",
            "Result" => 0,
            'Sections' => null
        );

        if(validate::tryParseInt($inLicenseId)){
            $returnArray['Sections'] = account::getLicenseToSectionTypeForUser($inLicenseId);
        }
        else{
            $Message = "LicenseId not valid. Called by ".$inCaller.".";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $Message, __METHOD__, __FILE__);
            $returnArray['Reason'] = "Invalid input";
        }


        return $returnArray;
    }

    public static function checkValidLicenseToSectionType($inLicenseId, $inSectionTypeId, $inCaller){
        $returnArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(validate::tryParseInt($inLicenseId) && validate::tryParseInt($inSectionTypeId)){
            $sections = account::getLicenseToSectionTypeForUser($inLicenseId);
            if(count($sections) > 0){
                $isValid = false;
                $SectionTypeId = (int)$inSectionTypeId;
                foreach ($sections as $stdKey => $object) {
                    if((int)$object->SectionTypeId == $SectionTypeId){
                        $isValid = true;
                        break;
                    }
                }
                $returnArray['Result'] = $isValid ? 1 : 0;

            }
            else{
                $Message = "No LicenseToSectionType found for LicenseId (".$inLicenseId."). Called by ".$inCaller.".";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $Message, __METHOD__, __FILE__);
                $returnArray['Reason'] = "Invalid input";
            }
        }
        else{
            $Message = "LicenseId (".$inLicenseId.") or SectionTypeId (".$inSectionTypeId.") not valid. Called by ".$inCaller.".";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $Message, __METHOD__, __FILE__);
            $returnArray['Reason'] = "Invalid input";
        }

        return $returnArray;
    }

    //*******************************
    // custom validation functions
    //*******************************

    public static function checkValidSectionsPreferenceArray($inSectionsArray){
        $returnArray = array(
            'Result' => true,
            'Reason' => ""
        );

        $validValues = array("1", "0");

        if(is_array($inSectionsArray) && !empty($inSectionsArray)){
            //FAR
            if(isset($inSectionsArray['FAR'])){
                if(!in_array($inSectionsArray['FAR'], $validValues)){
                    $returnArray['Result'] = false;
                    $returnArray['Reason'] .= "FAR was invalid. ";
                }
            }
            else{
                $returnArray['Result'] = false;
                $returnArray['Reason'] .= "FAR was missing. ";
            }

            //AUD
            if(isset($inSectionsArray['AUD'])){
                if(!in_array($inSectionsArray['AUD'], $validValues)){
                    $returnArray['Result'] = false;
                    $returnArray['Reason'] .= "AUD was invalid. ";
                }
            }
            else{
                $returnArray['Result'] = false;
                $returnArray['Reason'] .= "AUD was missing. ";
            }

            //BEC
            if(isset($inSectionsArray['BEC'])){
                if(!in_array($inSectionsArray['BEC'], $validValues)){
                    $returnArray['Result'] = false;
                    $returnArray['Reason'] .= "BEC was invalid. ";
                }
            }
            else{
                $returnArray['Result'] = false;
                $returnArray['Reason'] .= "BEC was missing. ";
            }

            //REG
            if(isset($inSectionsArray['REG'])){
                if(!in_array($inSectionsArray['REG'], $validValues)){
                    $returnArray['Result'] = false;
                    $returnArray['Reason'] .= "REG was invalid. ";
                }
            }
            else{
                $returnArray['Result'] = false;
                $returnArray['Reason'] .= "REG was missing. ";
            }
        }
        else{
            $returnArray['Result'] = false;
            $returnArray['Reason'] = "Sections array was invalid";
        }

        return $returnArray;
    }


    //*******************************
    // DTOs
    //*******************************

    /**
     * @param $inAccount
     * @return null
     */
    private static function returnAccountDTO($inAccount){

        if(isset($inAccount)){
            $myAccount = $inAccount;
            //unset items
            //unset($myAccount->AccountUserId);
            //unset($myAccount->LoginPassword);

            unset($myAccount->DateDeactivated);
            if($myAccount->IsEnabled && !$myAccount->IsSuspended && !$myAccount->IsDeactivated){
                $myAccount->Active = "1";
            }
            else{
                $myAccount->Active = "0";
            }
            unset($myAccount->IsEnabled);
            unset($myAccount->IsPasswordReset);
            unset($myAccount->ResetHashKey);
            unset($myAccount->DateResetHashExpires);
            unset($myAccount->IsSuspended);
            unset($myAccount->IsDeactivated);
            unset($myAccount->DeactivatedBy);
            unset($myAccount->DateLastModified);
            unset($myAccount->LastModifiedBy);
            unset($myAccount->CreatedBy);

            return $myAccount;
        }
        else{
            $errorMessage = "Account object was empty or not set.";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            return null;
        }

    }


    /**
     * @param $inLicense
     * @return null
     */
    private static function returnLicenseDTO($inLicense){

        if(isset($inLicense)){
            $myLicense = $inLicense;
            //unset items

            unset($myLicense->DateAssigned);
            unset($myLicense->DateLastModified);
            unset($myLicense->LastModifiedBy);
            unset($myLicense->DateCreated);
            unset($myLicense->CreatedBy);

            if($myLicense->Enabled && !$myLicense->Suspended && !$myLicense->Deactivated){
                $myLicense->Active = "1";
            }
            else{
                $myLicense->Active = "0";
            }
            unset($myLicense->Enabled);
            unset($myLicense->Suspended);
            unset($myLicense->Deactivated);
            unset($myLicense->LicenseId);
            unset($myLicense->Code);
            unset($myLicense->DateDeactivated);

            return $myLicense;
        }
        else{
            $errorMessage = "License object was empty or not set.";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            return null;
        }

    }


    /**
     * @param $inAccountSettings
     * @return null
     */
    private static function returnAccountSettingsDTO($inAccountSettings){

        if(isset($inAccountSettings)){
            $myAccountSettings = $inAccountSettings;
            //unset items
            unset($myAccountSettings->DateLastModified);
            unset($myAccountSettings->LastModifiedBy);
            unset($myAccountSettings->CreatedBy);
            unset($myAccountSettings->DateCreated);
            unset($myAccountSettings->AccountUserSettingsId);

            return $myAccountSettings;
        }
        else{
            $errorMessage = "Account settings object was empty or not set.";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            return null;
        }

    }

}