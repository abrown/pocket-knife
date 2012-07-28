<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
if (!class_exists('TestCase', false)) {
    require '../Case.php';
}

// pre-load ResourceList
if (!class_exists('ResourceList', false)) {
    TestCase::setUpBeforeClass();
    BasicClass::autoloadAll('ResourceList');
    BasicClass::autoloadAll('StorageMemory');
}

class ResourceListTest extends TestCase {

    /**
     * Setup
     */
    public static function setUpBeforeClass() {
        // setup URL
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/directory/index.php/lists?filter_on=name&filter_with=abc';
    }
    
    // Test GET method, including paging and filtering
    public function testGET(){
        $list = new lists();
        $list->GET();
        $this->assertEquals('y', $list->items[1]->getID());
        // filter
        $_GET['filter_on'] = 'name';
        $_GET['filter_with'] = 'abc';
        $list->GET();
        $this->assertEquals(1, count($list->items));
        // page
        $_GET = array();
        $_GET['page'] = 2;
        $_GET['page_size'] = 1;
        $list->GET();
        $this->assertEquals(1, count($list->items));
        $this->assertEquals('def', $list->items[1]->name);
    }
    
    public function testPUT(){
        
    }
    
    public function testPOST(){
        
    }
    
    public function testDELETE(){
        
    }
    
    public function testHEAD(){
        
    }
    
    public function testOPTIONS(){
        $list = new lists();
        $o = $list->OPTIONS();
        $this->assertEquals('items', $o->properties[0]);
    }

}

class lists extends ResourceList{
    protected $item_type = 'item';
    protected $storage = array('type' => 'memory', 'data' => array(
        'x' => array('name' => 'abc'),
        'y' => array('name' => 'def'),
        'z' => array('name' => 'abc')
    ));
}
class item extends ResourceItem{
    public $id;
    public $name;
}