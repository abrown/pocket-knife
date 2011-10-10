<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class DocumentationTest extends PHPUnit_Extensions_OutputTestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        autoload('BasicDocumentation');
    }
    
    public function testAutoloadAll(){
        $classes = BasicDocumentation::autoloadAll('TestDataExample');
        $this->assertEquals(array('TestDataExample', 'TestDataExample2'), $classes);
        $this->assertTrue( class_exists('TestDataExample', false) );
        $this->assertTrue( class_exists('TestDataExample', false) );
    }
}