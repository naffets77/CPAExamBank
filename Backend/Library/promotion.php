<?php
require_once(realpath(__DIR__)."/config.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_general.php");
include_once(library_configuration::$environment_librarypath."/validate.php");
include_once(library_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/stripe_config.php");

class promotion
{


    /**
     * @param $inPromotionId
     * @return int
     */
    public static function verifyPromotionExistsById($inPromotionId){
        if(validate::tryParseInt($inPromotionId)){
            $selectArray = array('PromotionId');  //or array("field1", "field2"...)
            $whereClause = "PromotionId = '".$inPromotionId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            $myAccount = database::select("Promotion", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);

            if(count($myAccount) > 0){
                return $myAccount[0]->PromotionId;
            }
            else{
                return 0;
            }
        }
        else{
            $errorMessage = "PromotionId was not an integer";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            //die("Invalid input.");
            return 0;
        }

    }

    /**
     * @param $inPromotionCode
     * @return int
     */
    public static function verifyPromotionExistsByCode($inPromotionCode){
        if(validate::isNotNullOrEmpty_String($inPromotionCode)){
            $selectArray = array('PromotionId');  //or array("field1", "field2"...)
            $whereClause = "PromotionCode = :PromotionCode";
            $orderBy = "";
            $limit = "";
            $preparedArray = array(
                ':PromotionCode' => $inPromotionCode
            );

            $myAccount = database::select("Promotion", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);

            if(count($myAccount) > 0){
                return $myAccount[0]->PromotionId;
            }
            else{
                return 0;
            }

        }
        else{
            $errorMessage = "PromotionCode was NULL or empty";
            util_errorlogging::LogGeneralError(2, $errorMessage, __METHOD__, __FILE__);
            //die("Invalid input.");
            return 0;
        }
    }

    /**
     * @param $inPromotionId
     * @return array|null
     */
    public static function getPromotionById($inPromotionId){

        if(validate::tryParseInt($inPromotionId)){
            $selectArray = null;  //or array("field1", "field2"...)
            $whereClause = "PromotionId = '".$inPromotionId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            return database::select("Promotion", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
        }
        else{
            return array();
        }

    }


    /**
     * @param $inPromotionCode
     * @return array|null
     */
    public static function getPromotionByCode($inPromotionCode){

        if(validate::isNotNullOrEmpty_String($inPromotionCode)){
            $promotionId = promotion::verifyPromotionExistsByCode($inPromotionCode);

            return promotion::getPromotionById($promotionId);
        }
        else{
            return array();
        }

    }

}

?>