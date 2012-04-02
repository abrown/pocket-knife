<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class BasicClassTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(dirname(__FILE__)));
        require $path . '/start.php';
        // get code
        autoload('BasicClass');
        autoload('BasicDocumentation');
        autoload('Error');
    }

    /**
     * When __autoload is not working, for some reason or 
     * other, BasicClass::autoloadAll will do so manually
     * using @uses annotations to get dependent classes
     */
    public function testAutoloadAll(){
        // test not loaded
        $loaded_classes = get_declared_classes();
        $this->assertNotContains('WebHttp', $loaded_classes);
        $this->assertNotContains('Error', $loaded_classes);
        $this->assertNotContains('Service', $loaded_classes);
        // load
        BasicClass::autoloadAll('Service');
        // test loaded
        $loaded_classes = get_declared_classes();
        $this->assertContains('WebHttp', $loaded_classes);
        $this->assertContains('Error', $loaded_classes);
        $this->assertContains('Service', $loaded_classes);
    }
    
    /**
     * Finds dependencies notated by the @uses annotation
     */
    public function testFindDependencies(){
        $expected = 'BasicDocumentation';
        $actual = BasicClass::findDependencies('BasicClass');
        $this->assertContains($expected, $actual);
    }
    
    /**
     * Finds the absolute path in the filesystem to a class
     */
    public function testGetPathToClass(){
        $expected = dirname(dirname(dirname(__FILE__))).DS.'Basic'.DS.'Class.php';
        $actual = BasicClass::getPathToClass('BasicClass');
        $this->assertEquals($expected, $actual);
    }
}