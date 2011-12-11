<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class StoragePdoTest extends PHPUnit_Framework_TestCase{
    
    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        BasicFile::autoloadAll('StoragePdo');
        // create connection
        $instance = new StoragePdo( $this->getConfig() );
        // create testing artifacts
        try {
            $dsn = "mysql:host={$config->location}";
            $instance = new PDO($dsn, $config->username, $config->password);
            $instance->query("CREATE DATABASE {$config->database}");
            $instance->query("USE {$config->database}");
            $instance->query("CREATE TABLE `{$config->table}` ( `{$config->primary}` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`{$config->primary}`) ) ENGINE=InnoDB");
            $instance->query("ALTER TABLE {$config->table} ADD COLUMN a DATETIME DEFAULT NULL");
            $instance->query("ALTER TABLE {$config->table} ADD COLUMN b TEXT");
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }
    
    public function setUp(){
        // create connection
        $this->db = new StoragePdo( $this->getConfig() );
        // create testing artifacts
        try {
            $dsn = "mysql:host={$config->location}";
            $instance = new PDO($dsn, $config->username, $config->password);
            $instance->query("CREATE DATABASE {$config->database}");
            $instance->query("USE {$config->database}");
            $instance->query("CREATE TABLE `{$config->table}` ( `{$config->primary}` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`{$config->primary}`) ) ENGINE=InnoDB");
            $instance->query("ALTER TABLE {$config->table} ADD COLUMN a DATETIME DEFAULT NULL");
            $instance->query("ALTER TABLE {$config->table} ADD COLUMN b TEXT");
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }    
    
    private function getConfig(){
        $config = new Configuration(array(
            'location' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'pocket_knife_test',
            'table'    => 'test',
            'primary'  => 'id'
        ));
        return $config;
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
        $id = $this->db->create($this->getObject(), 1);
        $this->assertEquals(1, $id);
    }
    
    public function testRead(){
        $object = $this->db->read(1);
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
        $updated_object = $this->db->update($object_changes, 1);
        // test
        $this->assertEquals($expected_object, $updated_object);
    }
    
    /**
     * @expectedException ExceptionStorage
     */
    public function testDelete(){
        $updated_object = $this->db->delete(1);
        $this->assertEquals('new_value', $updated_object->property);
        $this->db->read(1);
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
