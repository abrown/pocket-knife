<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class StorageMongoTest extends PHPUnit_Framework_TestCase{
    
    public static $id;
    
    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        autoload('StorageInterface');
        autoload('StorageMongo');
        autoload('WebHttp');
        autoload('Configuration');
        autoload('ExceptionStorage');
    }
    
    public function setUp(){
        $configuration = new Configuration(array(
            'location' => 'localhost',
            'database' => 'test',
            'collection' => 'test'
        ));
        $this->db = new StorageMongo($configuration);
    }
    
    private function getObject(){
        $object = new stdClass();
        $object->property = 'value';
        $object->{'array'} = array('1', 2, 'three');
        $object->object = new stdClass();
        $object->object->property2 = 'value2';
        return $object;
    }
    
    public function testConstructor(){
        
    }
    
    public function testBegin(){

    }
    
    public function testCommit(){
        
    }
    
    public function testRollback(){
        
    }
    
    public function testCreate(){
        $id = $this->db->create($this->getObject());
        self::$id = $id;
        $this->assertNotNull($id);
    }
    
    public function testRead(){
        $object = $this->db->read(self::$id);
        $this->assertEquals($this->getObject(), $object);
    }
    
    public function testUpdate(){
        // create changes
        $object_changes = new stdClass();
        $object_changes->property = 'new_value';
        // modify expected
        $expected_object = $this->getObject();
        $expected_object->property = 'new_value';
        // update
        $updated_object = $this->db->update($object_changes, self::$id);
        // test
        $this->assertEquals($expected_object, $updated_object);
    }
    
    /**
     * @expectedException ExceptionStorage
     */
    public function testDelete(){
        $updated_object = $this->db->delete(self::$id);
        $this->assertEquals('new_value', $updated_object->property);
        $this->db->read(self::$id);
    }
    
    public function testAll(){
        $records = $this->db->all();
        $this->assertEquals(array(), $records);
    }
    
    public function testSearch(){
        
    }
    
    public function testLast(){
        
    }
}
