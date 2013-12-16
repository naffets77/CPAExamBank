<?php
require_once(realpath(__DIR__)."/config.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(service_configuration::$environment_librarypath."/validate.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(service_configuration::$ciborium_librarypath."/ciboriumlib_account.php");

class service_promotion{
    //service name
    static $service = "service_promotion";

    static function validatePromotionCode(){
        $returnArray = array(
            "Reason" => "",
            "Result" => 0
        );

        $promoCode =  validate::requirePostField('promoCode', self::$service, __FUNCTION__);

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){

            $accountUserId = $_SESSION['Account']->AccountUserId;

            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);
            $checkValueArray = array(
                "promoCode" => $promoCode,
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
                $returnArray['Reason'] = "Missing required variable(s)";
                return $returnArray;
            }

            return ciborium_promotion::validatePromotionCodeForUser($promoCode, $accountUserId, __METHOD__);

        }
        elseif($promoCode != null){
            return ciborium_promotion::validateActivePromotionByCode($promoCode, __METHOD__);
        }
        else{
            $returnArray['Missing required variable(s)'];
        }

        return $returnArray;
    }
}

?>