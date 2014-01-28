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

    /**
     * @param $inPromotionId
     * @param $inAccountUserId
     * @param $inCaller
     * @return array|null
     */
    public static function getAccountUserToPromotion($inPromotionId, $inAccountUserId, $inCaller){
        if(validate::tryParseInt($inPromotionId) && validate::tryParseInt($inAccountUserId)){
            $selectArray = null;  //or array("field1", "field2"...)
            $whereClause = "PromotionId = '".$inPromotionId."' AND AccountUserId = '".$inAccountUserId."'";
            $orderBy = "";
            $limit = "";
            $preparedArray = null;

            return database::select("AccountUserToPromotion", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
        }
        else{
            $errorMessage = "PromotionId and/or AccountUserId were not integers. Called by ".$inCaller;
            util_errorlogging::LogGeneralError(3, $errorMessage, __METHOD__, __FILE__);
            return array();
        }
    }

    /**
     * @param $inPromotionId
     * @param $inAccountUserId
     * @param $inCaller
     * @return int|string
     */
    public static function insertAccountUserToPromotion($inPromotionId, $inAccountUserId, $inCaller){
        $inputArray = array(
            'AccountUserId' => ':AccountUserId',
            'PromotionId' => ':PromotionId',
            'LastModifiedBy' => ':LastModifiedBy',
            'DateCreated' => ':DateCreated',
            'CreatedBy' => ':CreatedBy'
        );
        $insertPrepare = array(
            ':AccountUserId' => $inAccountUserId,
            ':PromotionId' => $inPromotionId,
            ':LastModifiedBy' => $inCaller,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inCaller
        );
        $insertColumns = array_keys($inputArray);
        $insertValues = array_values($inputArray);

        return database::insert("AccountUserToPromotion", $insertColumns, $insertValues, $insertPrepare, __METHOD__);

    }

    /**
     * @param $inAccountUserToPromotionId
     * @param $inCaller
     * @return bool|string
     */
    public static function applyAccountUserToPromotion($inAccountUserToPromotionId, $inCaller){
        $updateArray = array(
            'DateApplied' => ':DateApplied',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':DateApplied' => util_datetime::getDateTimeNow(),
            ':LastModifiedBy' => $inCaller
        );

        $whereClause = "AccountUserToPromotionId = '".$inAccountUserToPromotionId."'";

        return database::update("AccountUserToPromotion", $updateArray, $updatePrepare, $whereClause, __METHOD__);

    }

    public static function redeemPromotion($inPromotionId, $inCaller){
        $selectArray = array("TimesRedeemed");  //or array("field1", "field2"...)
        $whereClause = "PromotionId = '".$inPromotionId."'";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        $timesRedeemed = database::select("Promotion", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);

        $updateArray = array(
            'TimesRedeemed' => ':TimesRedeemed',
            'LastModifiedBy' => ':LastModifiedBy'
        );
        $updatePrepare = array(
            ':TimesRedeemed' => $timesRedeemed,
            ':LastModifiedBy' => $inCaller
        );

        $whereClause = "PromotionId = '".$inPromotionId."'";

        return database::update("Promotion", $updateArray, $updatePrepare, $whereClause, __METHOD__);
    }
}

?>