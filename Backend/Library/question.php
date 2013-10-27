<?php
require_once(realpath(__DIR__)."/config.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(library_configuration::$environment_librarypath."/validate.php");
include_once(library_configuration::$environment_librarypath."/database.php");

class question{

    public static function getAllQuestionsForPublicUI($inAccountUserId, $inQuestionTypeId = 3, $inSectionTypeId = 5, $inLimit = ""){

        $QuestionTypeId = "3";
        $SectionTypeId = "5";

        $QuestionTypeIdArray = array("3");
        if(validate::tryParseInt($inQuestionTypeId)){
            if(!in_array((string)$inQuestionTypeId, $QuestionTypeIdArray)){
                $QuestionTypeId = (string)$inQuestionTypeId;
                array_push($QuestionTypeIdArray, $QuestionTypeId);
                unset($QuestionTypeIdArray[array_search("3", $QuestionTypeIdArray)]);
            }
        }

        $SectionTypeIdArray = array("5");
        if(validate::tryParseInt($inSectionTypeId)){
            if(!in_array((string)$inSectionTypeId, $SectionTypeIdArray)){
                $SectionTypeId = (string)$inSectionTypeId;
                array_push($SectionTypeIdArray , $SectionTypeId);
                unset($SectionTypeIdArray[array_search("5", $SectionTypeIdArray)]);
            }
        }
        //determine if need to get all or just one from a type id
        $boolGetAllQuestionTypeIds = array_search("3", $QuestionTypeIdArray) !== false ? true : false;
        $boolGetAllSectionTypeIds = array_search("5", $SectionTypeIdArray) !== false ? true : false;

        //$selectArray = array("QuestionId", "DisplayText", "Explanation");
        $selectArray = null;
        $whereClause = "IsApprovedForUse IN (1) AND IsActive IN (1) AND IsDeprecated IN (0) ";
        //$whereClause = "";
        $whereClause .= $boolGetAllQuestionTypeIds ? "AND QuestionTypeId IN (SELECT QuestionTypeId FROM QuestionType WHERE QuestionTypeId <> 3) " : "AND QuestionTypeId IN (".$QuestionTypeId.") ";
        $whereClause .= $boolGetAllSectionTypeIds ? "AND SectionTypeId IN (SELECT SectionTypeId FROM SectionType WHERE SectionTypeId <> 5) " : "AND SectionTypeId IN (".$SectionTypeId.") ";
        $orderBy = "";
        $limit = validate::tryParseInt($inLimit) ? (string)$inLimit : "";
        $preparedArray = null;

        //Getting which QuestionIds to return based off of user's history
        $FilterArray = array(
            'SectionTypeId' => $SectionTypeId,
            'ResultId' => 4,
            'OrderById' => 4
        );
        $QuestionHistoryMetrics = question::getAccountUserQuestionHistoryMetricsWithFiltersById($inAccountUserId, $FilterArray);

        if(count($QuestionHistoryMetrics) > 0 && $limit != ""){
            $QuestionIdsArray = array();
            $QuestionIdsToExcludeArray = array();
            $RightQuestionIdsArray = array();
            $WrongQuestionIdsArray = array();
            //TODO: Add these to config file
            $numRightTimesLimit = 10;
            //$numWrongTimesLimit = 7;
            $numTimesAnsweredLimit = 22;
            $percent_right = 0.2;
            $percent_wrong = 0.5;
            $questionLimit = (int)$inLimit;
            $amountRightToReturn = floor($questionLimit * $percent_right);
            $amountWrongToReturn = floor($questionLimit * $percent_wrong);
            $amountNewToReturn = $questionLimit - $amountRightToReturn - $amountWrongToReturn;

            foreach ($QuestionHistoryMetrics as $stdIndex => $QHObject) {
                $totalTimesAnswered = (int)$QHObject->TimesCorrect + (int)$QHObject->TimesIncorrect;
                if($totalTimesAnswered < $numTimesAnsweredLimit){
                    //add as a "right" question
                    if($QHObject->TimesCorrect < $numRightTimesLimit && $QHObject->TimesCorrect > 0){
                        $RightQuestionIdsArray[] = (int)$QHObject->QuestionId;
                        $QuestionIdsToExcludeArray[] = (int)$QHObject->QuestionId;
                    }

                    //add as a wrong question
                    if($QHObject->TimesCorrect == 0 && $QHObject->TimesIncorrect > 0){
                        $WrongQuestionIdsArray[] = (int)$QHObject->QuestionId;
                        $QuestionIdsToExcludeArray[] = (int)$QHObject->QuestionId;
                    }
                }
                else{
                    $QuestionIdsToExcludeArray[] = (int)$QHObject->QuestionId;
                }
            }

            //compile the initial question ids from history
            if(count($RightQuestionIdsArray) > $amountRightToReturn){
                $RightQuestionIdsArray = array_slice($RightQuestionIdsArray, 0, $amountRightToReturn);
            }
            if(count($WrongQuestionIdsArray) > $amountWrongToReturn){
                $WrongQuestionIdsArray = array_slice($WrongQuestionIdsArray, 0, $amountWrongToReturn);
            }
            $QuestionIdsArray = array_merge($QuestionIdsArray, $RightQuestionIdsArray);
            $QuestionIdsArray = array_merge($QuestionIdsArray, $WrongQuestionIdsArray);

            $QuestionIdsArray = array_unique($QuestionIdsArray);


            //check to see if there is a need for supplemental questions
            $NewQuestionLimit = $questionLimit - count($QuestionIdsArray);
            if($NewQuestionLimit > 0){

                $NewQuestionIdsArray = array();
                $NewQuestionIdsObjects = question::getRemainingQuestionIdsForSimulation($QuestionIdsToExcludeArray, $SectionTypeId, $NewQuestionLimit);

                //if there are enough to fulfill request
                if(count($NewQuestionIdsObjects) > 0 && count($NewQuestionIdsObjects) == $NewQuestionLimit){
                    foreach ($NewQuestionIdsObjects as $stdIndex => $Object) {
                        $NewQuestionIdsArray[] = (int)$Object->QuestionId;
                    }
                    $QuestionIdsArray = array_merge($QuestionIdsArray, $NewQuestionIdsArray);
                }
                //not enough to fill request, need to provide some questions regardless;
                //only exlude the right and wrong questions this time
                else{
                    $NewQuestionIdsObjects = question::getRemainingQuestionIdsForSimulation($QuestionIdsArray, $SectionTypeId, $NewQuestionLimit);
                    foreach ($NewQuestionIdsObjects as $stdIndex => $Object) {
                        $NewQuestionIdsArray[] = (int)$Object->QuestionId;
                    }
                    $QuestionIdsArray = array_merge($QuestionIdsArray, $NewQuestionIdsArray);
                }
            }

            //add to where clause
            $myQuestionIds = validate::isNotNullOrEmpty_Array($QuestionIdsArray) ? implode(", ", $QuestionIdsArray) : "0";
            $whereClause .= "AND QuestionId IN (".$myQuestionIds.") ";
        }

        return database::select("Question", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

	public static function getAllQuestionsForAdminUI($inQuestionTypeId = 3, $inSectionTypeId = 5, $inLimit = ""){

        $QuestionTypeId = "3";
        $SectionTypeId = "5";

        $QuestionTypeIdArray = array("3");
        if(validate::tryParseInt($inQuestionTypeId)){
            if(!in_array((string)$inQuestionTypeId, $QuestionTypeIdArray)){
                $QuestionTypeId = (string)$inQuestionTypeId;
                array_push($QuestionTypeIdArray, $QuestionTypeId);
                unset($QuestionTypeIdArray[array_search("3", $QuestionTypeIdArray)]);
            }
        }

        $SectionTypeIdArray = array("5");
        if(validate::tryParseInt($inSectionTypeId)){
            if(!in_array((string)$inSectionTypeId, $SectionTypeIdArray)){
                $SectionTypeId = (string)$inSectionTypeId;
                array_push($SectionTypeIdArray , $SectionTypeId);
                unset($SectionTypeIdArray[array_search("5", $SectionTypeIdArray)]);
            }
        }
        //determine if need to get all or just one from a type id
        $boolGetAllQuestionTypeIds = array_search("3", $QuestionTypeIdArray) !== false ? true : false;
        $boolGetAllSectionTypeIds = array_search("5", $SectionTypeIdArray) !== false ? true : false;

        //$selectArray = array("QuestionId", "DisplayText", "Explanation");
        $selectArray = null;
        $whereClause = "IsApprovedForUse IN (0,1) AND IsActive IN (0,1) AND IsDeprecated IN (0,1) ";
        //$whereClause = "";
        $whereClause .= $boolGetAllQuestionTypeIds ? "AND QuestionTypeId IN (SELECT QuestionTypeId FROM QuestionType WHERE QuestionTypeId <> 3) " : "AND QuestionTypeId IN (".$QuestionTypeId.") ";
        $whereClause .= $boolGetAllSectionTypeIds ? "AND SectionTypeId IN (SELECT SectionTypeId FROM SectionType WHERE SectionTypeId <> 5) " : "AND SectionTypeId IN (".$SectionTypeId.") ";
        $orderBy = "";
        $limit = validate::tryParseInt($inLimit) ? (string)$inLimit : "";
        $preparedArray = null;

        return database::select("Question", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getAllAnswersForUI($inQuestionIdArray){
        $QuestionIds = validate::isNotNullOrEmpty_Array($inQuestionIdArray) ? implode(", ", $inQuestionIdArray) : "0";
        $selectArray = array("QuestionToAnswersId", "QuestionId", "DisplayText", "IsAnswerToQuestion");
        $whereClause = "QuestionId IN (".$QuestionIds.")";
        $orderBy = "AnswerIndex ASC";
        $limit = "";
        $preparedArray = null;

        return database::select("QuestionToAnswers", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getQuestionsByIds($inQuestionIdArray){
        $QuestionIds = validate::isNotNullOrEmpty_Array($inQuestionIdArray) ? implode(", ", $inQuestionIdArray) : "0";
        $selectArray = array("QuestionId", "DisplayText", "Explanation");
        $whereClause = "IsApprovedForUse = 1 AND IsActive= 1 ";
        $whereClause .= "AND QuestionId IN (".$QuestionIds.")";
        $orderBy = "QuestionId ASC";
        $limit = "";
        $preparedArray = null;

        return database::select("Question", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getRemainingQuestionIdsForSimulation($inQuestionIdArray, $inSectionTypeId, $inLimit){
        $QuestionIds = validate::isNotNullOrEmpty_Array($inQuestionIdArray) ? implode(", ", $inQuestionIdArray) : "0";
        $selectArray = array("QuestionId");
        $whereClause = "IsApprovedForUse = 1 AND IsActive= 1 ";
        if($QuestionIds != "0"){
            $whereClause .= "AND QuestionId NOT IN (".$QuestionIds.") ";
        }
        else{
            //$whereClause .= "AND QuestionId < ".$QuestionIds." ";
        }
        $whereClause .= "AND SectionTypeId = ".$inSectionTypeId." ";
        $orderBy = "";
        $limit = (string)$inLimit;
        $preparedArray = null;

        return database::select("Question", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getAllQuestionsByIds($inQuestionIdArray){
        $QuestionIds = validate::isNotNullOrEmpty_Array($inQuestionIdArray) ? implode(", ", $inQuestionIdArray) : "0";
        $selectArray = array("QuestionId", "DisplayText", "Explanation");
        $whereClause = "QuestionId IN (".$QuestionIds.") ";
        $orderBy = "QuestionId ASC";
        $limit = "";
        $preparedArray = null;

        return database::select("Question", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getAllQuestions(){
        $selectArray = null;  //or array("field1", "field2"...)
        $whereClause = "";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("Question", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getAllAnswers(){
        $selectArray = null;  //or array("field1", "field2"...)
        $whereClause = "";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("QuestionToAnswers", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }

    public static function getAccountUserQuestionHistoryById($inAccountUserId){
        $parametersArray = array($inAccountUserId);
        return database::callStoredProcedure("sp_getAccountUserQuestionHistoryById", $parametersArray, __METHOD__);
    }

    public static function deleteAccountUserQuestionHistoryById($inAccountUserId){
        $parametersArray = array($inAccountUserId);
        return database::callStoredProcedure("sp_DELETEAccountUserQuestionHistoryById", $parametersArray, __METHOD__);
    }

    public static function getAccountUserQuestionHistoryMetricsById($inAccountUserId){
        $parametersArray = array($inAccountUserId);
        return database::callStoredProcedure("sp_getAccountUserQuestionHistoryMetricsById", $parametersArray, __METHOD__);
    }

    public static function getSubscriptionTypes($inSubscriptionTypeIdArray){

        $SubscriptionTypeIds = validate::isNotNullOrEmpty_Array($inSubscriptionTypeIdArray) ? implode(", ", $inSubscriptionTypeIdArray) : "0";
        $selectArray = null;  //or array("field1", "field2"...)
        $whereClause = "SubscriptionTypeId IN (".$SubscriptionTypeIds.") ";
        $orderBy = "";
        $limit = "";
        $preparedArray = null;

        return database::select("SubscriptionType", $selectArray, $whereClause, $orderBy, $limit, $preparedArray, __METHOD__);
    }


    public static function getAccountUserQuestionHistoryWithFiltersById($inAccountUserId, $inFilterArray){
        $parametersArray = array(
            $inAccountUserId,
            $inFilterArray['SectionTypeId'],
            $inFilterArray['ResultId'],
            $inFilterArray['OrderById']
        );
        return database::callStoredProcedure("sp_getAccountUserQuestionHistoryWithFiltersById", $parametersArray, __METHOD__);
    }

    public static function getAccountUserQuestionHistoryMetricsWithFiltersById($inAccountUserId, $inFilterArray){
        $parametersArray = array(
            $inAccountUserId,
            $inFilterArray['SectionTypeId'],
            $inFilterArray['ResultId'],
            $inFilterArray['OrderById']
        );
        return database::callStoredProcedure("sp_getAccountUserQuestionHistoryMetricsWithFiltersById", $parametersArray, __METHOD__);
    }

    public static function saveAccountUserQuestionHistoryEntry($inInputArray, $inPrepareArray, $inLastModifiedBy){

        /*$inputArray = array(
            'LoginName' => ':LoginName',
            'LoginPassword' => ':LoginPassword',
            'LastModifiedBy' => ':LastModifiedBy',
            'DateCreated' => ':DateCreated',
            'CreatedBy' => ':CreatedBy'
        );
        $insertPrepare = array(
            ':LoginName' => $inEmail,
            ':LoginPassword' => $inPassword,
            ':LastModifiedBy' => $inLastModifiedBy,
            ':DateCreated' => util_datetime::getDateTimeNow(),
            ':CreatedBy' => $inLastModifiedBy
        );*/

        if(count($inInputArray) != count($inPrepareArray)){
            $inMessage = "Values array did not match Prepare array size in ".__METHOD__."() . Called by ".$inLastModifiedBy."() .";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return 0;
        }

        $insertColumns = array_keys($inInputArray);
        $insertValues = array_values($inInputArray);

        return database::insert("AccountUserQuestionHistory", $insertColumns, $insertValues, $inPrepareArray, __METHOD__);

    }

    public static function updateQuestion($inQuestionId, $inValuesArray, $inPrepareArray, $inLastModifiedBy){

        if(!validate::tryParseInt($inQuestionId)){
            $inMessage = "QuestionId was not an integer in ".__METHOD__."() . Called by ".$inLastModifiedBy."() .";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        if(count($inValuesArray) != count($inPrepareArray)){
            $inMessage = "Values array did not match Prepare array size in ".__METHOD__."() . Called by ".$inLastModifiedBy."() .";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        $whereClause = "QuestionId = ".$inQuestionId."";


        return database::update("Question", $inValuesArray, $inPrepareArray, $whereClause, $inLastModifiedBy);
    }

    public static function updateAnswer($inQuestionToAnswersId, $inValuesArray, $inPrepareArray, $inLastModifiedBy){

        if(!validate::tryParseInt($inQuestionToAnswersId)){
            $inMessage = "QuestionToAnswersId was not an integer in ".__METHOD__."() . Called by ".$inLastModifiedBy."() .";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        if(count($inValuesArray) != count($inPrepareArray)){
            $inMessage = "Values array did not match Prepare array size in ".__METHOD__."() . Called by ".$inLastModifiedBy."() .";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return false;
        }

        $whereClause = "QuestionToAnswersId = ".$inQuestionToAnswersId."";

        return database::update("QuestionToAnswers", $inValuesArray, $inPrepareArray, $whereClause, $inLastModifiedBy);
    }

    public static function copyQuestionAndAnswersById($inQuestionId){
        $parametersArray = array($inQuestionId);
        return database::callStoredProcedure("sp_CopyQuestionAndAnswersById", $parametersArray, __METHOD__);
    }

}