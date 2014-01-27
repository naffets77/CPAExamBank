<?php
require_once(realpath(__DIR__)."/config.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_browser.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_errorlogging.php");

/**
 * database : contains the generic database functions: select, update, etc.
 *
 * Uses PDO to perform MySQL transactions. Optional parameters are
 * In current use are: select, insert & update
 * @version 1.0
 * @author mrico
 */




class database
{

    public $dbc;

    function database(){
        $environment = library_configuration::$environment;
        $host = library_configuration::$dbhost;
        $dbname = library_configuration::$dbname;
        $dblogin = library_configuration::$dblogin;
        $dbpassword = library_configuration::$dbpassword;

        try {
            $this->dbc = new PDO("mysql:host=".$host.";dbname=".$dbname."", $dblogin, $dbpassword);

        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";

            $myErrorArray = array(
                'LogTypeId' => 1, //critical
                'Message' => "Could not connect to database ".$dbname." on ".$host.". Exception message: ".$e->getMessage(),
                'FileSource' => __FILE__,
                'IPAddress' => util_browser::getClientIPAddress(),
                'LastModifiedBy' => __METHOD__,
                'CreatedBy' => __METHOD__,
                'DateCreated' => util_datetime::getDateTimeNow(),
                'BrowserName' => null,
                'BrowserVersion' => null,
                'BrowserPlatform' => null,
                'BrowserCSSVersion' => null,
                'BrowserJSEnabled' => null
            );
            util_errorlogging::LogDBError($myErrorArray, library_configuration::error_toemail, library_configuration::error_fromemail);
            die();
        }
    }

    /**
     * @param $inTable
     * @param null $inSelectArray
     * @param string $inWhereClause
     * @param string $inOrderBy
     * @param string $inLimit
     * @param null $inPreparedArray
     * @param $inCaller
     * @return array|null
     */
    public static function select($inTable, $inSelectArray = null, $inWhereClause = "", $inOrderBy = "", $inLimit = "", $inPreparedArray = null, $inCaller){

        //checking not empty
        $tableProvided = !empty($inTable) ? true : false;
        $selectProvided = !empty($inSelectArray) ? true : false;
        $whereProvided = !empty($inWhereClause) ? true : false;
        $orderByProvided = !empty($inOrderBy) ? true : false;
        $limitProvided = !empty($inLimit) ? true : false;
        $prepareProvided = !empty($inPreparedArray) ? true : false;
        $callerProvided = !empty($inCaller) ? true : false;

        //checking type
        $tableTypeCorrect = is_string($inTable) ? true : false;
        if($inSelectArray != null){
            $selectTypeCorrect = ($selectProvided && is_array($inSelectArray)) ? true : false;
        }
        else{
            $selectTypeCorrect = (!$selectProvided && $inSelectArray == null) ? true : false;
        }
        $whereTypeCorrect = is_string($inWhereClause);
        $orderByTypeCorrect = is_string($inOrderBy);
        $limitTypeCorrect = is_string($inLimit);
        if($inPreparedArray != null){
            $prepareTypeCorrect = ($prepareProvided && is_array($inPreparedArray)) ? true : false;
        }
        else{
            $prepareTypeCorrect = (!$prepareProvided && $inPreparedArray == null) ? true : false;
        }
        $callerTypeCorrect = is_string($inCaller) ? true : false;

        $selectFields = ($selectProvided && is_array($inSelectArray)) ? implode(",", $inSelectArray) : "*";
        $whereClause = ($whereProvided && $whereTypeCorrect) ? "WHERE ".$inWhereClause : "";
        $orderBy = ($orderByProvided && $orderByTypeCorrect) ? "ORDER BY ".$inOrderBy : "";
        $limit = ($limitProvided && validate::tryParseInt($inLimit)) ? "LIMIT 0,".(string)$inLimit : "";

        //adding to array
        $ArrayToCheck = array(
            'Provided' => array(
                'Table' => $tableProvided,
                'Caller' => $callerProvided
            ),
            'Type' => array(
                'Table' => $tableTypeCorrect,
                'Select' => $selectTypeCorrect,
                'Where' => $whereTypeCorrect,
                'OrderBy' => $orderByTypeCorrect,
                'Limit' => $limitTypeCorrect,
                'Prepare' => $prepareTypeCorrect,
                'Caller' => $callerTypeCorrect
            )
        );
        $ArrayCheckResult = database::areTransactionVariablesValid($ArrayToCheck);

        if($ArrayCheckResult['Result']){
            try{
                $db = new database();
                $query = "SELECT $selectFields FROM $inTable $whereClause $orderBy $limit;";
                $sth= $db->dbc->prepare($query);
                if($whereProvided && $prepareProvided){
                    $sth->execute($inPreparedArray);
                    return $result = $sth->fetchAll(PDO::FETCH_OBJ);
                }
                elseif($whereProvided && !$prepareProvided){
                    $sth->execute();
                    return $result = $sth->fetchAll(PDO::FETCH_OBJ);
                }
                elseif(!$whereProvided && !$prepareProvided){
                    $sth->execute();
                    return $result = $sth->fetchAll(PDO::FETCH_OBJ);
                }
                else{
                    $errorMessage = "Where clause was not provided, but Prepare array was.";
                    util_errorlogging::LogGeneralError(1, $errorMessage, $inCaller."=>".__METHOD__, __FILE__);
                    return null;
                }


            }
            catch(PDOException $ex){
                util_errorlogging::LogGeneralError(1, $ex->getMessage(), $inCaller."=>".__METHOD__, __FILE__);
                return null;
            }
        }
        else{
            $errorMessage = "\$ArrayCheckResult['Result'] was false.";
            $errorMessage .= !empty($ArrayCheckResult['NotProvided']) ? "Variable(s) that were empty: ".$ArrayCheckResult['NotProvided'] : "";
            $errorMessage .= !empty($ArrayCheckResult['TypeNotCorrect']) ? "Variable(s) with incorrect type: ".$ArrayCheckResult['TypeNotCorrect'] : "";
            util_errorlogging::LogGeneralError(1, $errorMessage, $inCaller."=>".__METHOD__, __FILE__);
        }

    }

