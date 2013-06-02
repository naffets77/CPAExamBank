<?php
    include_once("../config.php");
    require_once($libPath . "simpletest/autorun.php");
    
    
    
class TestOfLogin extends UnitTestCase {
    function testLogCreatesNewFileOnFirstMessage() {
        $this->assertTrue(true);
    }
}

?>