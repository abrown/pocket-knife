<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
/**
 * Get autoload ready for Library example class
 */
$path = dirname(dirname(__FILE__));
require_once $path . '/start.php';
autoload('BasicClass');
BasicClass::autoloadAll('Cache');
BasicClass::autoloadAll('ResourceGeneric');
BasicClass::autoloadAll('StorageMemory');

class CacheTest extends PHPUnit_Framework_TestCase {

    public $instance;

    public static function setUpBeforeClass() {
        
    }

    public function setUp() {
        $this->instance = new Cache();
    }

    public function testExistence() {
        $this->assertNotNull($this->instance);
    }

    /**
     * Create a cache entry
     */
    public function testCreateEntry() {
        // create resource; default URI will be 'dog'
        $dog = new Dog();
        $dog->name = 'Spike';
        $dog->color = 'Black';
        $dog->age = 7;
        // cache resource
        $this->instance->POST($dog);
        // test
        $this->assertEquals($dog, $this->instance->GET('dog'));
    }

    /**
     * Modify a cache entry
     */
    public function testModifyEntry() {
        // retrieve resource
        $dog = $this->instance->GET('dog');
        $prior_modified = $this->instance->modified;
        $prior_version = $this->instance->version;
        // edit resource, cache should update from within Resource
        $dog->PUT(array('name' => 'Fido'), 'dog');
        // retrieve resource again
        $dog = $this->instance->GET('dog');
        $after_modified = $this->instance->modified;
        $after_version = $this->instance->version;
        // test
        $this->assertGreaterThan($prior_modified, $after_modified);
        $this->assertEquals($prior_version, $after_version - 1);
    }
    
    /**
     * Delete a cache entry
     */
    public function testDeleteEntry(){
        $dog = $this->instance->DELETE('dog');
        // test
        $this->assertEquals('Fido', $dog->name);
        $this->assertEquals(false, $this->instance->HEAD());
        $this->setExpectedException('Error');
        $this->instance->GET('dog');
    }

}

/**
 * An example resource
 */
class Dog extends ResourceGeneric {

    public $name;
    public $color;
    public $age;
    protected $storage = array('type' => 'memory');

    public function PUT($entity, $id) {
        foreach ($entity as $key => $value) {
            $this->$key = $value;
        }
        $this->changed();
    }

}