    /**
     * @param $inTable
     * @param null $inColumnsArray
     * @param $inValuesArray
     * @param null $inPrepareArray
     * @param $inCaller
     * @return int|string
     */
    public static function insert($inTable, $inColumnsArray = null, $inValuesArray, $inPrepareArray = null, $inCaller){

        $result = "0"; //transaction result

        //checking not empty
        $tableProvided = !empty($inTable) ? true : false;
        $columnsProvided = !empty($inColumnsArray) ? true : false;
        $valuesProvided = !empty($inValuesArray) ? true : false;
        $prepareProvided = !empty($inPrepareArray) ? true : false;
        $callerProvided = !empty($inCaller) ? true : false;

        //checking type
        $tableTypeCorrect = is_string($inTable) ? true : false;
        if($inColumnsArray != null){
            $columnsTypeCorrect = ($columnsProvided && is_array($inColumnsArray)) ? true : false;
        }
        else{
            $columnsTypeCorrect = (!$columnsProvided && $inColumnsArray == null) ? true : false;
        }
        $valuesTypeCorrect = is_array($inValuesArray) ? true : false;
        if($inPrepareArray != null){
            $prepareTypeCorrect = ($prepareProvided && is_array($inPrepareArray)) ? true : false;
        }
        else{
            $prepareTypeCorrect = (!$prepareProvided && $inPrepareArray == null) ? true : false;
        }
        $callerTypeCorrect = is_string($inCaller) ? true : false;


        //adding to array
        $ArrayToCheck = array(
            'Provided' => array(
                'Table' => $tableProvided,
                'Values' => $valuesProvided,
                'Caller' => $callerProvided
            ),
            'Type' => array(
                'Table' => $tableTypeCorrect,
                'Columns' => $columnsTypeCorrect,
                'Values' => $valuesTypeCorrect,
                'Prepare' => $prepareTypeCorrect,
                'Caller' => $callerTypeCorrect
            )
        );
        $ArrayCheckResult = database::areTransactionVariablesValid($ArrayToCheck);
        $DataArraysSameLength = false;
        if( $ArrayCheckResult['Result'] == true){
            if($columnsProvided && $prepareProvided){
                $DataArraysSameLength = (count($inColumnsArray) == count($inValuesArray)) && (count($inValuesArray) == count($inPrepareArray)) ? true : false;
            }
            elseif($columnsProvided && !$prepareProvided){
                $DataArraysSameLength = count($inColumnsArray) == count($inValuesArray) ? true : false;
            }
            elseif(!$columnsProvided && $prepareProvided){
                $DataArraysSameLength = count($inValuesArray) == count($inPrepareArray) ? true : false;
            }
            elseif(!$columnsProvided && !$prepareProvided){
                $DataArraysSameLength = true;
                //$DataArraysSameLength = count($inColumnsArray) == count($inValuesArray) ? true : false;
            }
            else{
                //nothing
            }

        }

        if($ArrayCheckResult['Result'] && $DataArraysSameLength){
            try{

                $db = new database();

                if($columnsProvided && $valuesProvided && $prepareProvided){
                    $columns = "".implode(", ", $inColumnsArray )."";
                    $values = "".implode(", ", $inValuesArray )."";
                    $query = "INSERT INTO $inTable($columns) VALUES($values)";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute($inPrepareArray);
                    //$sth->commit();
                    return $result = $sth->lastInsertId();
                }
                elseif($columnsProvided && $valuesProvided && !$prepareProvided){
                    $columns = "".implode(", ", $inColumnsArray )."";
                    $values = "'".implode("', '", $inValuesArray )."'";
                    $query = "INSERT INTO $inTable($columns) VALUES($values)";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute();
                    //$sth->commit();
                    return $result = $sth->lastInsertId();
                }
                elseif(!$columnsProvided && $valuesProvided && $prepareProvided){
                    $values = "".implode(", ", $inValuesArray )."";
                    $query = "INSERT INTO $inTable VALUES($values)";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute($inPrepareArray);
                    //$sth->commit();
                    return $result = $sth->lastInsertId();
                }
                elseif(!$columnsProvided && $valuesProvided && !$prepareProvided){
                    $values = "'".implode("', '", $inValuesArray )."'";
                    $query = "INSERT INTO $inTable VALUES($values)";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute();
                    //$sth->commit();
                    return $result = $sth->lastInsertId();
                }
                else{
                    $errorMessage = "Input set provided was not valid permutation.";
                    util_errorlogging::LogGeneralError(1, $errorMessage, $inCaller."=>".__METHOD__, __FILE__);
                    return $result;
                }

            }
            catch(PDOException $ex){
                //$db->dbc->rollBack();
                util_errorlogging::LogGeneralError(1, $ex->getMessage(), $inCaller."=>".__METHOD__, __FILE__);
                return $result;
            }
        }
        else{
            $errorMessage = "\$ArrayCheckResult['Result'] or \$DataArraysSameLength was false.";
            $errorMessage .= !empty($ArrayCheckResult['NotProvided']) ? "Variable(s) that were empty: ".$ArrayCheckResult['NotProvided'] : "";
            $errorMessage .= !empty($ArrayCheckResult['TypeNotCorrect']) ? "Variable(s) with incorrect type: ".$ArrayCheckResult['TypeNotCorrect'] : "";
            util_errorlogging::LogGeneralError(1, $errorMessage, $inCaller."=>".__METHOD__, __FILE__);
            return $result;
        }


    }

