<?php
require_once(realpath(__DIR__)."/config.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(service_configuration::$environment_librarypath."/validate.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_question.php");
include_once(service_configuration::$ciborium_librarypath."/ciboriumlib_account.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_general.php");

class service_general{

    //service name
    static $service = "service_general";

    static function submitContactUsRequest(){
        $returnArray = array(
            "Reason" => "",
            "Result" => 0
        );

        $contactEmail = validate::requirePostField('contactEmail', self::$service, __FUNCTION__);
        $reason = validate::requirePostField('reason', self::$service, __FUNCTION__);
        $message = validate::requirePostField('message', self::$service, __FUNCTION__);

        $checkValueArray = array(
            "contactEmail" => $contactEmail,
            "reason" => $reason,
            "message" => $message
        );

        if(in_array(null, $checkValueArray)){
            $inCheckValueArray = util_general::stringValuesInAssociativeArray($checkValueArray);
            $inMessage = "Missing one or more POST variables in ".self::$service."::".__FUNCTION__." . ";
            $inMessageAppend = array();
            foreach($inCheckValueArray as $key => $value){

                array_push($inMessageAppend, $key."==".$value);
            }
            $inMessage .= implode(", ", $inMessageAppend);
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);

            $myArray['Reason'] = "Missing required variable(s)";
            return $myArray;
        }

        $contactUsArray = array(
            'contactEmail' => $contactEmail,
            'reason' => $reason,
            'message' => $message
        );
        return ciborium_general::submitContactUsRequest($contactUsArray, __METHOD__);

    }


}



?>