<?php
require_once(realpath(__DIR__)."/config.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/database.php");

class ciborium_email{

    ///////////////////////////////////
    // Registration / Account User emails
    ///////////////////////////////////

    /**
     * Sends new user registered email
     * @param int $inAccountUserId
     * @param string $inCaller usually __METHOD__
     * @return bool result of PHP mail() function
     */
    public static function sendEmail_NewUserRegistered($inAccountUserId, $inCaller, $inUseTodayAsSentDate = true){

        //validation
        if(!validate::tryParseInt($inAccountUserId)){
            $inMessage = "inAccountUserId was not an integer in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }


        $Accounts = account::getAccountUserById((int)$inAccountUserId);
        if(count($Accounts) == 1){
            $myAccount = $Accounts[0];

            $myQueue = new emailQueue();
            $myQueue->ToEmail = $myAccount->ContactEmail;
            $myQueue->FromEmail = "registration@cpaexambank.com";
            $myQueue->CCList = "";
            $myQueue->BCCList = "cpabccbin@gmail.com";
            $myQueue->Subject = "CPAExambank.com Registration Completed";
            $myQueue->Body = self::getEmailTemplateAsString(ciborium_configuration::$ciborium_emailtemplatepath."/".enum_EmailTemplates::NewUserRegistered);
            $myQueue->Attachments = array();

            //string replacements in template
            //As of 7/21/2013, First and Last name could be empty
            $myQueue->Body = str_replace("~BASE_URL~", util_general::getBaseURL(), $myQueue->Body);
            $EmailBodyDate = $inUseTodayAsSentDate ? util_datetime::getDateNow() : util_datetime::getDateTimeToDate($myAccount->CreatedDate);
            $myQueue->Body = str_replace("~CURRENT_DATE~", $EmailBodyDate, $myQueue->Body);
            $UserFullName = validate::isNotNullOrEmpty_String(trim($myAccount->FirstName." ".$myAccount->LastName)) ? $myAccount->FirstName." ".$myAccount->LastName : "User";
            $myQueue->Body = str_replace("~USER_FULL_NAME~", $UserFullName, $myQueue->Body);
            $myQueue->Body = str_replace("~USER_LOGIN_NAME~", $myAccount->LoginName, $myQueue->Body);
            //$myQueue->Body = str_replace("~CURRENT_DATETIME~", util_datetime::getDateTimeNow(), $myQueue->Body);

            /*$SectionsInterestedIn = "";
            $SectionsInterestedIn .= "<li style=\"list-style: square; margin-left: 50px;\">AUD</li>";
            $myQueue->Body = str_replace("~SECTIONS_INTERESTED_IN~", $SectionsInterestedIn, $myQueue->Body);*/

            return self::sendEmail($myQueue, __METHOD__, true, false);
        }
        else{
            $inMessage = "Account not found for AccountUserId ".$inAccountUserId." in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Critical, $inMessage, __METHOD__, __FILE__);
            return false;
        }
    }


    public static function sendEmail_PasswordReset($inAccountUserId, $inCaller, $inUseTodayAsSentDate = true){

        //validation
        if(!validate::tryParseInt($inAccountUserId)){
            $inMessage = "inAccountUserId was not an integer in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }


        $Accounts = account::getAccountUserById((int)$inAccountUserId);
        if(count($Accounts) == 1){
            $myAccount = $Accounts[0];

            $myQueue = new emailQueue();
            $myQueue->ToEmail = $myAccount->ContactEmail;
            $myQueue->FromEmail = "registration@cpaexambank.com";
            $myQueue->CCList = "";
            $myQueue->BCCList = "cpabccbin@gmail.com";
            $myQueue->Subject = "CPAExambank.com Password Reset";
            $myQueue->Body = self::getEmailTemplateAsString(ciborium_configuration::$ciborium_emailtemplatepath."/".enum_EmailTemplates::PasswordReset);
            $myQueue->Attachments = array();

            //string replacements in template
            //$myAccountHash = account::createAccountHash($myAccount->AccountUserId);
            $myPasswordResetURL = util_general::getBaseURL()."/#Action=ResetPassword&User=".$myAccount->LoginName."&Hash=".$myAccount->ResetHashKey;
            $myQueue->Body = str_replace("~PASSWORD_RESET_URL~", $myPasswordResetURL , $myQueue->Body);
            $myQueue->Body = str_replace("~BASE_URL~", util_general::getBaseURL(), $myQueue->Body);
            $EmailBodyDate = $inUseTodayAsSentDate ? util_datetime::getDateNow() : util_datetime::getDateTimeToDate($myAccount->CreatedDate);
            $myQueue->Body = str_replace("~CURRENT_DATE~", $EmailBodyDate, $myQueue->Body);
            $UserFullName = validate::isNotNullOrEmpty_String(trim($myAccount->FirstName." ".$myAccount->LastName)) ? $myAccount->FirstName." ".$myAccount->LastName : "User";
            $myQueue->Body = str_replace("~USER_FULL_NAME~", $UserFullName, $myQueue->Body);

            return self::sendEmail($myQueue, __METHOD__, true, false);
        }
        else{
            $inMessage = "Account not found for AccountUserId ".$inAccountUserId." in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Critical, $inMessage, __METHOD__, __FILE__);
            return false;
        }
    }


    public static function sendEmail_ContactUs_Company($inEmailArray, $inCaller){

        //validation
        if(!validate::isNotNullOrEmpty_Array($inEmailArray)){
            $inMessage = "inEmailArray was empty in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        $myQueue = new emailQueue();
        $myQueue->ToEmail = "contactus@cpaexambank.com";
        $myQueue->FromEmail = "no-reply@cpaexambank.com";
        $myQueue->CCList = "";
        $myQueue->BCCList = "cpabccbin@gmail.com";
        $myQueue->Subject = "CPAExambank.com Contact Me";
        $myQueue->Body = self::getEmailTemplateAsString(ciborium_configuration::$ciborium_emailtemplatepath."/".enum_EmailTemplates::ContactUs_Company);
        $myQueue->Attachments = array();

        //string replacements in template
        $myQueue->Body = str_replace("~CURRENT_DATE~", util_datetime::getDateNow(), $myQueue->Body);
        $myQueue->Body = str_replace("~CONTACT_EMAIL~", $inEmailArray['ContactEmail'] , $myQueue->Body);
        $myQueue->Body = str_replace("~REASON~", $inEmailArray['Reason'] , $myQueue->Body);
        $myQueue->Body = str_replace("~MESSAGE~", $inEmailArray['Message'] , $myQueue->Body);
        $myQueue->Body = str_replace("~IP_ADDRESS~", $inEmailArray['IPAddress'] , $myQueue->Body);
        $myQueue->Body = str_replace("~BROWSER_NAME~", $inEmailArray['Browser'] , $myQueue->Body);
        $myQueue->Body = str_replace("~BROWSER_VERSION~", $inEmailArray['Version'] , $myQueue->Body);
        $myQueue->Body = str_replace("~BROWSER_PLATFORM~", $inEmailArray['Platform'] , $myQueue->Body);
        $myQueue->Body = str_replace("~BROWSER_CSSVERSION~", $inEmailArray['CSSVersion'] , $myQueue->Body);
        $myQueue->Body = str_replace("~BROWSER_JSENABLED~", $inEmailArray['JSEnabled'] , $myQueue->Body);

        return self::sendEmail($myQueue, __METHOD__, true, false);
    }

    ///////////////////////////////////
    // Test emails
    ///////////////////////////////////

    /**
     * Sends out a non-HTML test email from the "_Test-NonHTMLEmail.html" file template
     * @return bool result of PHP mail() function
     */
    public static function sendTestEmail(){
        $myQueue = new emailQueue();
        $myQueue->ToEmail = ciborium_configuration::$ciborium_testemailaddress;
        $myQueue->FromEmail = "emailtest@cpaexambank.com";
        $myQueue->CCList = "";
        $myQueue->BCCList = "cpabccbin@gmail.com";
        $myQueue->Subject = "Non-HTML Email Test";
        $myQueue->Body = self::getEmailTemplateAsString(ciborium_configuration::$ciborium_emailtemplatepath."/".enum_EmailTemplates::Test_NonHTML);
        $myQueue->Attachments = array();
        //string replacements in template
        $myQueue->Body = str_replace("~CURRENT_DATETIME~", util_datetime::getDateTimeNow(), $myQueue->Body);

        return self::sendEmail($myQueue, __METHOD__, false, false);
    }

    /**
     * Sends out a HTML test email from the "_Test-HTMLEmail.html" file template
     * @return bool result of PHP mail() function
     */
    public static function sendTestHTMLEmail(){
        $myQueue = new emailQueue();
        $myQueue->ToEmail = ciborium_configuration::$ciborium_testemailaddress;
        $myQueue->FromEmail = "emailtest@cpaexambank.com";
        $myQueue->CCList = "";
        $myQueue->BCCList = "cpabccbin@gmail.com";
        $myQueue->Subject = "HTML Email Test";
        $myQueue->Body = self::getEmailTemplateAsString(ciborium_configuration::$ciborium_emailtemplatepath."/".enum_EmailTemplates::Test_HTML);
        $myQueue->Attachments = array();

        //string replacements in template
        $myQueue->Body = str_replace("~CURRENT_DATE~", util_datetime::getDateNow(), $myQueue->Body);
        $myQueue->Body = str_replace("~CURRENT_DATETIME~", util_datetime::getDateTimeNow(), $myQueue->Body);
        $myQueue->Body = str_replace("~BASE_URL~", util_general::getBaseURL(), $myQueue->Body);

        return self::sendEmail($myQueue, __METHOD__, true, false);
    }





    ///////////////////////////////////
    // HELPERS
    ///////////////////////////////////

    /**
     * Gets email template from file as a string
     * @param string $inFullFileName Real path + file name
     * @return string $myTemplate
     */
    public static function getEmailTemplateAsString($inFullFileName){

        $myTemplate = "-TEMPLATE NOT FOUND-";

        if(file_exists($inFullFileName)){
            $myTemplate = file_get_contents($inFullFileName);
        }

        return $myTemplate;

    }

    /**
     * Sends an email based off of emailQueue object input
     * @param emailQueue $inEmailQueue
     * @param string $inCaller usually __METHOD__
     * @param bool $inIsHTML determines to set HTML header or not
     * @param bool $inOverrideMissingAttachements determines to still send email if there is a missing attachment
     * @return bool result of PHP mail() function
     */
    public static function sendEmail(emailQueue $inEmailQueue, $inCaller, $inIsHTML = true, $inOverrideMissingAttachments = false){

        //TODO: Can use email list if wanted, but just one email for to and from

        //sample header
        /*        $headers .= 'To: Mary <mary@example.com>, Kelly <kelly@example.com>' . "\r\n";
                $headers .= 'From: Birthday Reminder <birthday@example.com>' . "\r\n";
                $headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
                $headers .= 'Bcc: birthdaycheck@example.com' . "\r\n";*/

        //validation on minimum to send an email; expecting valid data this point
        //ToEmail
        if(!validate::isNotNullOrEmpty_String($inEmailQueue->ToEmail)){
            $inMessage = "ToEmail was null or empty in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        //Subject
        if(!validate::isNotNullOrEmpty_String($inEmailQueue->Subject)){
            $inMessage = "Subject was null or empty in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        //Body
        if(!validate::isNotNullOrEmpty_String($inEmailQueue->Body)){
            $inMessage = "Body was null or empty in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        if($inIsHTML){
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        }
        else{
            $headers = "";
        }

        //set headers
        $headers .= "To: ".$inEmailQueue->ToEmail."\r\n";
        $headers .= "From: ".$inEmailQueue->FromEmail."\r\n";
        if(validate::isNotNullOrEmpty_String($inEmailQueue->CCList)){
            $headers .= "Cc: ".$inEmailQueue->CCList."\r\n";
        }
        if(validate::isNotNullOrEmpty_String($inEmailQueue->BCCList)){
            $headers .= "Bcc: ".$inEmailQueue->BCCList."\r\n";
        }

        return mail($inEmailQueue->ToEmail, $inEmailQueue->Subject, $inEmailQueue->Body, $headers);

    }

    /**
     * Gives back a comma separated list of email addresses if valid input. Either inEmailString
     * or inEmailArray must not be null or it will error out
     * @param string $inCaller usually __METHOD__
     * @param null $inEmailString if null, won't check it
     * @param null $inEmailArray if null, won't check it
     * @return array $returnArray An associative array of 'Result' (bool)
     * and 'EmailList' (string)
     */
    public static function returnValidEmailAddressResponse($inCaller, $inEmailString = null, $inEmailArray = null){
        $returnArray = array(
            'Result' => false,
            'EmailList' => ""
        );

        if($inEmailString !== null || $inEmailArray !== null){
            if(is_string($inEmailString)){
                if(validate::isNotNullOrEmpty_String($inEmailString)){
                    if(!validate::emailAddress(trim($inEmailString))){
                        $inMessage = "inEmailString was not valid in ".__METHOD__."() . Called by ".$inCaller;
                        util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                    }
                    else{
                        $returnArray['Result'] = true;
                        $returnArray['EmailList'] = trim($inEmailString);
                    }
                }
                else{
                    $inMessage = "inEmailString was null or empty string in ".__METHOD__."() . Called by ".$inCaller;
                    util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                }
            }
            else if(is_array($inEmailArray)){
                if(validate::isNotNullOrEmpty_Array($inEmailArray)){
                    $EmailList = array();
                    foreach ($inEmailArray as $value) {
                        if(!validate::emailAddress($value)){
                            $inMessage = "An invalid email address was found in inEmailArray in ".__METHOD__."() . Called by ".$inCaller;
                            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                            break;
                        }
                        else{
                            $EmailList[] = trim($value);
                        }
                    }

                    if(count($EmailList) == count($inEmailArray)){
                        $returnArray['Result'] = true;
                        $returnArray['EmailList'] = implode(", ", $EmailList);
                    }

                }
                else{
                    $inMessage = "inEmailArray was null or empty array in ".__METHOD__."() . Called by ".$inCaller;
                    util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                }
            }
            else{
                $inMessage = "inEmailArray was not an expected data type in ".__METHOD__."() . Called by ".$inCaller;
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $inMessage = "inEmailString and inEmailArray were both null in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
        }

        return $returnArray;
    }

}

class emailQueue{
    public $ToEmail; //string
    public $FromEmail; //string
    public $CCList; //array(string emails)
    public $BCCList; //array(string emails)
    public $Subject; //string
    public $Body; //string
    public $Attachments; //array(string paths)

    function emailQueue(){
        $this->ToEmail = "";
        $this->FromEmail = "";
        $this->CCList = "";
        $this->BCCList = "";
        $this->Subject = "";
        $this->Body = "";
        $this->Attachments = array();
    }
}

?>