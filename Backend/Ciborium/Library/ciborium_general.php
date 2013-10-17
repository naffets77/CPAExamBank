<?php
require_once(realpath(__DIR__)."/config.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/question.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_email.php");

class ciborium_general{

    public static function submitContactUsRequest($inRequestArray, $inCaller){
        $myResponse = new genericRequestResponse();

        //validation
        $isValidRequestCheck = self::checkIsValidContactUsRequest($inRequestArray);
        if($isValidRequestCheck->Result && $isValidRequestCheck->Code == enum_ResponseCodes::Successful){
            $myResponse->Reason = $isValidRequestCheck->Reason;
            $myResponse->Code = $isValidRequestCheck->Code;

            $clientBrowserInfoArray = util_browser::getClientBrowserInfo();
            $JSEnabled = (bool)$clientBrowserInfoArray['BrowserJSEnabled'] ? "true" : "false";
            $emailArray = array(
                'ContactEmail' => $inRequestArray['contactEmail'],
                'Reason' => htmlspecialchars($inRequestArray['reason']),
                'Message' => htmlspecialchars($inRequestArray['message']),
                'IPAddress' => util_browser::getClientIPAddress(),
                'Browser' => $clientBrowserInfoArray['BrowserName'],
                'Version' => $clientBrowserInfoArray['BrowserVersion'],
                'Platform' => $clientBrowserInfoArray['BrowserPlatform'],
                'CSSVersion' => $clientBrowserInfoArray['BrowserCSSVersion'],
                'JSEnabled' => $clientBrowserInfoArray['BrowserJSEnabled']
            );

            //TODO: create table to store this
            /*$inputArray = array(
                'LoginName' => ':LoginName',
                'LoginPassword' => ':LoginPassword',
                'ContactEmail' => ':ContactEmail',
                'LastModifiedBy' => ':LastModifiedBy',
                'DateCreated' => ':DateCreated',
                'CreatedBy' => ':CreatedBy'
            );
            $insertPrepare = array(
                ':LoginName' => $myEmail,
                ':LoginPassword' => $myPassword,
                ':ContactEmail' => $myEmail,
                ':LastModifiedBy' => $inCaller,
                ':DateCreated' => util_datetime::getDateTimeNow(),
                ':CreatedBy' => $inCaller
            );
            $insertColumns = array_keys($inputArray);
            $insertValues = array_values($inputArray);

            return database::insert("SOMETABLE", $insertColumns, $insertValues, $insertPrepare, __METHOD__);*/

            $wasEmailed = ciborium_email::sendEmail_ContactUs_Company($emailArray, __METHOD__." via ".$inCaller);

            if($wasEmailed){
                $myResponse->Result = true;
            }
            else{
                $myResponse->Code = enum_ResponseCodes::Incomplete;
                $myResponse->Reason .= "Error submitting request.";
                $Message = "Error emailing contact us request. Called by ".$inCaller;
                util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
            }

        }
        else{
            $myResponse->Reason = $isValidRequestCheck->Reason;
            $myResponse->Code = $isValidRequestCheck->Code;
            $Message = "Invalid input for contact us. Called by ".$inCaller.". Invalid input fields were: ".implode(", ", $isValidRequestCheck->InvalidFieldsArray);
            util_errorlogging::LogGeneralError(3, $Message, __METHOD__, __FILE__);
        }

        return $myResponse;
    }


    public static function checkIsValidContactUsRequest($inRequestArray){
        $myResponse = new validationRequestResponse();
        $myResponse->Result = false;

        //contact email
        if(isset($inRequestArray['contactEmail'])){
            if(validate::emailAddress($inRequestArray['contactEmail'])){
                $myResponse->ValidFieldsArray[] = "Contact Email";
            }
            else{
                $myResponse->InvalidFieldsArray[] = "Contact Email";
                $myResponse->Reason .= "Contact Email was invalid. ";
            }
        }
        else{
            $myResponse->InvalidFieldsArray[] = "Contact Email";
            $myResponse->Reason .= "Contact Email was not set. ";
        }

        //reason
        if(isset($inRequestArray['reason'])){
            if(validate::isNotNullOrEmpty_String($inRequestArray['reason'])){
                $myResponse->ValidFieldsArray[] = "reason";
            }
            else{
                $myResponse->InvalidFieldsArray[] = "Reason";
                $myResponse->Reason .= "Reason was empty. ";
            }
        }
        else{
            $myResponse->InvalidFieldsArray[] = "Reason";
            $myResponse->Reason .= "Reason was not set. ";
        }

        //message
        if(isset($inRequestArray['message'])){
            if(validate::isNotNullOrEmpty_String($inRequestArray['message'])){
                $myResponse->ValidFieldsArray[] = "message";
            }
            else{
                $myResponse->InvalidFieldsArray[] = "Message";
                $myResponse->Reason .= "Message was empty. ";
            }
        }
        else{
            $myResponse->InvalidFieldsArray[] = "Message";
            $myResponse->Reason .= "Message was not set. ";
        }

        //set response code
        if(!empty($myResponse->InvalidFieldsArray)){
            $myResponse->Code = enum_ResponseCodes::InvalidInput;
        }
        else{
            $myResponse->Code = enum_ResponseCodes::Successful;
            $myResponse->Result = true;
        }

        return $myResponse;
    }
}

class genericRequestResponse{
    public $Result; //bit
    public $Reason; //string
    public $Code; //int

    function genericRequestResponse(){
        $this->Result = 0;
        $this->Reason = "";
        $this->Code = enum_ResponseCodes::InProgress;
    }

}

class validationRequestResponse extends genericRequestResponse{
    public $ValidFieldsArray; //array(); non-associative
    public $InvalidFieldsArray; //array(); non-associative

    function validationRequestResponse(){
        $this->ValidFieldsArray = array();
        $this->InvalidFieldsArray = array();
    }

}

?>