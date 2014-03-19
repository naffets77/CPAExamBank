<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * config.php for library
 *
 */

class library_configuration{

    public static $environment = "stage"; //dev, qa, stage, demo, prod
    public static $main_librarypath = "/srv/lib/_master";
    public static $environment_librarypath = "/srv/lib/_master/stage";

    //MySQL parameters
    public static $dbhost = "198.211.105.160";
    public static $dbname = "ciborium_stage";
    public static $dblogin = "root";
    public static $dbpassword = "!Naffets77";

    public static $error_toemail = "contact@cpaexambank.com";
    public static $error_fromemail = "errors@cpaexambank.com";

    public static $salt = "cibor14";
    public static $timeout = 43200; //43200 s = 12 hours; have to update in Library config.php as well
    public static $hashexpiration = 60;
}
?>