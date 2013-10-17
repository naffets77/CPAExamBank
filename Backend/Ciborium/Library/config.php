<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
/**
 * config.php for Ciborium library
 *
 */

class ciborium_configuration{

    public static $environment = "dev"; //dev, qa, stage, demo, prod
    public static $ciborium_librarypath = "/srv/lib/TPrepLib/dev";
    public static $environment_librarypath = "/srv/lib/_master/dev";
    public static $ciborium_servicepath = "/srv/lib/TPrepServices/dev";
    public static $ciborium_emailtemplatepath = "/srv/lib/TPrepLib/dev/EmailTemplates";
    public static $ciborium_testemailaddress = "marcusrico32@gmail.com";

    //MySQL parameters
    public static $dbhost = "198.211.105.160";
    public static $dbname = "ciborium_dev";
    public static $dblogin = "root";
    public static $dbpassword = "!Naffets77";

    public static $salt = "cibor14";
    public static $timeout = 43200; //43200 s = 12 hours; have to update in Master library config.php as well

}

?>