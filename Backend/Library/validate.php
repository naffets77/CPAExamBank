<?php
require_once(realpath(__DIR__)."/config.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(library_configuration::$environment_librarypath."/database.php");

/**
 * All generic validation classes
 */

class validate {


    public static function requirePostField($fieldName, $service, $function, $inDie = true){

        if( !isset($_POST[$fieldName]) ){
            $errorMessage = "Service error in ".$service."-".$function.". Missing POST field name ".$fieldName;
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            if($inDie){
                die("Service Error ($service - $function) : Expected variable to be present.");
            }
            return null;
        }

        return $_POST[$fieldName];
    }

    public static function requireSessionField($fieldName, $service, $function, $inDie = true){

        if ( !isset($_SESSION) ){
            session_start();
        }

        if( !isset($_SESSION[$fieldName]) ){
            $errorMessage = "Service error in ".$service."-".$function.". Missing SESSION field name ".$fieldName;
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            if($inDie){
                die("Service Error ($service - $function) : Expected variable to be present.");
            }
            return null;
        }

        return $_SESSION[$fieldName];
    }

    public static function requireValidHash($service, $function){

        $result = true;
        $reason = "";

        if ( !isset($_SESSION) ){
            session_start();
        }

        if( !isset($_SESSION['AccountHash']) ){
            $errorMessage = "Service error in ".$service."-".$function.". Missing 'AccountHash' in SESSION";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            $result = false;
            $reason = "Expected variable was not present.";
            return array(
                'Result' => $result,
                'Reason' => $reason
            );
            //die("Service Error ($service - $function) : Expected variable to be present.");
        }

        if(!isset($_POST['Hash'])){
            $errorMessage = "Service error in ".$service."-".$function.". Missing 'Hash' in POST";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            $result = false;
            $reason = "Expected variable was not present.";
            return array(
                'Result' => $result,
                'Reason' => $reason
            );
            //die("Service Error ($service - $function) : Expected variable to be present.");
        }

        if( $_SESSION['AccountHash'] != $_POST['Hash'] ){
            $errorMessage = "Service error in ".$service."-".$function.". AccountHash did not match POST Hash.";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            $result = false;
            $reason = "Expected variable(s) did not match.";
            return array(
                'Result' => $result,
                'Reason' => $reason
        );
            //die("Service Error ($service - $function) : Variable mismatch");
        }

        return array(
            'Result' => $result,
            'Reason' => $reason
        );

    }

    //check if MD5 hash is valid
    public static function isValidMd5($md5)
    {
        return !empty($md5) && preg_match('/^[a-f0-9]{32}$/', $md5);
    }

    //$offset should be like "+00:00" or "-10:00" up to +/-12:00
    public static function isValidTimeZoneOffset($inOffset)
    {
        $regex1 = "/[+-][0][0-9]:[0-5][0-9]/";
        $regex2 = "/[+-][1][0-2]:[0-5][0-9]/"; //for 10:**, 11:**, 12:**
        $myBool = false;
        if(preg_match($regex1, $inOffset) || preg_match($regex2, $inOffset)){$myBool = true;}
        return $myBool;
    }

    public static function tryParseInt($inNum){
        if(is_numeric($inNum)){
            if(is_int((int)$inNum)){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }

    }


    /**
     * @param $inString
     * @return bool
     */
    public static function isNotNullOrEmpty_String($inString){
        if($inString === null){
            return false;
        }
        if(is_string($inString)){
            if(strlen($inString) > 0){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public static function emailAddress($inEmail){

        if(filter_var($inEmail, FILTER_VALIDATE_EMAIL)){
            return true;
        }
        else{
            return false;
        }

    }

    //expect 03 or 12, etc.
    public static function isValidNumeric2DigitMonth($inMonth)
    {
        $regex1 = "/0[1-9]/";
        $regex2 = "/1[0-2]/";
        $myBool = false;
        if(preg_match($regex1, $inMonth) || preg_match($regex2, $inMonth)){$myBool = true;}
        return $myBool;
    }

    public static function isValid4DigitYear($inYear){
        $myBool = false;

        if(validate::tryParseInt($inYear)){
            $myYear = (int)$inYear;
            if($myYear >= 0 && $myYear <= 9999){
                $myBool = true;
            }
        }
        return $myBool;
    }

    public static function isValid4DigitCCYear($inYear){
        $myBool = false;
        $currentYear = (int)util_datetime::getCurrentYear4Digit();

        if(validate::isValid4DigitYear($inYear)){
            $myYear = (int)$inYear;
            $myMaxYear = $currentYear + 25;
            if($myYear >= $currentYear && $myYear <= $myMaxYear){
                $myBool = true;
            }
        }

        return $myBool;
    }

    public static function isNotNullOrEmpty_Array($inArray){
        if($inArray === null){
            return false;
        }
        if(is_array($inArray) && !empty($inArray)){
            return true;
        }
        else{
            return false;
        }
    }

    public static function isValidJSONString($inJSON){
        $bool = false;
        $myJSON = trim($inJSON);
        if(validate::isNotNullOrEmpty_String($myJSON)){
            $validFirstCharacters = array("{", "[");
            if(in_array(substr($myJSON, 0, 1), $validFirstCharacters)){
                json_decode($myJSON);
                return (json_last_error() == JSON_ERROR_NONE);
            }
        }
        return $bool;
    }

    public static function isValidBool($inString){
        if(validate::isNotNullOrEmpty_String($inString)){
            if(is_bool((bool)$inString)){
                return true;
            }
            else{
                return false;
            }
        }
        return false;
    }

    public static function arrayContainAllIntegerValues($inArray){
        if(validate::isNotNullOrEmpty_Array($inArray)){
            foreach ($inArray as $key => $value) {
                if(!is_int($value)){
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}


?>