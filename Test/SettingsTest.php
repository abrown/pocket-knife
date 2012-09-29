<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class SettingsTest extends PHPUnit_Framework_TestCase{
    
    public $array;
    public $config;
    
    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require_once $path . '/start.php';
        // get code
        autoload('BasicClass');
        BasicClass::autoloadAll('Settings');
    }

    public function setUp() {
        // setup array
        $this->array = array(
            'one' => 1,
            'two' => 2,
            'list1' => array('item1', 'item2', 'item3'),
            'list2' => array('four'=>4, 'five'=>5, 'six'=>6),
            'LIST3' => array('list4'=>array(7, 8, 9))
        );
        // setup config
        $this->config = new Settings($this->array);
    }
    
    /**
     * Demonstrates how to access configuration settings
     */
    public function testInstanceConstruction(){
        $array = $this->array;
        $config = $this->config;
        // test
        $this->assertTrue( is_object($config->getData()) );
        $this->assertEquals($array['one'], $config->one);
        $this->assertEquals($array['list1'][2], $config->list1->{2});
        $this->assertEquals((object) $array['list2'], $config->list2);
        $this->assertEquals($array['LIST3']['list4'][0], $config->list3->list4->{0});
        // this fails: $this->assertEquals((object) $array['list1'], $config->list1);
    }
    
    /**
     * Demonstrates how to set configuration settings with object notation
     */
    public function testOverloading(){
        $this->config->ten = 10;
        $this->assertEquals(10, $this->config->ten);    
    }
    
    /**
     * Demonstrates how to set configuration settings with dot notation
     */
    public function testDotNotation(){
        $this->config->set('x.y.z', 11);
        $this->assertEquals(11, $this->config->x->y->z); 
        $this->assertEquals(11, $this->config->get('x.y.z')); 
    }
    
    /**
     * 
     */
    public function testValidate(){
        $template = array(
            'one' => Settings::MANDATORY,
            'two' => Settings::MANDATORY | Settings::NUMERIC | Settings::SINGLE,
            'list1' => Settings::MULTIPLE,
            'list1.0' => Settings::STRING,
            'list2' => Settings::OPTIONAL | Settings::MULTIPLE,
            'list2.five' => Settings::OPTIONAL | Settings::NUMERIC,
            'list10' => Settings::OPTIONAL
        );
        $this->assertTrue( $this->config->validate($template) );
    }
    
    /**
     * Demonstrates what exception is thrown when validation fails
     * @expectedError Error
     */
    public function testValidateError(){
        $template = array(
            'list2' => Settings::OPTIONAL | Settings::SINGLE,
        );
        $this->assertTrue( $this->config->validate($template) );
    }
    
    /**
     * Demonstrates validation of paths
     */
    public function testValidatePath(){
        $template = array(
            'path' => Settings::MANDATORY | Settings::PATH,
            'dir' => Settings::PATH,
        );
        $this->config->path = __FILE__;
        $this->config->dir = dirname(__FILE__);
        // test
        $this->assertTrue( $this->config->validate($template) );
    }
    
    public function testFileWrite(){
        
    }
    
    public function testFileRead(){
        
    }
}
