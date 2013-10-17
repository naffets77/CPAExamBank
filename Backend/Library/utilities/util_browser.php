<?php
//require_once(realpath(__DIR__."/..")."/config.php");
//require_once(library_configuration::$environment_librarypath."/validate.php");

class util_browser{

    //get client's Browser information
    public static function getClientBrowserInfo(){
        $browser = get_browser(null, true);
        //must match db column names for keys
        $myArray = array(
            'BrowserName' => $browser['browser'],
            'BrowserVersion' => $browser['version'],
            'BrowserPlatform' => $browser['platform'],
            'BrowserCSSVersion' => $browser['cssversion'],
            'BrowserJSEnabled' => $browser['javascript']
        );

        return $myArray;
    }

    public static function getClientIPAddress($choice = null){
        switch($choice){
            case 1:
                return null; //another choice someday
            default:
                return $_SERVER['REMOTE_ADDR']; //TCP stack IP
        }
    }
}
?>