<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Base test case for framework tests; sets up file dependencies before running
 * tests.
 * @uses BasicClass
 */
class TestCase extends PHPUnit_Framework_TestCase {

    /**
     * Set up framework and autoload classes
     */
    public static function setUpBeforeClass() {
        // use test_autoload() from start.php to autoload all required classes for this test
        if (self::getCalledClass() != 'Case') {
            test_autoload(self::getCalledClass());
        }
    }

    /**
     * Return the class we are testing
     * @return string 
     */
    public static function getCalledClass() {
        $class = get_called_class();
        $class = str_replace('Test', '', $class);
        return $class;
    }
    
    public function testStub(){
        
    }

}