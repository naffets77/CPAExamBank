<?php
require_once(realpath(__DIR__)."/config.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(service_configuration::$environment_librarypath."/validate.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(service_configuration::$ciborium_librarypath."/ciboriumlib_account.php");

/**
 * Ciborium account service
 */

class service_account{

    //service name
    static $service = "service_account";

    /**
     * Service: login()
     * Logs a user into either the Public or Admin portal
     *
     * POST Input:
     *      email
     *      password
     *
     * @return array
     *      Reason
     *      Result
     *      Account (stdClass)
     *      Hash
     *      Licenses (stdClass)
     *      UserSettings (stdClass)
     */
    static function login(){

        // Verify Inputs
        $email =  validate::requirePostField('email', self::$service, __FUNCTION__);
        $password = validate::requirePostField('password', self::$service, __FUNCTION__);

        $checkValueArray = array(
            "email" => $email,
            "password" => $password
        );

        if(in_array(null, $checkValueArray))
        {
            $inEmail = $email == null ? "null" : $email;
            $inPassword = $password == null ? "null" : $password;
            $inMessage = "Missing one or more POST variables in ".self::$service."::".__FUNCTION__." . Email was: ".$inEmail." and Password was: ".$inPassword;
            util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);
            return array(
                'Result' => 0,
                'Reason' => "Missing required variable(s)"
            );
        }

        $result = ciboriumlib_account::login($email, $password);

        return $result;
    }


    /**
     * Service: logout()
     * Logs a user out of either the Public or Admin portal
     *
     * POST Input: <none>
     *
     * @return array
     *      Reason
     *      Result
     */
    static function logout(){
        $myArray = array(
            "Reason" => "",
            "Result" => 1
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            $myArray['Result'] = (int)ciboriumlib_account::logout();
        }
        else{
            $myArray['Reason'] = "Was already logged out";
        }

        return $myArray;
    }


    /**
     * Service: refreshLogin()
     * Refreshes user's client session for either the Public or Admin portal
     *
     * POST Input: <none>
     *
     * @return array
     *      Reason
     *      Result
     *      Account (stdClass)
     *      Hash
     *      Licenses (stdClass)
     *      UserSettings (stdClass)
     */
    static function refreshLogin(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){

            $myAccount = ciboriumlib_account::refreshLogin($_SESSION['Account']->LoginName, $_SESSION['Account']->LoginPassword, $_SESSION['Account']->AccountUserId, $_SESSION['AccountHash']);
            return $myAccount;
        }
        else{

            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }
    }


    /**
     * Service: updateLoginEmail()
     * Updates user's login name (email)
     *
     * POST Input:
     *      Hash
     *      email
     *
     * @return array
     *      Reason
     *      Result
     */
    static function updateLoginEmail(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            $accountUserId = $_SESSION['Account']->AccountUserId;
            $email =  validate::requirePostField('email', self::$service, __FUNCTION__);
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "email" => $email,
                "Hash" => $hash,
                'hashCheckResult' => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult'])
            {
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables OR hash check failed in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){
                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);
                $myArray['Reason'] = "Missing required variable(s)";
                return $myArray;
            }

            $myResultArray = ciboriumlib_account::updateLoginEmail($accountUserId, $email);
            return $myResultArray;
        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }

    }


    /**
     * Service: updateContactEmail()
     * Updates user's login name (email)
     *
     * POST Input:
     *      Hash
     *      email
     *
     * @return array
     *      Reason
     *      Result
     */
    static function updateContactEmail(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            $accountUserId = $_SESSION['Account']->AccountUserId;
            $email =  validate::requirePostField('email', self::$service, __FUNCTION__);
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "email" => $email,
                "Hash" => $hash,
                'hashCheckResult' => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult'])
            {
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables OR hash check failed in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){
                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);
                $myArray['Reason'] = "Missing required variable(s)";
                return $myArray;
            }

            $myResultArray = ciboriumlib_account::updateContactEmail($accountUserId, $email);
            return $myResultArray;
        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }

    }


    /**
     * Service: registerNewUser()
     * Creates a new user from Public portal
     *
     * POST Input:
     *      Hash
     *      password
     *
     * @return array
     *      Reason
     *      Result
     *      %array(Account items)
     */
    static function registerNewUser(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        $email = validate::requirePostField('email', self::$service, __FUNCTION__);
        $password = validate::requirePostField('password', self::$service, __FUNCTION__);
        $sections= validate::requirePostField('sections', self::$service, __FUNCTION__);
        $referralSource = validate::requirePostField('referralSource', self::$service, __FUNCTION__);
		$promoCode = validate::requirePostField('promoCode', self::$service, __FUNCTION__);

        $checkValueArray = array(
            "email" => $email,
            "password" => $password,
            'sections' => $sections,
            'referralSource' => $referralSource,
			'promoCode' => $promoCode
        );

        if(in_array(null, $checkValueArray))
        {
            $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
            $inMessage = "Missing one or more POST variables ".self::$service."::".__FUNCTION__." . ";
            $inMessageAppend = array();
            foreach($inCheckValueArray as $key => $value){
                array_push($inMessageAppend, $key."==".$value);
            }
            $inMessage .= implode(", ", $inMessageAppend);
            util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);
            $myArray['Reason'] = "Missing required variable(s)";
            return $myArray;
        }

        $myJSONObject = json_decode($sections, true);
        if(json_last_error() == JSON_ERROR_NONE){
            $sectionsArray = $myJSONObject[0];
            $myResultArray = ciboriumlib_account::registerNewUser($email, $password, $sectionsArray, $referralSource, $promoCode, __METHOD__);
            return $myResultArray;
        }
        else{
            $myArray['Reason'] = "One or more required variables were invalid";
            return $myArray;
        }

    }

    /**
     * Service: updatePassword()
     * Updates the user's password
     *
     * POST Input:
     *      Hash
     *      password
     *
     * @return array
     *      Reason
     *      Result
     */
    static function updatePassword(){

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );


        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            // Verify Inputs
            $currentPassword = validate::requirePostField('password', self::$service, __FUNCTION__);
            $confirmPassword =  validate::requirePostField('confirmPassword', self::$service, __FUNCTION__);
            $newPassword =  validate::requirePostField('newPassword', self::$service, __FUNCTION__);
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                'password' => $currentPassword,
                "newPassword" => $newPassword,
                "confirmPassword" => $confirmPassword,
                "Hash" => $hash,
                'hashCheckResult' => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult'])
            {
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables OR hash check failed in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){
                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);
                $myArray['Reason'] = "Missing requried variable(s)";
                return $myArray;
            }

            if($_SESSION['Account']->LoginPassword == $currentPassword && $newPassword == $confirmPassword){
                $result = ciboriumlib_account::updateLoginPassword($_SESSION['Account']->AccountUserId, $confirmPassword, __METHOD__);
                return $result;
            }
            else{
                $myArray['Reason'] = "Passwords did not match.";
                return $myArray;
            }
        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }

    }

    /**
     * for the special case of resetting password. Must be logged in and have "reset password" status on account
     *
     * @return array
     */
    static function resetPassword(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );


        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            // Verify Inputs
            $confirmPassword =  validate::requirePostField('confirmPassword', self::$service, __FUNCTION__);
            $newPassword =  validate::requirePostField('newPassword', self::$service, __FUNCTION__);
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "newPassword" => $newPassword,
                "confirmPassword" => $confirmPassword,
                "Hash" => $hash,
                'hashCheckResult' => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult'])
            {
                $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
                $inMessage = "Missing one or more POST variables OR hash check failed in ".self::$service."::".__FUNCTION__." . ";
                $inMessageAppend = array();
                foreach($inCheckValueArray as $key => $value){
                    array_push($inMessageAppend, $key."==".$value);
                }
                $inMessage .= implode(", ", $inMessageAppend);
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);
                $myArray['Reason'] = "Missing requried variable(s)";
                return $myArray;
            }

            if($_SESSION['Account']->IsPasswordReset && $_SESSION['Account']->ResetHashKey != null){
                if($newPassword == $confirmPassword){
                    $oldPassword = (string)$_SESSION['Account']->LoginPassword;
                    $passwordUpdateResult = ciboriumlib_account::updateLoginPassword($_SESSION['Account']->AccountUserId, $confirmPassword, __METHOD__);
                    if($passwordUpdateResult['Result']){
                        $resetPasswordStatusUpdateResult = ciboriumlib_account::setPasswordResetStatus($_SESSION['Account']->AccountUserId, false, null, null, __METHOD__);

                        if($resetPasswordStatusUpdateResult['Result']){
                            return ciboriumlib_account::refreshLogin($_SESSION['Account']->LoginName, $_SESSION['Account']->LoginPassword, $_SESSION['Account']->AccountUserId, $hash);
                        }
                        else{
                            ciboriumlib_account::updateLoginPassword($_SESSION['Account']->AccountUserId, $oldPassword, __METHOD__);
                            $myArray['Reason'] = "Error updating password";
                        }
                        return $myArray;
                    }
                    else{
                        $myArray['Reason'] = "Error updating password";
                        return $myArray;
                    }

                }
                else{
                    $myArray['Reason'] = "Passwords did not match.";
                    return $myArray;
                }
            }
            else{
                $inMessage = "IsPasswordReset was not true or ResetHashKey was null for AccountUserId ".$_SESSION['Account']->AccountUserId.".";
                util_errorlogging::LogBrowserError(3, $inMessage, __METHOD__, __FILE__);
                $myArray['Reason'] = "Reset link has expired.";
                return $myArray;
            }
        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }

    }

    /**
     * Service: sendResetPasswordEmail()
     * Sends reset password email to user
     *
     * @return array
     */
    static function sendResetPasswordEmail(){

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        // Verify Inputs
        $email =  validate::requirePostField('email', self::$service, __FUNCTION__);

        $checkValueArray = array(
            "email" => $email
        );

        if(in_array(null, $checkValueArray))
        {
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

        $result = ciboriumlib_account::sendResetPasswordEmail($email, __METHOD__);

        return $result;
    }

    static function loginFromResetURL(){

        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        // Verify Inputs
        $email =  validate::requirePostField('email', self::$service, __FUNCTION__);
        $hashkey = validate::requirePostField('hashkey', self::$service, __FUNCTION__);

        $checkValueArray = array(
            "email" => $email,
            "hashkey" => $hashkey
        );

        if(in_array(null, $checkValueArray))
        {
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

        $myAccountResponse = ciboriumlib_account::getAccountUserByLoginName($email, __METHOD__);
        if($myAccountResponse['Result']){
            $myAccount = $myAccountResponse['Account'];
            if($myAccount->ResetHashKey != null && $myAccount->ResetHashKey == $hashkey){

                //TODO: compare against UTC time
                if(time() < util_datetime::getDateTimeToPHPTime($myAccount->DateResetHashExpires)){
                    return ciboriumlib_account::login($myAccount->LoginName, $myAccount->LoginPassword);
                }
                else{
                    $myArray['Reason'] = "Reset link expired.";
                    return $myArray;
                }
            }
            else{
                $myArray['Reason'] = "Variables mismatching.";
                return $myArray;
            }
        }
        else{
            $myArray['Reason'] = "User not found.";
            return $myArray;
        }
    }

}
