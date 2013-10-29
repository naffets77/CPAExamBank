<?php
require_once(realpath(__DIR__."/..")."/config.php");
//include_once(library_configuration::$environment_librarypath."/utilities/util_errorlogging.php");
include_once(library_configuration::$environment_librarypath."/validate.php");

class util_general{

    public static function stringValuesInAssociativeArray($inAssociativeArray){

        foreach($inAssociativeArray as $key => $value){

            switch(util_general::getVariableTypeAsString($value)){
                case "boolean":
                    $value = $value == true ? "true" : "false";
                    break;

                case "null":
                    $value = "NULL";
                    break;

                case "array":
                    $value = empty($value) ? "array()" : "array(values)";
                    break;

                case "unknown":
                    break;

                default:
                    $value = (string)$value;
                    break;
            }

        }

        return $inAssociativeArray;
    }

    public static function getVariableTypeAsString($inVar){
        if(is_object($inVar))
            return get_class($inVar);
        if(is_null($inVar))
            return "null";
        if(is_string($inVar))
            return "string";
        if(is_array($inVar))
            return "array";
        if(is_int($inVar))
            return "integer";
        if(is_bool($inVar))
            return "boolean";
        if(is_float($inVar))
            return "float";
        if(is_resource($inVar))
            return "resource";
        //throw new NotImplementedException();
        return "unknown";
    }

    public static function cleanBitValue($inBitValue){
        return ord($inBitValue);
    }

    public static function getBaseURL(){
        $env = library_configuration::$environment;
        if($env == "prod"){
            return "http://www.cpaexambank.com";
        }
        else{
            return "http://".$env.".cpaexambank.com";
        }
    }

    public static function getProtectedValue($inClass, $inPropertyName){
        $class = new ReflectionClass($inClass);
        $property = $class->getProperty($inPropertyName);
        $property->setAccessible(true);

        return $property->getValue($inClass);
    }
}

?>