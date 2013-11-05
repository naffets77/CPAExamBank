<?php
require_once(realpath(__DIR__)."/config.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(service_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(service_configuration::$environment_librarypath."/validate.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_enums.php");
include_once(service_configuration::$ciborium_librarypath."/ciborium_question.php");
include_once(service_configuration::$ciborium_librarypath."/ciboriumlib_account.php");

class service_question{

    //service name
    static $service = "service_question";

    /**
     * Service: getAllQuestionsAndAnswers()
     * Gets all questions and answers for simulator
     *
     * POST Input:
     *      Hash
     *
     * @return array
     *      Reason
     *      Result
     *      %array(questionResponse)
     */
    static function getAllQuestionsAndAnswers(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__) && (bool)$_SESSION['Account']->IsAdmin){
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "Hash" => $hash,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
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

            $myResponse = ciborium_question::getAllQuestionsAndAnswersForUI(false);
            return $myResponse;

        }
        else{
            $myArray['Reason'] = "User was no longer logged in or does not have sufficient privileges";
            return $myArray;
        }
    }

    /**
     * Service: getAllQuestionsAndAnswers_Manager()
     * Gets all questions and answers for Question Manager portal
     *
     * POST Input:
     *      Hash
     *
     * @return array
     *      Reason
     *      Result
     *      %array(questionResponse)
     */
    static function getAllQuestionsAndAnswers_Manager(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__) && (bool)$_SESSION['Account']->IsAdmin){
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "Hash" => $hash,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
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

            $myResponse = ciborium_question::getAllQuestionsAndAnswersForUI();
            return $myResponse;

        }
        else{
            $myArray['Reason'] = "User was no longer logged in or does not have sufficient privileges";
            return $myArray;
        }
    }

    /**
     * Service: getQuestionsAndAnswers()
     * Gets questions and answers for the public UI
     *
     * POST Input:
     *      Hash
     *      SectionTypeId
     *      QuestionAmount
     *
     * @return array
     *      Reason
     *      Result
     *      %array(questionResponse)
     */
    static function getQuestionsAndAnswers(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );

        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $SectionTypeId = validate::requirePostField('SectionTypeId', self::$service, __FUNCTION__);
            $QuestionAmount = validate::requirePostField('QuestionAmount', self::$service, __FUNCTION__);
            //$FilterArray = validate::requirePostField('Filters', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "Hash" => $hash,
                "SectionTypeId" => $SectionTypeId,
                "QuestionAmount" => $QuestionAmount,
                //'FilterArray' => $FilterArray,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
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

            //Check SectionTypeId against the Licenses->SubscriptionTypeId to see if it is valid
            //$mySubscriptionCheckResponse = ciborium_question::checkValidSectionTypeToSubscriptionType($_SESSION['Licenses']->SubscriptionTypeId, $SectionTypeId, __METHOD__."[".$_SESSION['Account']->AccountUserId."]");

            $myLicenseToSectionTypeResult = false; //for checking if user is still subscribed actively (not expired) to the section
            $mySectionTypeId = (int)$SectionTypeId;
            foreach ($_SESSION['Subscriptions'] as $stdKey => $object) {
                $myExpirationTime = util_datetime::getDateTimeToPHPTime($object->DateExpiration);
                if((int)$object->SectionTypeId == $mySectionTypeId && time() < $myExpirationTime){
                    $myLicenseToSectionTypeResult = true;
                    break;
                }
            }


            if($myLicenseToSectionTypeResult || (int)$_SESSION['Licenses']->SubscriptionTypeId == enum_SubscriptionType::Free || (int)$QuestionAmount == enum_PracticeNumberOfQuestions::FreeLimit){

                //If Free license, limit number of return questions automatically
                $QuestionAmount = (int)$_SESSION['Licenses']->SubscriptionTypeId == enum_SubscriptionType::Free ? enum_PracticeNumberOfQuestions::FreeLimit : $QuestionAmount;
                $isFreeAccount = ((int)$_SESSION['Licenses']->SubscriptionTypeId == enum_SubscriptionType::Free) || !$myLicenseToSectionTypeResult ? true : false;
                $myResponse = ciborium_question::getQuestionsAndAnswersBySectionType($SectionTypeId, $QuestionAmount, $_SESSION['Account']->AccountUserId, $_SESSION['Account']->IsAdmin, $isFreeAccount, enum_QuestionType::MultipleChoice);
                return $myResponse;
            }
            else{
                $inMessage = "SubscriptionTypeId (".$_SESSION['Licenses']->SubscriptionTypeId.") was not valid for SectionTypeId (".$SectionTypeId.") selected.";
                util_errorlogging::LogGeneralError(enum_LogType::Normal, $inMessage, __METHOD__, __FILE__);
                $myArray['Reason'] = "Invalid input request";
                return $myArray;
            }
        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }
    }


    /**
     * Service: getAccountUserQuestionHistory()
     * Gets all the user's Question History for the public UI with filters
     *
     * POST Input:
     *      Hash
     *      Filters
     *
     * @return array
     *      Reason
     *      Result
     *      %array(
     *          questionHistory->Metrics,
     *          questionHistory->QuestionResponse,
     *          questionHistory-> Summary
     *      )
     */
    static function getAccountUserQuestionHistory(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $JSONObject = validate::requirePostField('Filters', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "Hash" => $hash,
                "Filters" => $JSONObject,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
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
            $AccountUserId = $_SESSION['Account']->AccountUserId;

            $myJSONString = trim($JSONObject);
            if(validate::isValidJSONString($myJSONString)){
                $FilterArray = json_decode($myJSONString, true);
                $myResponse = ciborium_question::getAccountUserQuestionHistoryWithFiltersById($AccountUserId, $FilterArray, __METHOD__."[".$_SESSION['Account']->AccountUserId."]");
                return $myResponse;
            }
            else{
                $myArray['Reason'] = "Invalid variable(s)";
                return $myArray;
            }



        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }
    }


    /**
     * Service: saveQuestionHistory()
     * Saves user's  Question History for the session
     *
     * POST Input:
     *      Hash
     *      QuestionHistory (JSON Object)
     *
     * @return array
     *      Reason
     *      Result
     */
    static function saveQuestionHistory(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__)){
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $JSONObject = validate::requirePostField('QuestionHistory', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "Hash" => $hash,
                "QuestionHistory" => $JSONObject,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
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

            $myResponse = ciborium_question::saveAccountUserQuestionHistory($JSONObject, __METHOD__."[".$_SESSION['Account']->AccountUserId."]");
            return $myResponse;

        }
        else{
            $myArray['Reason'] = "User was no longer logged in";
            return $myArray;
        }
    }


    /**
     * Service: updateQuestion()
     * Updates the question / answer set from the Admin UI
     *
     * POST Input:
     *      Hash
     *      QuestionUpdateResponse (JSON object)
     *
     * @return array
     *      Reason
     *      Result
     *      QuestionResponse
     */
    static function updateQuestion(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__) && (bool)$_SESSION['Account']->IsAdmin){
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $JSONObject = validate::requirePostField('QuestionUpdateResponse', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "Hash" => $hash,
                "QuestionUpdateResponse" => $JSONObject,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
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

            $myResponse = ciborium_question::updateQuestionAndAnswers($JSONObject, __METHOD__."[".$_SESSION['Account']->AccountUserId."]");
            return $myResponse;

        }
        else{
            $myArray['Reason'] = "User was no longer logged in or does not have sufficient privileges";
            return $myArray;
        }
    }


    /**
     * Service: copyQuestion()
     * Copys a question / answer set and returns it to the Admin UI
     *
     * POST Input:
     *      Hash
     *      QuestionId
     *
     * @return array
     *      Reason
     *      Result
     *      QuestionResponse
     */
    static function copyQuestion(){
        $myArray = array(
            "Reason" => "",
            "Result" => 0
        );
        if(ciboriumlib_account::checkValidLogin(self::$service, __FUNCTION__) && (bool)$_SESSION['Account']->IsAdmin){
            $hash = validate::requirePostField('Hash', self::$service, __FUNCTION__);
            $QuestionId = validate::requirePostField('QuestionId', self::$service, __FUNCTION__);
            $hashCheckReturn = validate::requireValidHash(self::$service, __FUNCTION__);

            $checkValueArray = array(
                "Hash" => $hash,
                "QuestionId" => $QuestionId,
                "hashCheckResult" => (bool)$hashCheckReturn['Result']
            );

            if(in_array(null, $checkValueArray) || !$checkValueArray['hashCheckResult']){
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

            $myResponse = ciborium_question::copyQuestionResponse($QuestionId, __METHOD__."[".$_SESSION['Account']->AccountUserId."]");
            return $myResponse;

        }
        else{
            $myArray['Reason'] = "User was no longer logged in or does not have sufficient privileges";
            return $myArray;
        }
    }
}

?>