    /**
     * @param $inTable
     * @param $inColumnsToValuesArray
     * @param null $inPrepareArray
     * @param string $inWhereClause
     * @param $inCaller
     * @return bool|string
     */
    public static function update($inTable, $inColumnsToValuesArray, $inPrepareArray = null, $inWhereClause = "", $inCaller){

        $result = false; //transaction result

        //checking not empty
        $tableProvided = !empty($inTable) ? true : false;
        $columnsToValuesProvided = !empty($inColumnsToValuesArray) ? true : false;
        $prepareProvided = !empty($inPrepareArray) ? true : false;
        $whereProvided = !empty($inWhereClause) ? true : false;
        $callerProvided = !empty($inCaller) ? true : false;

        //checking type
        $tableTypeCorrect = is_string($inTable) ? true : false;
        $columnsToValuesTypeCorrect = is_array($inColumnsToValuesArray) ? true : false;
        if($inPrepareArray != null){
            $prepareTypeCorrect = ($prepareProvided && is_array($inPrepareArray)) ? true : false;
        }
        else{
            $prepareTypeCorrect = (!$prepareProvided && $inPrepareArray == null) ? true : false;
        }
        $whereTypeCorrect = is_string($inWhereClause) ? true : false;
        $callerTypeCorrect = is_string($inCaller) ? true : false;

        //adding to array
        $ArrayToCheck = array(
            'Provided' => array(
                'Table' => $tableProvided,
                'Columns' => $columnsToValuesProvided,
                'Caller' => $callerProvided
            ),
            'Type' => array(
                'Table' => $tableTypeCorrect,
                'Columns' => $columnsToValuesTypeCorrect,
                'Prepare' => $prepareTypeCorrect,
                'Where' => $whereTypeCorrect,
                'Caller' => $callerTypeCorrect
            )
        );
        $ArrayCheckResult = database::areTransactionVariablesValid($ArrayToCheck);
        $DataArraysSameLength = false;
        if( $ArrayCheckResult['Result'] == true && $prepareProvided){
            $DataArraysSameLength = count($inColumnsToValuesArray) == count($inPrepareArray) ? true : false;
        }


        if($ArrayCheckResult['Result'] && $DataArraysSameLength){

            try{
                $db = new database();
                $update = "";
                $count = count($inColumnsToValuesArray);
                foreach ($inColumnsToValuesArray as $key => $value) {
                    $count--;
                    if($count == 0){
                        $update .= $key." = ".$value."";
                    }
                    else{
                        $update .= $key." = ".$value.", ";
                    }
                }

                if($prepareProvided && $whereProvided){
                    $query = "UPDATE $inTable SET $update WHERE $inWhereClause";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute($inPrepareArray);
                    //$sth->commit();
                    return true;
                }
                elseif($prepareProvided && !$whereProvided){

                    $query = "UPDATE $inTable SET $update";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute($inPrepareArray);
                    //$sth->commit();
                    return true;
                }
                elseif(!$prepareProvided && $whereProvided){
                    $query = "UPDATE $inTable SET $update WHERE $inWhereClause";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute($inPrepareArray);
                    //$sth->commit();
                    return true;
                }
                elseif(!$prepareProvided && !$whereProvided){
                    $query = "UPDATE $inTable SET $update";
                    $sth = $db->dbc;
                    $q = $sth->prepare($query);
                    //$sth->beginTransaction();
                    $q->execute($inPrepareArray);
                    //$sth->commit();
                    return true;
                }
                else{
                    $errorMessage = "Input set provided was not valid permutation.";
                    util_errorlogging::LogGeneralError(1, $errorMessage, $inCaller."=>".__METHOD__, __FILE__);
                    return false;
                }
            }
            catch(Exception $ex){
                //$db->dbc->rollBack();
                util_errorlogging::LogGeneralError(1, $ex->getMessage(), $inCaller."=>".__METHOD__, __FILE__);
                return false;
            }

        }
        else{
            $errorMessage = "\$ArrayCheckResult['Result'] or \$DataArraysSameLength was false.";
            $errorMessage .= !empty($ArrayCheckResult['NotProvided']) ? "Variable(s) that were empty: ".$ArrayCheckResult['NotProvided'] : "";
            $errorMessage .= !empty($ArrayCheckResult['TypeNotCorrect']) ? "Variable(s) with incorrect type: ".$ArrayCheckResult['TypeNotCorrect'] : "";
            util_errorlogging::LogGeneralError(1, $errorMessage, $inCaller."=>".__METHOD__, __FILE__);
            return false;
        }

    }

