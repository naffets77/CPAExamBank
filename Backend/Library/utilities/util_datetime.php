<?php
require_once(realpath(__DIR__."/..")."/config.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(library_configuration::$environment_librarypath."/validate.php");

class util_datetime
{
    public static function getPHPTimeToDateTime($time){
        return date('Y-m-d H:i:s', $time);
    }

    public static function getDateTimeToPHPTime($datetime){
        return strtotime($datetime);
    }

    public static function getDateTimeNow(){
        return date('Y-m-d H:i:s', time() );
    }

    public static function getDateNow(){
        return date('m/d/Y', time() );
    }

    public static function getDateStringToDateTime($dateString){
        return util_datetime::getPHPTimeToDateTime(util_datetime::getDateTimeToPHPTime($dateString));
    }

    /**
     * Converts a DateTime string to Date string
     * @param string $inDateTime
     * @return string Date in format (##/##/####) e.g. 12/31/2012
     */
    public static function getDateTimeToDate($inDateTime){
        $myTimeStamp = strtotime($inDateTime);
        return date('m/d/Y', $myTimeStamp);
    }

    public static function getCurrentYear4Digit(){
        return date('Y', time());
    }

    public static function getCurrentYear2Digit(){
        return date('y', time());
    }

    //use this only for display of DateTime
    public static function applyOffsetToDateTime($datetime, $inOffset){

        if(validate::isValidTimeZoneOffset($inOffset)){
            $hours = (int)substr($inOffset, 1, 2); //just in case of 00
            $minutes = (int)substr($inOffset, 5, 2);
            $addOffset = substr($inOffset,0, 1) == "+" ? true : false;

            $interval = "";
            if($addOffset){
                $interval .= $hours."hours ".$minutes." minutes";
            }
            else{
                $interval .= "-".$hours."hours -".$minutes." minutes";
            }
            return $newDateTime = strtotime($interval, $datetime);
        }
        else{
            //log error
            return $datetime;
        }

    }
}

?>