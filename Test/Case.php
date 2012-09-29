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
        // start pocket-knife
        $path = dirname(__DIR__);
        if (!function_exists('pr'))
            require $path . '/start.php';
        // autoload
        if( !class_exists('BasicClass') ){
            autoload('BasicClass');
        }
        if (self::getCalledClass() != 'Case') {
            BasicClass::autoloadAll(self::getCalledClass());
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

//    /**
//     * Checks that all necessary files have been autoloaded
//     */
//    public function testAllFilesAutoloaded() {
//        try {
//            $dependencies = BasicClass::findDependencies($this->getCalledClass());
//            foreach($dependencies as $dependency){
//                if( in_array($dependency, get_declared_classes())){
//                    $this->fail("Could not find: ".$dependency);
//                }
//            }
//        } catch (Error $e) {
//            $this->fail($e->message);
//        }
//    }

}