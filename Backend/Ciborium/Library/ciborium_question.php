<?php
require_once(realpath(__DIR__)."/config.php");
include_once(ciborium_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(ciborium_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(ciborium_configuration::$environment_librarypath."/validate.php");
include_once(ciborium_configuration::$environment_librarypath."/database.php");
include_once(ciborium_configuration::$environment_librarypath."/question.php");

class ciborium_question{

    //////////////////////////////////////////
    // Selects
    //////////////////////////////////////////

    /**
     * Library: getAllQuestionsAndAnswersForUI()
     * Gets all admin questions form database
     *
     * @param bool inGetIsActive
     *
     * @return array
     *      Reason
     *      Result
     *      QuestionResponses
     */
    public static function getAllQuestionsAndAnswersForUI($inGetIsActive = true){
        $returnArray = array(
            'Result' => 1,
            'Reason' => "",
            'QuestionResponses' => array()
        );

        //Verify inputs
        if(!is_bool($inGetIsActive)){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for inGetIsActive. Not a boolean";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        $questionObjects = question::getAllQuestionsForAdminUI(3, 5, "", $inGetIsActive);

        if(count($questionObjects) > 0){
            $responsesArray = self::buildQuestionsAndAnswersArray_Admin($questionObjects);
            $returnArray['QuestionResponses'] = $responsesArray;
        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "No questions were found";
        }

        return $returnArray;
    }

    /**
     * Library: getQuestionsAndAnswersBySectionType()
     * Gets all public questions based off of user input
     *
     * @param int $inSectionTypeId
     * @param int $inMaxNumberOfQuestionsToReturn
     * @param int $inAccountUserId
     * @param bool $inIsAdmin
     * @param bool $inIsFreeAccount
     * @param int $inQuestionTypeId
     *
     *
     * @return array
     *      Reason
     *      Result
     *      QuestionResponses
     */
    public static function getQuestionsAndAnswersBySectionType($inSectionTypeId, $inMaxNumberOfQuestionsToReturn, $inAccountUserId, $inIsAdmin, $inIsFreeAccount = false, $inQuestionTypeId = 1){
        $returnArray = array(
            'Result' => 1,
            'Reason' => "",
            'QuestionResponses' => array()
        );

        //validate SectionTypeId
        if(validate::tryParseInt($inSectionTypeId)){
            $SectionTypeId = (string)$inSectionTypeId;
        }
        else{
            $inMessage = "SectionTypeId was not an integer in ".__METHOD__."() . ";
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            $SectionTypeId = "0";
        }

        //validate AccountUserId
        if(validate::tryParseInt($inAccountUserId)){
            $AccountUserId = (string)$inAccountUserId;
        }
        else{
            $inMessage = "AccountUserId was not an integer in ".__METHOD__."() . ";
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            $AccountUserId = "0";
        }

        //validate question limit
        if(validate::tryParseInt($inMaxNumberOfQuestionsToReturn)){
            $int_QuestionLimit = (int)$inMaxNumberOfQuestionsToReturn;
            if($int_QuestionLimit > 0){
                $validQuestionLimits = self::returnValidPublicPracticeNumberOfQuestionsArray();
                if(in_array($int_QuestionLimit, $validQuestionLimits)){
                    $QuestionLimit = (string)$inMaxNumberOfQuestionsToReturn;
                }
                else{
                    $inMessage = "Question limit (".$int_QuestionLimit.") was not in the list of expected values ".__METHOD__."() . ";
                    util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                    $QuestionLimit = "0";
                }
            }
            elseif($int_QuestionLimit == -1 && $inIsAdmin){
                $QuestionLimit = "";
            }
            else{
                $inMessage = "Question limit (".$inMaxNumberOfQuestionsToReturn.") was not valid in ".__METHOD__."() . ";
                util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                $QuestionLimit = "0";
            }
        }
        else{
            $inMessage = "Question limit was not an integer in ".__METHOD__."() . ";
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            $QuestionLimit = "0";
        }

        if($QuestionLimit != "0" && $QuestionLimit != ""){
            $questionObjects = question::getAllQuestionsForPublicUI($AccountUserId, $inQuestionTypeId, $SectionTypeId, $QuestionLimit, (bool)$inIsFreeAccount);

            if(count($questionObjects) > 0){
                $responsesArray = self::buildQuestionsAndAnswersArray($questionObjects);
                $returnArray['QuestionResponses'] = $responsesArray;
            }
            else{
                $returnArray['Result'] = 0;
                $returnArray['Reason'] = "No questions were found";
            }
        }
        elseif(($int_QuestionLimit == -1 && $inIsAdmin)){
            $questionObjects = question::getAllQuestionsForAdminUI(1, $SectionTypeId, "");

            if(count($questionObjects) > 0){
                $responsesArray = self::buildQuestionsAndAnswersArray_Admin($questionObjects);
                $returnArray['QuestionResponses'] = $responsesArray;
            }
            else{
                $returnArray['Result'] = 0;
                $returnArray['Reason'] = "No questions were found";
            }
        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "No questions were found; search not performed";
        }


        return $returnArray;
    }

    /**
     * Library: getAllQuestionsAndAnswers()
     * Gets all questions and answers in database
     *
     *
     * @return array
     *      Reason
     *      Result
     *      QuestionHistoryReturns
     */
    public static function getAllQuestionsAndAnswers(){
        $returnArray = array(
            'Result' => 1,
            'Reason' => "",
            'QuestionResponses' => array()
        );

        $questionObjects = question::getAllQuestions();

        if(count($questionObjects) > 0){
            $responsesArray = self::buildQuestionsAndAnswersArray_Admin($questionObjects);
            $returnArray['QuestionResponses'] = $responsesArray;
        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "No questions were found";
        }

        return $returnArray;
    }

    /**
     *
     * Library: getAccountUserQuestionHistoryById()
     * Gets all questions and answers history for a user
     *
     * @param $inAccountUserId
     * @param $inCaller
     * @return array
     *      Result
     *      Reason
     *      QuestionHistoryReturns
     */
    public static function getAccountUserQuestionHistoryById($inAccountUserId, $inCaller){
        $returnArray = array(
            'Result' => 1,
            'Reason' => "",
            'QuestionHistoryReturns' => array()
        );

        if(validate::tryParseInt($inAccountUserId)){
            $questionHistoryMetricsObjects = question::getAccountUserQuestionHistoryMetricsById($inAccountUserId);
        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "Invalid variable(s)";
            $inMessage = "AccountUserId was not an integer in ".__METHOD__."() . ";
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
        }

        if(count($questionHistoryMetricsObjects) > 0){
            //get question ids to get question objects
            $QuestionIdArray = array();
            foreach($questionHistoryMetricsObjects as $key => $MetricsObject){
                array_push($QuestionIdArray, (int)$MetricsObject->QuestionId);
            }
            $questionResponseObject = ciborium_question::getQuestionsAndAnswersForQuestionHistory($QuestionIdArray);
            $questionHistoryObjects = question::getAccountUserQuestionHistoryById($inAccountUserId);

            //build each QuestionHistory object
            foreach ($questionHistoryMetricsObjects as $stdKey1 => $MetricsObject) {
                $QHObject = new questionHistory();
                array_push($QHObject->Metrics, $MetricsObject);
                //Questions and answers array
                foreach ($questionResponseObject['QuestionResponses'] as $stdKey2 => $ResponseObject) {
                    if($MetricsObject->QuestionId == $ResponseObject->QuestionId){
                        array_push($QHObject->QuestionResponse, $ResponseObject);
                    }
                }

                //Question history array
                foreach ($questionHistoryObjects as $stdKey3 => $HistoryObject) {
                    if($MetricsObject->QuestionId == $HistoryObject->QuestionId){
                        array_push($QHObject->Summary, $HistoryObject);
                    }
                }

                array_push($returnArray['QuestionHistoryReturns'], $QHObject);
            }

        }

        return $returnArray;
    }

    /**
     *
     * Library: deleteAccountUserHistoryById()
     * Deletes all questions and answer history for a user
     *
     * @param $inAccountUserId
     * @param $inCaller
     * @return array
     *      Result
     *      Reason
     */
    public static function deleteAccountUserHistoryById($inAccountUserId, $inCaller){
        $returnArray = array(
            'Result' => 0,
            'Reason' => ""
        );

        //Verify inputs
        if(!validate::tryParseInt($inAccountUserId)){
            $returnArray['Reason'] = "Invalid input";
            $errorMessage = $returnArray['Reason']." for user id. Was not an integer.";
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        if(!account::verifyAccountUserExistsById($inAccountUserId)){
            $returnArray['Reason'] = "User does not exist.";
            $errorMessage = $returnArray['Reason']." AccountUserId was ".$inAccountUserId;
            util_errorlogging::LogBrowserError(3, $errorMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        question::deleteAccountUserQuestionHistoryById($inAccountUserId);
        $returnArray['Result'] = 1;
        $returnArray['Reason'] = "User's question history was deleted.";
        return $returnArray;
    }

    /**
     * Library: getAccountUserQuestionHistoryWithFiltersById()
     * Gets all user's public question history with filters
     *
     * @param int $inAccountUserId
     * @param array $inFilterArray
     * @param string $inCaller
     *
     * @return array
     *      Reason
     *      Result
     *      QuestionHistoryReturns
     */
    public static function getAccountUserQuestionHistoryWithFiltersById($inAccountUserId, $inFilterArray, $inCaller){
        $returnArray = array(
            'Result' => 1,
            'Reason' => "",
            'QuestionHistoryReturns' => array()
        );

        if(validate::tryParseInt($inAccountUserId)){
            $FilterArrayCheck = self::checkValidQuestionHistoryFilterArray($inFilterArray);
            if($FilterArrayCheck['Result']){
                $questionHistoryMetricsObjects = question::getAccountUserQuestionHistoryMetricsWithFiltersById($inAccountUserId, $inFilterArray);
            }
            else{
                $returnArray['Result'] = 0;
                $returnArray['Reason'] = "Invalid variable(s)";
                $inMessage = "FilterArray was not valid in ".__METHOD__."() . Called by ".$inCaller;
                util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "Invalid variable(s)";
            $inMessage = "AccountUserId was not an integer in ".__METHOD__."() . Called by ".$inCaller;
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
        }

        if(count($questionHistoryMetricsObjects) > 0){
            //get question ids to get question objects
            $QuestionIdArray = array();
            foreach($questionHistoryMetricsObjects as $key => $MetricsObject){
                array_push($QuestionIdArray, (int)$MetricsObject->QuestionId);
            }
            $questionResponseObject = ciborium_question::getQuestionsAndAnswersForQuestionHistory($QuestionIdArray);
            $questionHistoryObjects = question::getAccountUserQuestionHistoryWithFiltersById($inAccountUserId, $inFilterArray);

            //build each QuestionHistory object
            foreach ($questionHistoryMetricsObjects as $stdKey1 => $MetricsObject) {
                $QHObject = new questionHistory();
                array_push($QHObject->Metrics, $MetricsObject);
                //Questions and answers array
                foreach ($questionResponseObject['QuestionResponses'] as $stdKey2 => $ResponseObject) {
                    if($MetricsObject->QuestionId == $ResponseObject->QuestionId){
                        array_push($QHObject->QuestionResponse, $ResponseObject);
                    }
                }

                //Question history array
                foreach ($questionHistoryObjects as $stdKey3 => $HistoryObject) {
                    if($MetricsObject->QuestionId == $HistoryObject->QuestionId){
                        array_push($QHObject->Summary, $HistoryObject);
                    }
                }

                array_push($returnArray['QuestionHistoryReturns'], $QHObject);
            }

        }

        return $returnArray;
    }

    /**
     * Library: getQuestionsAndAnswersForQuestionHistory()
     * Gets selected question history by question ids
     *
     * @param array(int) $inQuestionIdArray
     *
     * @return array
     *      Reason
     *      Result
     *      QuestionHistoryReturns
     */
    public static function getQuestionsAndAnswersForQuestionHistory($inQuestionIdArray){

        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'QuestionResponses' => array()
        );

        //validate question array, must have real integers in it
        if(validate::isNotNullOrEmpty_Array($inQuestionIdArray)){
            $containsAllInts = validate::arrayContainAllIntegerValues($inQuestionIdArray);
            if(!$containsAllInts){
                $returnArray['Result'] = 0;
                $returnArray['Reason'] = "Invalid input.";
                $inMessage = "QuestionIdArray did not have all integers in it ".__METHOD__."() . ";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                return $returnArray;
            }
        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "Invalid input.";
            $inMessage = "QuestionIdArray was empty or null in ".__METHOD__."() . ";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return $returnArray;
        }

        $questionObjects = question::getAllQuestionsByIds($inQuestionIdArray);

        if(count($questionObjects) > 0){
            $responsesArray = self::buildQuestionsAndAnswersArray($questionObjects);
            $returnArray['Result'] = 1;
            $returnArray['QuestionResponses'] = $responsesArray;
        }
        else{
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "No questions were found";
        }

        return $returnArray;
    }


    //////////////////////////////////////////
    // Updates and Inserts
    //////////////////////////////////////////

    public static function saveAccountUserQuestionHistory($inJSONString, $inLastModifiedBy){

        $returnArray = array(
            'Result' => 0,
            'Reason' => ""
        );

        $insertResultsArray = array();
        $myJSONString = trim($inJSONString);
        if(validate::isValidJSONString($myJSONString)){
            $myJSONArray = json_decode($myJSONString, true);

            foreach($myJSONArray as $key => $value){
                $checkResult = self::checkValidQuestionHistoryArray($value);
                if($checkResult['Result']){
                    $wasAnsweredCorrectly = trim($value['answeredCorrectly']) == "1" ? true : false;
                    $wasSkipped = (int)$value['selectedAnswer'] == 0 ? true : false;
                    //added from 7/3/2013 email
                    $selectedAnswer = $value['selectedAnswer'];
                    $isIgnored = false;
                    if((int)$value['selectedAnswer'] == -1 || ((int)$value['mode'] == 1 && $wasSkipped) ){
                        $isIgnored = true;
                        $selectedAnswer = "0";
                    }

                    //build inputArray and prepareArray for insert
                    $inputArray = array(
                        'QuestionId' => ":QuestionId",
                        'AccountUserId' => ":AccountUserId",
                        'TimeSpentOnQuestion' => ":TimeSpentOnQuestion",
                        'TestModeTypeId' => ":TestModeTypeId",
                        'QuestionsToAnswersId' => ":QuestionsToAnswersId",
                        'WasAnsweredCorrectly' => ":WasAnsweredCorrectly",
                        'WasSkipped' => ":WasSkipped",
                        'IsIgnored' => ":IsIgnored",
                        'LastModifiedBy' => ':LastModifiedBy',
                        'DateCreated' => ':DateCreated',
                        'CreatedBy' => ':CreatedBy'
                    );
                    $prepareArray = array(
                        ':QuestionId' => $value['questionId'],
                        ':AccountUserId' => $value['accountUserId'],
                        ':TimeSpentOnQuestion' => $value['timeTaken'],
                        ':TestModeTypeId' => $value['mode'],
                        ':QuestionsToAnswersId' => $value['selectedAnswer'],
                        ':WasAnsweredCorrectly' => $wasAnsweredCorrectly,
                        ':WasSkipped' => $wasSkipped,
                        ':IsIgnored' => $isIgnored,
                        ':LastModifiedBy' => $inLastModifiedBy,
                        ':DateCreated' => util_datetime::getDateTimeNow(),
                        ':CreatedBy' => $inLastModifiedBy
                    );

                    $insertId = (int)question::saveAccountUserQuestionHistoryEntry($inputArray, $prepareArray, __METHOD__);
                    array_push($insertResultsArray, $insertId);
                }
                else{
                    $returnArray['Reason'] = "Invalid input";
                    $inMessage = "JSON array was not valid in ".__METHOD__."() . Relevant trace: ".$checkResult['Reason'];
                    util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                }
            }
        }
        else{
            $returnArray['Reason'] = "Invalid input";
            $inMessage = "JSON string was not valid in ".__METHOD__."() . ";
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return $returnArray;
        }


        //check insertIdsArray
        if(!empty($insertResultsArray)){
            if(!in_array(0, $insertResultsArray)){
                $returnArray['Result'] = 1;
            }
            else{
                $returnArray['Reason'] = "Error saving question history";
                $inMessage = "Error saving question history in ".__METHOD__."() . Some inserts were done, but there was at least one insert that failed.";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $returnArray['Reason'] = "Error saving question history";
            $inMessage = "Error saving question history in ".__METHOD__."() . No inserts were even attempted due to invalid JSON data.";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
        }

        return $returnArray;
    }

    public static function updateQuestionAndAnswers($inJSONString, $inLastModifiedBy){

        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'QuestionResponse' => ""
        );

        $updateResultsArray = array();
        //$myJSON = "{\"QuestionId\":\"1\",\"CorrectAnswerIndex\":0,\"QuestionClientId\":\"AUD1S1\",\"QuestionClientImage\":\"jpg 48\",\"IsApprovedForUse\":\"1\",\"IsDeprecated\":\"0\",\"IsActive\":\"1\",\"Explanation\":\"The CPA should make inquiries of the predecessor auditor when he is approached to perform an audit for the first time. This is required because the predecessor may provide the successor the relevant information which will assist the successor in determining\",\"Question\":\"<p>The  services which cannot be performed for a nonissuer test client are </p><p></p><p></p><p></p><p></p><p></p>\",\"Answers\":[{\"DisplayText\":\"Signing of Payroll Checks\",\"QuestionToAnswersId\":\"1\",\"IsAnswerToQuestion\":\"1\"},{\"DisplayText\":\"To Record transactions approved by management\",\"QuestionToAnswersId\":\"2\",\"IsAnswerToQuestion\":\"0\"},{\"DisplayText\":\"Performing data processing services\",\"QuestionToAnswersId\":\"3\",\"IsAnswerToQuestion\":\"0\"},{\"DisplayText\":\"Preparation of  Financial Statements\",\"QuestionToAnswersId\":\"4\",\"IsAnswerToQuestion\":\"0\"}]}";
        $myJSONString = trim($inJSONString);
        if(validate::isValidJSONString($myJSONString)){
            $myJSONArray = json_decode($myJSONString, true); //is a single questionResponse object, but in array form

            $checkResult = self::checkValidQuestionResponseArray($myJSONArray);
            if($checkResult['Result']){
            //if(true){
                //turn data back into questionResponse object
                $QuestionResponse = new questionResponse();
                $QuestionResponse->Question = $myJSONArray['Question'];
                $QuestionResponse->CorrectAnswerIndex = (int)$myJSONArray['CorrectAnswerIndex'];
                $QuestionResponse->Explanation = $myJSONArray['Explanation'];
                $QuestionResponse->QuestionId = (int)$myJSONArray['QuestionId'];
                $QuestionResponse->Answers = $myJSONArray['Answers'];
                $QuestionResponse->IsApprovedForUse = (int)$myJSONArray['IsApprovedForUse'];
                $QuestionResponse->IsActive = (int)$myJSONArray['IsActive'];
                $QuestionResponse->IsDeprecated = (int)$myJSONArray['IsDeprecated'];
                $QuestionResponse->QuestionClientImage = $myJSONArray['QuestionClientImage'];
                $QuestionResponse->QuestionClientId = $myJSONArray['QuestionClientId'];
                //$QuestionResponse->SectionTypeId = (int)$myJSONArray['SectionTypeId'];

                /*foreach ($QuestionResponse->Answers as $AnswerIndex => $Answer) {
                    if($QuestionResponse->Explanation == $AnswerIndex){
                        $QuestionResponse->Answers[$AnswerIndex]['IsAnswerToQuestion'] = 1;
                    }
                    else{
                        $QuestionResponse->Answers[$AnswerIndex]['IsAnswerToQuestion'] = 0;
                    }
                }*/

                $updateResult = self::updateQuestionResponse($QuestionResponse, $inLastModifiedBy);
                array_push($updateResultsArray, (bool)$updateResult['Result']);


            }
            else{
                $returnArray['Reason'] = "Invalid input";
                $inMessage = "JSON array was not valid in ".__METHOD__."() . Relevant trace: ".$checkResult['Reason'];
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $returnArray['Reason'] = "Invalid input";
            $inMessage = "JSON string was not valid in ".__METHOD__."() . ";
            util_errorlogging::LogBrowserError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            return $returnArray;
        }


        //check updateResultsArray
        if(!empty($updateResultsArray)){
            if(!in_array(false, $updateResultsArray)){
                $questionObjects = question::getAllQuestionsByIds(array($QuestionResponse->QuestionId));

                if(count($questionObjects) > 0){
                    $responsesArray = self::buildQuestionsAndAnswersArray($questionObjects);
                    $returnArray['Result'] = 1;
                    $returnArray['QuestionResponse'] = $responsesArray;
                }
                else{
                    $returnArray['Result'] = 0;
                    $returnArray['Reason'] = "New question was not found";
                }
            }
            else{
                $returnArray['Reason'] = "Error updating question and answers";
                $inMessage = "Error updating question and answers in ".__METHOD__."() . Some updates were done, but there was at least one update that failed. Called by ".$inLastModifiedBy."()";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            }
        }
        else{
            $returnArray['Reason'] = "Error updating question and answers.";
            $inMessage = "Error updating question and answers in ".__METHOD__."() . No updates were even attempted due to invalid JSON data. Called by ".$inLastModifiedBy."()";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
        }

        return $returnArray;

    }

    public static function copyQuestionResponse($inQuestionId, $inLastModifiedBy){
        $returnArray = array(
            'Result' => 0,
            'Reason' => "",
            'QuestionResponse' => ""
        );

        if(validate::tryParseInt($inQuestionId)){
            $myResponse = new questionResponse();
            $newQuestionIdObj = question::copyQuestionAndAnswersById($inQuestionId);
            $newQuestionId = $newQuestionIdObj[0]->newQuestionId;

            if($newQuestionId > 0){
                $questionObjects = question::getAllQuestionsByIds(array($newQuestionId));

                if(count($questionObjects) > 0){
                    $responsesArray = self::buildQuestionsAndAnswersArray($questionObjects);
                    $returnArray['Result'] = 1;
                    $returnArray['QuestionResponse'] = $responsesArray;
                }
                else{
                    $returnArray['Result'] = 0;
                    $returnArray['Reason'] = "New question was not found";
                }

            }
            else{
                $returnArray['Reason'] = "Error copying Question/Answers";
                $inMessage = "New QuestionId was invalid (0) in ".__METHOD__."() . Called by ".$inLastModifiedBy."()";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            }

        }
        else{
            $returnArray['Reason'] = "QuestionId was not an integer";
            $inMessage = "QuestionId was not an integer in ".__METHOD__."() . Called by ".$inLastModifiedBy."()";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
        }




        return $returnArray;
    }


    //////////////////////////////////////////
    // HELPERS
    //////////////////////////////////////////

    public static function checkValidQuestionHistoryArray($inArray){
        $returnArray = array(
            'Result' => false,
            'Reason' => ""
        );
        $validTestModeTypes = array(enum_TestModeType::Practice,enum_TestModeType::TestSimulation);
        $myBool = true;
        if(validate::isNotNullOrEmpty_Array($inArray)){
            //check each field
            if(isset($inArray['questionId'])){
                if(!validate::tryParseInt($inArray['questionId'])){
                    $returnArray['Reason'] .= "questionId was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "questionId was not set. ";
                $myBool = false;
            }

            if(isset($inArray['accountUserId'])){
                if(!validate::tryParseInt($inArray['accountUserId'])){
                    $returnArray['Reason'] .= "accountUserId was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "accountUserId was not set. ";
                $myBool = false;
            }

            if(isset($inArray['timeTaken'])){
                if(!validate::tryParseInt($inArray['timeTaken'])){
                    $returnArray['Reason'] .= "timeTaken was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "timeTaken was not set. ";
                $myBool = false;
            }

            if(isset($inArray['mode'])){
                if(!validate::tryParseInt($inArray['mode'])){
                    $returnArray['Reason'] .= "mode was not an integer. ";
                    $myBool = false;
                }
                else{
                    if(!in_array((int)$inArray['mode'], $validTestModeTypes)){
                        $returnArray['Reason'] .= "mode was not an expected integer ";
                        $myBool = false;
                    }
                }
            }
            else{
                $returnArray['Reason'] .= "mode was not set. ";
                $myBool = false;
            }

            if(isset($inArray['selectedAnswer'])){
                if(!validate::tryParseInt($inArray['selectedAnswer'])){
                    $returnArray['Reason'] .= "selectedAnswer was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "selectedAnswer was not set. ";
                $myBool = false;
            }

            if(isset($inArray['answeredCorrectly'])){
                if(!validate::tryParseInt($inArray['answeredCorrectly'])){
                    $returnArray['Reason'] .= "answeredCorrectly was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "answeredCorrectly was not set. ";
                $myBool = false;
            }
        }
        else{
            $returnArray['Reason'] .= "QuestionHistory array was empty. ";
            $myBool = false;
        }

        $returnArray['Result'] = $myBool;

        return $returnArray;
    }

    public static function checkValidQuestionResponseArray($inArray){
        $returnArray = array(
            'Result' => false,
            'Reason' => ""
        );

        $myBool = true;
        if(validate::isNotNullOrEmpty_Array($inArray)){
            //questionid
            if(isset($inArray['QuestionId'])){
                if(!validate::tryParseInt($inArray['QuestionId'])){
                    $returnArray['Reason'] .= "QuestionId was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "QuestionId was not set. ";
                $myBool = false;
            }

            //correctanswerindex
            /*
            if(isset($inArray['CorrectAnswerIndex'])){
                if(!validate::tryParseInt($inArray['CorrectAnswerIndex'])){
                    $returnArray['Reason'] .= "CorrectAnswerIndex was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "CorrectAnswerIndex was not set. ";
                $myBool = false;
            }
            */

            //question client id
            if(isset($inArray['QuestionClientId'])){
                if(!validate::isNotNullOrEmpty_String($inArray['QuestionClientId'])){
                    $returnArray['Reason'] .= "QuestionClientId was an empty string or null. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "QuestionClientId was not set. ";
                $myBool = false;
            }

            //question client image
            if(isset($inArray['QuestionClientImage'])){
                /*if(!validate::isNotNullOrEmpty_String($inArray['QuestionClientImage'])){
                    $returnArray['Reason'] .= "QuestionClientImage was an empty string or null. ";
                    $myBool = false;
                }*/
            }
            else{
                $returnArray['Reason'] .= "QuestionClientImage was not set. ";
                $myBool = false;
            }

            //IsApprovedForUse
            if(isset($inArray['IsApprovedForUse'])){
                if(!validate::tryParseInt($inArray['IsApprovedForUse'])){
                    $returnArray['Reason'] .= "IsApprovedForUse was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "IsApprovedForUse was not set. ";
                $myBool = false;
            }

            //IsDeprecated
            if(isset($inArray['IsDeprecated'])){
                if(!validate::tryParseInt($inArray['IsDeprecated'])){
                    $returnArray['Reason'] .= "IsDeprecated was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "IsDeprecated was not set. ";
                $myBool = false;
            }

            //IsActive
            if(isset($inArray['IsActive'])){
                if(!validate::tryParseInt($inArray['IsActive'])){
                    $returnArray['Reason'] .= "IsActive was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "IsActive was not set. ";
                $myBool = false;
            }

            //explanation
            if(isset($inArray['Explanation'])){
                if(!validate::isNotNullOrEmpty_String($inArray['Explanation'])){
                    $returnArray['Reason'] .= "Explanation was not null or empty. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "Explanation was not set. ";
                $myBool = false;
            }

            //question text
            if(isset($inArray['Question'])){
                if(!validate::isNotNullOrEmpty_String($inArray['Question'])){
                    $returnArray['Reason'] .= "Question was an empty string or null. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "Question was not set. ";
                $myBool = false;
            }

            //answers array
            if(isset($inArray['Answers'])){
                if(validate::isNotNullOrEmpty_Array($inArray['Answers'])){
                    foreach ($inArray['Answers'] as $key => $Answer) {
                        if(validate::isNotNullOrEmpty_Array($Answer)){
                            if(isset($Answer['QuestionToAnswersId']) && validate::tryParseInt($Answer['QuestionToAnswersId'])){
                                if(isset($Answer['DisplayText']) && validate::isNotNullOrEmpty_String($Answer['DisplayText'])){
                                    if(isset($Answer['IsAnswerToQuestion']) && validate::tryParseInt($Answer['IsAnswerToQuestion'])){
                                        //good to go
                                    }
                                    else{
                                        $returnArray['Reason'] .= "Answers[".$key."] IsAnswerToQuestion was not set or a valid boolean. ";
                                        $myBool = false;
                                    }
                                }
                                else{
                                    $returnArray['Reason'] .= "Answers[".$key."] DisplayText was not set or was empty. ";
                                    $myBool = false;
                                }
                            }
                            else{
                                $returnArray['Reason'] .= "Answers[".$key."] QuestionToAnswersId was not set or not an integer. ";
                                $myBool = false;
                            }
                        }
                        else{
                            $returnArray['Reason'] .= "Answers[".$key."] was empty. ";
                            $myBool = false;
                        }
                    }
                }
                else{
                    $returnArray['Reason'] .= "Answers was empty. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "Answers was not set. ";
                $myBool = false;
            }

        }
        else{
            $returnArray['Reason'] .= "QuestionResponse array was empty. ";
            $myBool = false;
        }


        $returnArray['Result'] = $myBool;

        return $returnArray;
    }

    public static function checkValidQuestionHistoryFilterArray($inArray){
        $returnArray = array(
            'Result' => false,
            'Reason' => ""
        );

        $myBool = true;
        if(validate::isNotNullOrEmpty_Array($inArray)){
            //get enums to compare against
            $refHelper = new ReflectionClass("enum_SectionType");
            $ValidSectionTypeIds =  $refHelper->getConstants();
            $refHelper = new ReflectionClass("enum_QuestionHistoryResult");
            $ValidResultIds =  $refHelper->getConstants();
            $refHelper = new ReflectionClass("enum_QuestionHistoryOrderBy");
            $ValidOrderByIds =  $refHelper->getConstants();

            //SectionTypeId
            if(isset($inArray['SectionTypeId'])){
                if(!validate::tryParseInt($inArray['SectionTypeId'])){
                    $returnArray['Reason'] .= "SectionTypeId was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "SectionTypeId was not set. ";
                $myBool = false;
            }

            //ResultId
            if(isset($inArray['ResultId'])){
                if(!validate::tryParseInt($inArray['ResultId'])){
                    $returnArray['Reason'] .= "ResultId was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "ResultId was not set. ";
                $myBool = false;
            }

            //OrderById
            if(isset($inArray['OrderById'])){
                if(!validate::tryParseInt($inArray['OrderById'])){
                    $returnArray['Reason'] .= "OrderById was not an integer. ";
                    $myBool = false;
                }
            }
            else{
                $returnArray['Reason'] .= "OrderById was not set. ";
                $myBool = false;
            }

        }
        else{
            $returnArray['Reason'] .= "Filter array was empty. ";
            $myBool = false;
        }


        $returnArray['Result'] = $myBool;

        return $returnArray;

    }

    public static function buildQuestionsAndAnswersArray($inQuestionObjects){

        $questionIdArray = array();
        $responsesArray = array();

        //get question ids for getting questions
        foreach($inQuestionObjects as $key => $value){
            array_push($questionIdArray, $value->QuestionId);
        }

        //get answers for questions returned
        $answerObjects = question::getAllAnswersForUI($questionIdArray);

        foreach($inQuestionObjects as $stdQuestionIndex => $Question){
            $Response = new questionResponse();
            $Response->Result = 1;
            $Response->QuestionId = $Question->QuestionId;
            $Response->Question = $Question->DisplayText;
            $Response->Explanation = $Question->Explanation;
            $Response->QuestionClientId = $Question->QuestionClientId;
            //find answers for this question
            $answerCount = 0;
            foreach($answerObjects as $stdAnswerIndex => $Answer){
                if($Answer->QuestionId == $Question->QuestionId){
                    $isCorrectAnswer = (bool)$Answer->IsAnswerToQuestion;
                    //unset($Answer->QuestionId);
                    //unset($Answer->IsAnswerToQuestion);
                    array_push($Response->Answers, $Answer);
                    if($isCorrectAnswer){
                        end($Response->Answers);
                        $Response->CorrectAnswerIndex = key($Response->Answers);
                    }
                    $answerCount += 1;
                }
            }
            if($answerCount == 0){
                $Response->Result = 0;
                $Response->Reason .= "No answers were found for the question.";
            }
            array_push($responsesArray, $Response);
        }

        return $responsesArray;
    }

    public static function buildQuestionsAndAnswersArray_Admin($inQuestionObjects){

        $questionIdArray = array();
        $responsesArray = array();

        //get question ids for getting questions
        foreach($inQuestionObjects as $key => $value){
            array_push($questionIdArray, $value->QuestionId);
        }

        //get answers for questions returned
        $answerObjects = question::getAllAnswersForUI($questionIdArray);

        foreach($inQuestionObjects as $stdQuestionIndex => $Question){
            $Response = new questionResponse();
            $Response->Result = 1;
            $Response->QuestionClientId = $Question->QuestionClientId;
            $Response->QuestionTypeId = $Question->QuestionTypeId;
            $Response->SectionTypeId = $Question->SectionTypeId;
            $Response->QuestionClientImage = $Question->QuestionClientImage;
            $Response->IsApprovedForUse = $Question->IsApprovedForUse;
            $Response->IsActive = $Question->IsActive;
            $Response->QuestionId = $Question->QuestionId;
            $Response->Question = $Question->DisplayText;
            $Response->Explanation = $Question->Explanation;
            $Response->IsDeprecated = $Question->IsDeprecated;

            //find answers for this question
            $answerCount = 0;
            foreach($answerObjects as $stdAnswerIndex => $Answer){
                if($Answer->QuestionId == $Question->QuestionId){
                    $isCorrectAnswer = (bool)$Answer->IsAnswerToQuestion;
                    array_push($Response->Answers, $Answer);
                    if($isCorrectAnswer){
                        end($Response->Answers);
                        $Response->CorrectAnswerIndex = key($Response->Answers);
                    }
                    $answerCount += 1;
                }
            }
            if($answerCount == 0){
                $Response->Result = 0;
                $Response->Reason .= "No answers were found for the question.";
            }
            array_push($responsesArray, $Response);
        }

        return $responsesArray;
    }

    public static function updateQuestionResponse(questionResponse $inQuestionResponse, $inLastModifiedBy){
        //return
        $returnArray = array(
            'Result' => 1,
            'Reason' => "",
            'UpdateArray' => array()
        );

        $updatesArray = array();

        //update question
        $QuestionId = $inQuestionResponse->QuestionId;

        $updateQuestionValuesArray = array(
            'QuestionTypeId' => ':QuestionTypeId',
            'DisplayText' => ':DisplayText',
            'Explanation' => ':Explanation',
            //'SectionTypeId' => ':SectionTypeId',
            //'Tags' => ':Tags',
            'QuestionClientImage' => ':QuestionClientImage',
            'QuestionClientId' => ':QuestionClientId',
            'IsApprovedForUse' => ':IsApprovedForUse',
            'IsActive' => ':IsActive',
            'IsDeprecated' => ':IsDeprecated',
            'LastModifiedBy' => ':LastModifiedBy'
        );

        $updateQuestionPrepareArray = array(
            ':QuestionTypeId' => $inQuestionResponse->QuestionTypeId,
            ':DisplayText' => $inQuestionResponse->Question,
            ':Explanation' => $inQuestionResponse->Explanation,
            //':SectionTypeId' => $inQuestionResponse->SectionTypeId,
            //':Tags' => $inQuestionResponse->Tags,
            ':QuestionClientImage' => $inQuestionResponse->QuestionClientImage,
            ':QuestionClientId' => $inQuestionResponse->QuestionClientId,
            ':IsApprovedForUse' => $inQuestionResponse->IsApprovedForUse,
            ':IsActive' => $inQuestionResponse->IsActive,
            ':IsDeprecated' => $inQuestionResponse->IsDeprecated,
            ':LastModifiedBy' => __METHOD__."--".$inLastModifiedBy
        );

        //print_r($updateQuestionValuesArray);
        //print_r($updateQuestionPrepareArray);
        $questionUpdateResult = question::updateQuestion($QuestionId, $updateQuestionValuesArray, $updateQuestionPrepareArray, __METHOD__);

        array_push($updatesArray, $questionUpdateResult);

        //update answers
        foreach($inQuestionResponse->Answers as $key => $Answer){
            $updateAnswerValuesArray = array(
                //'QuestionToAnswersId' => ':QuestionToAnswersId',
                //'QuestionId' => ':QuestionId',
                'DisplayText' => ':DisplayText',
                'IsAnswerToQuestion' => ':IsAnswerToQuestion',
                'LastModifiedBy' => ':LastModifiedBy'
            );

            $updateAnswerPrepareArray = array(
                //':QuestionToAnswersId' => $Answer['QuestionToAnswersId'],
                //':QuestionId' => $Answer['QuestionId'],
                ':DisplayText' => $Answer['DisplayText'],
                ':IsAnswerToQuestion' => $Answer['IsAnswerToQuestion'],
                ':LastModifiedBy' => __METHOD__."--".$inLastModifiedBy
            );

            //print_r($updateAnswerValuesArray);
            //print_r($updateAnswerPrepareArray);

            $answerUpdateResult = question::updateAnswer((int)$Answer['QuestionToAnswersId'], $updateAnswerValuesArray, $updateAnswerPrepareArray, __METHOD__);
            array_push($updatesArray, $answerUpdateResult);
        }

        if(!empty($updatesArray) && in_array(false, $updatesArray)){
            $returnArray['Result'] = 0;
            $returnArray['Reason'] = "Some errors updating";
        }

        return $returnArray;

    }

    private static function returnQuestionsResponseDTO($inQuestionResponsesArray){

        foreach($inQuestionResponsesArray as $key => $QuestionResponseObject){
            foreach($$QuestionResponseObject as $AnswerIndex => $AnswerArray){
                unset($AnswerArray['QuestionId']);
                unset($AnswerArray['IsAnswerToQuestion']);
            }
        }

        return $inQuestionResponsesArray;

    }

    public static function checkValidSectionTypeToSubscriptionType($inSubscriptionTypeId, $inSectionTypeId, $inLastModifiedBy){
        $returnArray = array(
            'Result' => false,
            'Reason' => ""
        );

        $validSectionTypeIds = self::returnValidPublicSectionTypeArray();
        $validSubscriptionTypeIds = self::returnValidPublicSubscriptionTypeArray();
        $SubscriptionToSectionTypeIds = array(
            enum_SectionType::FAR => false,
            enum_SectionType::AUD => false,
            enum_SectionType::BEC => false,
            enum_SectionType::REG => false
        );

        //validation
        if(validate::tryParseInt($inSubscriptionTypeId) && validate::tryParseInt($inSectionTypeId)){
            $SubscriptionTypeId = (int)$inSubscriptionTypeId;
            $SectionTypeId = (int)$inSectionTypeId;

            if(in_array($SubscriptionTypeId, $validSubscriptionTypeIds) && in_array($SectionTypeId, $validSectionTypeIds)){
                $SubscriptionResult = question::getSubscriptionTypes(array($SubscriptionTypeId));
                if(count($SubscriptionResult) == 1){
                    $Subscription = $SubscriptionResult[0];
                    //check each flag
                    $SubscriptionToSectionTypeIds[1] = (bool)$Subscription->HasFARModule ? true : false;
                    $SubscriptionToSectionTypeIds[2] = (bool)$Subscription->HasAUDModule ? true : false;
                    $SubscriptionToSectionTypeIds[3] = (bool)$Subscription->HasBECModule ? true : false;
                    $SubscriptionToSectionTypeIds[4] = (bool)$Subscription->HasREGModule ? true : false;

                    //$SubscriptionToSectionTypeIds[".$SubscriptionTypeId."]
                    $isValid = false;
                    foreach ($SubscriptionToSectionTypeIds as $key => $value) {
                        if($key == $SectionTypeId && $value == true){
                            $isValid = true;
                            break;
                        }
                    }

                    if($isValid){
                        $returnArray['Result'] = true;
                    }
                    else{
                        $returnArray['Reason'] .= "Section or license was invalid ";
                        $returnArray['Result'] = false;
                    }
                }
                else{
                    $inMessage = "SubscriptionTypeId was not found in database in ".__METHOD__."() . Values was ".$SubscriptionTypeId." respectively. Called by ".$inLastModifiedBy."()";
                    util_errorlogging::LogGeneralError(enum_LogType::Blocker, $inMessage, __METHOD__, __FILE__);
                    $returnArray['Reason'] .= "Section or license was invalid ";
                    $returnArray['Result'] = false;
                }
            }
            else{
                $inMessage = "SectionTypeId and/or SubscriptionTypeId were not valid choices in ".__METHOD__."() . Values were ".$SectionTypeId." and ".$SubscriptionTypeId." respectively. Called by ".$inLastModifiedBy."()";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                $returnArray['Reason'] .= "Section or license was invalid ";
                $returnArray['Result'] = false;
            }

        }
        else{
            $inMessage = "SectionTypeId and/or SubscriptionTypeId was not integer in ".__METHOD__."() . Called by ".$inLastModifiedBy."()";
            util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
            $returnArray['Reason'] .= "Section or license was invalid ";
            $returnArray['Result'] = false;
        }

        return $returnArray;
    }

    public static function returnValidPublicSubscriptionTypeArray(){
        //TODO: may want to optimize using caching
        $refl = new ReflectionClass('enum_SubscriptionType');
        $myArray = $refl->getConstants();

        unset($myArray['Perpetual']);

        return $myArray;
    }

    public static function returnValidPublicSectionTypeArray(){
        //TODO: may want to optimize using caching
        $refl = new ReflectionClass('enum_SectionType');
        $myArray = $refl->getConstants();

        unset($myArray['All']);

        return $myArray;
    }

    public static function returnValidPublicPracticeNumberOfQuestionsArray(){
        //TODO: may want to optimize using caching
        $refl = new ReflectionClass('enum_PracticeNumberOfQuestions');
        $myArray = $refl->getConstants();

        //unset($myArray['All']);

        return $myArray;
    }

}

class questionResponse{
    public $Result; //bit
    public $Reason; //string
    public $QuestionClientId; //string
    public $QuestionTypeId; //int
    public $SectionTypeId; //int
    public $QuestionClientImage; //string
    public $IsApprovedForUse; //int
    public $IsActive; //int
    public $Question; //string
    public $Answers; //array( array() )
    public $CorrectAnswerIndex; //int
    public $Explanation; //string
    public $QuestionId; //int
    public $IsDeprecated; //int



    function questionResponse(){
        $this->Result = 0;
        $this->Reason = "";
        $this->QuestionClientId = "";
        $this->QuestionTypeId = 1;
        $this->SectionTypeId = 0;
        $this->QuestionClientImage = "";
        $this->IsApprovedForUse = 0;
        $this->IsActive = 0;
        $this->Question = "";
        $this->Answers = array();
        $this->CorrectAnswerIndex = 0;
        $this->Explanation = "";
        $this->QuestionId = 0;
        $this->IsDeprecated = 0;
    }
}

class questionHistory{
    public $Metrics;
    public $QuestionResponse;
    public $Summary;


    function questionHistory(){
        $this->Metrics = array();
        $this->QuestionResponse = array();
        $this->Summary = array();
    }
}

?>