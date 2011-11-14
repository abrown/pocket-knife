<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class BasicFileTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        autoload('BasicFile');
    }
    
    public function testAutoloadAll(){
        $classes = BasicFile::autoloadAll('TestDataExample');
        $this->assertEquals(array('TestDataExample', 'TestDataExample2'), $classes);
        $this->assertTrue( class_exists('TestDataExample', false) );
        $this->assertTrue( class_exists('TestDataExample', false) );
    }
    
    public function testAutoloadAll2(){
        $classes_loaded = BasicFile::autoloadAll('Service');
        $service_uses = array(
            'Service',
            'Configuration', 
            'WebRouting', 
            'WebHttp', 
            'WebTemplate', 
            'ExceptionFile',
            'ExceptionConfiguration'
        );
        sort($service_uses);
        sort($classes_loaded);
        $this->assertEquals($service_uses, $classes_loaded);
    }
}