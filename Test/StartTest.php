<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StartTest extends PHPUnit_Extensions_OutputTestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require_once $path . '/start.php';
        // load classes
        autoload('Error');
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
        autoload('Service');
        $this->assertContains('Service', get_declared_classes());
    }
    
    public function testAddIncludePath(){
        add_include_path('/etc');
        $this->assertRegExp('#/etc#i', get_include_path());
    }

    public function testPr() {
        $this->expectOutputString("<pre>test</pre>\n");
        pr('test');
    }

    public function testGetPublicVars() {
        $class = new Example();
        $reflect = new ReflectionClass($class);
        $expected = array();
        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $var) {
            $expected[$var->name] = null;
        }
        $actual = get_public_vars($class);
        $this->assertEquals($expected, $actual);
    }

    public function testToObject() {
        
    }

}

/**
 * Simple test class
 */
class Example {

    public $a;
    protected $b;
    private $c;

}