<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
/**
 * config.php for Ciborium service
 *
 */

class service_configuration{

    public static $environment = "prod"; //dev, qa, stage, demo, prod
    public static $ciborium_librarypath = "/srv/lib/TPrepLib/prod";
    public static $environment_librarypath = "/srv/lib/_master/prod";
    public static $ciborium_servicepath = "/srv/lib/TPrepServices/prod";

}

?>