<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
/**
 * config.php for Ciborium service
 *
 */

class service_configuration{

    public static $environment = "qa"; //dev, qa, stage, demo, prod
    public static $ciborium_librarypath = "/srv/lib/TPrepLib/qa";
    public static $environment_librarypath = "/srv/lib/_master/qa";
    public static $ciborium_servicepath = "/srv/lib/TPrepServices/qa";

}

?>