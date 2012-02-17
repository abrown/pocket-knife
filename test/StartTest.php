<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class StartTest extends PHPUnit_Extensions_OutputTestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
    }

    public function testGetBaseDir() {
        $path1 = get_base_dir();
        // change directory to .. from here
        chdir(dirname(dirname(__FILE__)));
        $path2 = getcwd();
        // test
        $this->assertEquals($path1, $path2);
    }

    public function testAutoload() {
        autoload('TestDataExample');
        $this->assertContains('TestDataExample', get_declared_classes());
    }

    public function testPr() {
        $this->expectOutputString("<pre>test</pre>\n");
        pr('test');
    }

    public function testGetPublicVars() {
        $class = new TestDataExample();
        $reflect = new ReflectionClass($class);
        $vars = array();
        foreach( $reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $var ){
            $vars[$var->name] = null;
        }
        $this->assertEquals(get_public_vars($class), $vars);
    }
    
    public function testToObject(){
        
    }
}