    /**
     * checks input array to see that all conditions are set to true by the caller
     * @param $inArrayToCheck
     * @return array
     */
    private static function areTransactionVariablesValid($inArrayToCheck){
        $result = true;
        $NotProvided = "";
        $TypeNotCorrect = "";
        $ProvidedArray = $inArrayToCheck['Provided'];
        $TypeArray = $inArrayToCheck['Type'];

        foreach ($ProvidedArray as $key => $value) {
            if($value == false){
                $NotProvided .= ".$key.";
                $result = false;
                //break;
            }
        }

        foreach ($TypeArray as $key => $value) {
            if($value == false){
                $TypeNotCorrect .= ".$key.";
                $result = false;
                //break;
            }
        }

        return array(
            'Result' => $result,
            'NotProvided' => $NotProvided,
            'TypeNotCorrect' => $TypeNotCorrect
        );
    }

    public static function convertBitFieldData(){

    }

    //Parameters array must match exactly the order of the stored procedure
    public static function callStoredProcedure($inSPName, $inParametersArray = null, $inCaller){

        $returnArray = array();

        //checking input varaible are
        $storedProcedureProvided = !empty($inSPName) ? true : false;
        $parametersProvided = !empty($inParametersArray) ? true : false;
        $callerProvided = !empty($inCaller) ? true : false;

        //checking types
        $storedProcedureCorrect = is_string($inCaller) ? true : false;
        if($inParametersArray != null){
            $parametersTypeCorrect = ($parametersProvided && is_array($inParametersArray)) ? true : false;
        }
        else{
            $parametersTypeCorrect = (!$parametersProvided && $inParametersArray == null) ? true : false;
        }
        $callerTypeCorrect = is_string($inCaller) ? true : false;

        //adding to array
        $ArrayToCheck = array(
            'Provided' => array(
                'StoredProcedure' => $storedProcedureProvided,
                'Caller' => $callerProvided
            ),
            'Type' => array(
                'StoredProcedure' => $storedProcedureCorrect,
                'Parameters' => $parametersTypeCorrect,
                'Caller' => $callerTypeCorrect
            )
        );
        $ArrayCheckResult = database::areTransactionVariablesValid($ArrayToCheck);

        if($ArrayCheckResult['Result']){
            try{
                $db = new database();
                $parameters = (is_array($inParametersArray) && !empty($inParametersArray)) ? implode(", ", $inParametersArray) : "";
                $query = "CALL $inSPName(".$parameters.");";
                $sth= $db->dbc->prepare($query);
                $sth->execute();
                $result = $sth->fetchAll(PDO::FETCH_OBJ);

                return $returnArray = $result;
            }
            catch(PDOException $ex){
                util_errorlogging::LogGeneralError(1, $ex->getMessage(), $inCaller."=>".__METHOD__, __FILE__);
                return $returnArray;
            }
        }
        else{
            $errorMessage = "\$ArrayCheckResult['Result'] was false.";
            $errorMessage .= !empty($ArrayCheckResult['NotProvided']) ? "Variable(s) that were empty: ".$ArrayCheckResult['NotProvided'] : "";
            $errorMessage .= !empty($ArrayCheckResult['TypeNotCorrect']) ? "Variable(s) with incorrect type: ".$ArrayCheckResult['TypeNotCorrect'] : "";
            util_errorlogging::LogGeneralError(1, $errorMessage, $inCaller."=>".__METHOD__, __FILE__);
        }
        return $returnArray;
    }

    //WARNING: don't use this for any public calls
    public static function runCustomSelectQueryString($inQueryString, $inCaller){
        $returnArray = array();

        //Check to see if string starts with "SELECT"
        $beginningOfQuery = "INVALID";
        if(strlen($inQueryString > 6)){
            $beginningOfQuery = strtoupper(substr($inQueryString, 0, 5));
        }

        if($beginningOfQuery == "SELECT"){
            try{
                $db = new database();
                $query = $inQueryString;
                $sth= $db->dbc->prepare($query);
                $sth->execute();
                $result = $sth->fetchAll(PDO::FETCH_OBJ);

                return $returnArray = $result;
            }
            catch(PDOException $ex){
                util_errorlogging::LogGeneralError(1, $ex->getMessage(), $inCaller."=>".__METHOD__, __FILE__);
                return $returnArray;
            }
        }
        else{
            util_errorlogging::LogGeneralError(3, "Query string was invalid. Did not start with SELECT.", $inCaller."=>".__METHOD__, __FILE__);
            return $returnArray;
        }

        return $returnArray;
    }

    //destructor
    function __destruct(){
        $this->dbc = null;
    }

}