<?php
require_once(realpath(__DIR__."/..")."/config.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_browser.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_datetime.php");
include_once(library_configuration::$environment_librarypath."/utilities/util_general.php");
include_once(library_configuration::$environment_librarypath."/validate.php");
include_once(library_configuration::$environment_librarypath."/database.php");

class util_errorlogging{

    /**
     * @param $inLogTypeId - severity of error
     * @param $inMessage - exception or other custom message
     * @param $inMethodName - __METHOD__
     * @param $inFileName - __FILE__
     * @return int|string - last insert id
     */
    public static function LogBrowserError($inLogTypeId, $inMessage, $inMethodName, $inFileName){
        $browser = util_browser::getClientBrowserInfo();
        $myErrorArray = array(
            'LogTypeId' => $inLogTypeId, //3 = normal
            'Message' => $inMessage,
            'FileSource' => $inFileName,
            'IPAddress' => util_browser::getClientIPAddress(),
            'LastModifiedBy' => $inMethodName,
            'CreatedBy' => $inMethodName,
            'DateCreated' => util_datetime::getDateTimeNow(),
            'BrowserName' => $browser['BrowserName'],
            'BrowserVersion' => $browser['BrowserVersion'],
            'BrowserPlatform' => $browser['BrowserPlatform'],
            'BrowserCSSVersion' => $browser['BrowserCSSVersion'],
            'BrowserJSEnabled' => $browser['BrowserJSEnabled']
        );
        $result = database::insert("ErrorLog", array_keys($myErrorArray), array_values($myErrorArray), null, __METHOD__);

        if($result != "0"){
            return $result;
        }
        else{
            try{
                self::LogDBError($myErrorArray, library_configuration::$error_toemail, library_configuration::$error_fromemail);
                return $result;
            }
            catch(Exception $ex){
                return $result;
            }

        }
    }

    /**
     * @param $inLogTypeId - severity of error
     * @param $inMessage - exception or other custom message
     * @param $inMethodName - __METHOD__
     * @param $inFileName - __FILE__
     * @return int|string - last insert id
     */
    public static function LogGeneralError($inLogTypeId, $inMessage, $inMethodName, $inFileName){
        $myErrorArray = array(
            'LogTypeId' => $inLogTypeId, //normal
            'Message' => $inMessage,
            'FileSource' => $inFileName,
            'IPAddress' => util_browser::getClientIPAddress(),
            'LastModifiedBy' => $inMethodName,
            'CreatedBy' => $inMethodName,
            'DateCreated' => util_datetime::getDateTimeNow(),
            'BrowserName' => null,
            'BrowserVersion' => null,
            'BrowserPlatform' => null,
            'BrowserCSSVersion' => null,
            'BrowserJSEnabled' => null
        );
        $result = database::insert("ErrorLog", array_keys($myErrorArray), array_values($myErrorArray), null, __METHOD__);

        if($result != "0"){
            return $result;
        }
        else{
            try{
                self::LogDBError($myErrorArray, library_configuration::$error_toemail, library_configuration::$error_fromemail);
                return $result;
            }
            catch(Exception $ex){
                return $result;
            }

        }
    }

    /**
     * @param $inErrorArray
     * @param $inToEmail
     * @param $inFromEmail
     * @return bool
     */
    public static function LogDBError($inErrorArray, $inToEmail, $inFromEmail){
        //TODO: will need to email an address like errors@corviden.com
        $myStringArray = util_general::stringValuesInAssociativeArray($inErrorArray);

        $to = $inToEmail;
        $from = $inFromEmail;
        $subject = "Unlogged Error - ".$myStringArray['CreatedBy'];
        $headers = "From: ".$from."";

        $message = "Could not log error to database due to an error; possibly with the connection. Here is what would have been logged:\n\n";
        $message .= "<table class='ErrorArray'>";
        $message .= "<td class='field'>LogTypeId</td><td class='value'>".$myStringArray['LogTypeId']."</td></tr>";
        $message .= "<td class='field'>Message</td><td class='value'>".$myStringArray['Message']."</td></tr>";
        $message .= "<td class='field'>FileSource</td><td class='value'>".$myStringArray['FileSource']."</td></tr>";
        $message .= "<td class='field'>IPAddress</td><td class='value'>".$myStringArray['IPAddress']."</td></tr>";
        $message .= "<td class='field'>LastModifiedBy</td><td class='value'>".$myStringArray['LastModifiedBy']."</td></tr>";
        $message .= "<td class='field'>CreatedBy</td><td class='value'>".$myStringArray['CreatedBy']."</td></tr>";
        $message .= "<td class='field'>DateCreated</td><td class='value'>".$myStringArray['DateCreated']."</td></tr>";
        $message .= "<td class='field'>BrowserName</td><td class='value'>".$myStringArray['BrowserName']."</td></tr>";
        $message .= "<td class='field'>BrowserVersion</td><td class='value'>".$myStringArray['BrowserVersion']."</td></tr>";
        $message .= "<td class='field'>BrowserPlatform</td><td class='value'>".$myStringArray['BrowserPlatform']."</td></tr>";
        $message .= "<td class='field'>BrowserCSSVersion</td><td class='value'>".$myStringArray['BrowserCSSVersion']."</td></tr>";
        $message .= "<td class='field'>BrowserJSEnabled</td><td class='value'>".$myStringArray['BrowserJSEnabled']."</td></tr>";
        $message .= "</table>";

        $message .= "\n\nPlease log this error as soon as possible.";

        return $result = mail($to, $subject, $message, $headers);
    }

}
?>