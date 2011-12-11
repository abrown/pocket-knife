<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class SettingsTest extends PHPUnit_Framework_TestCase{
    
    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        autoload('Settings');
        autoload('ExceptionSettings');
    }

    public function setUp() {
        $this->a = array(
            'one' => 1,
            'two' => 2,
            'list1' => array('item1', 'item2', 'item3'),
            'list2' => array('four'=>4, 'five'=>5, 'six'=>6),
            'LIST3' => array('list4'=>array(7, 8, 9))
        );
        $this->c = new Settings($this->a);
    }
    
    public function testInstanceConstruction(){
        $array = $this->a;
        $config = $this->c;
        // test
        $this->assertTrue( is_object($config->getInstance()) );
        $this->assertEquals($array['one'], $config->one);
        $this->assertEquals($array['list1'][2], $config->list1->{2});
        $this->assertEquals((object) $array['list2'], $config->list2);
        $this->assertEquals($array['LIST3']['list4'][0], $config->list3->list4->{0});
        // this fails: $this->assertEquals((object) $array['list1'], $config->list1);
    }
    
    public function testOverloading(){
        $this->c->ten = 10;
        $this->assertEquals(10, $this->c->ten);    
    }
    
    public function testDotNotation(){
        $this->c->set('x.y.z', 11);
        $this->assertEquals(11, $this->c->x->y->z); 
        $this->assertEquals(11, $this->c->get('x.y.z')); 
    }
    
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
        $this->assertTrue( $this->c->validate($template) );
    }
    
    /**
     * @expectedException ExceptionSettings
     */
    public function testValidateException(){
        $template = array(
            'list2' => Settings::OPTIONAL | Settings::SINGLE,
        );
        $this->assertTrue( $this->c->validate($template) );
    }
    
    public function testValidatePath(){
        $template = array(
            'path' => Settings::MANDATORY | Settings::PATH,
            'dir' => Settings::PATH,
        );
        $this->c->path = __FILE__;
        $this->c->dir = dirname(__FILE__);
        // test
        $this->assertTrue( $this->c->validate($template) );
    }
    
    public function testFileWrite(){
        
    }
    
    public function testFileRead(){
        
    }
}
