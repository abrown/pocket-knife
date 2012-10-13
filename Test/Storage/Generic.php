<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
require_once dirname(__DIR__).'/start.php';

class StorageGeneric extends PHPUnit_Framework_TestCase {

    public static $instance;

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        throw new Error('Implement in child classes.', 500);
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        throw new Error('Implement in child classes.', 500);
    }

    /**
     * Tear down after each test
     */
    public function tearDown() {
        self::$instance->begin();
        self::$instance->deleteAll();
        self::$instance->commit();
    }

    /**
     * Tear down after class
     */
    public static function tearDownAfterClass() {
        // remove all records
        //self::$instance->deleteAll();
    }

    /**
     * Return the test object
     * @return stdClass
     */
    private function getObject() {
        $object = new stdClass();
        $object->property = 'value';
        $object->{'array'} = array('1', 2, 'three');
        $object->object = new stdClass();
        $object->object->property2 = 'value2';
        return $object;
    }

    /**
     * Test rollback
     */
    public function testRollback() {
        self::$instance->begin();
        self::$instance->create('...', 'ROLLBACK');
        self::$instance->rollback();
        // test
        $this->assertEquals(false, self::$instance->exists('ROLLBACK'));
    }

    /**
     * Test create method
     */
    public function testCreate() {
        // create
        self::$instance->begin();
        $id1 = self::$instance->create($this->getObject());
        $id2 = self::$instance->create('...');
        self::$instance->commit();
        // test
        $this->assertEquals(1, $id1);
        $this->assertEquals(2, $id2);
        $this->assertEquals(2, self::$instance->count());
    }

    /**
     * Test read
     */
    public function testRead() {
        // create
        self::$instance->begin();
        self::$instance->create($this->getObject(), 'ID');
        self::$instance->commit();
        // read one
        $object = self::$instance->read('ID');
        // test
        $this->assertEquals($this->getObject(), $object);
    }

    /**
     * Test update method
     */
    public function testUpdate() {
        // create
        self::$instance->begin();
        $id = self::$instance->create($this->getObject());
        self::$instance->commit();
        // create changes
        $object_changes = new stdClass();
        $object_changes->property = 'new_value';
        // modify expected
        $expected_object = $this->getObject();
        $expected_object->property = 'new_value';
        // update
        self::$instance->begin();
        $updated_object = self::$instance->update($object_changes, $id);
        self::$instance->commit();
        // test
        $this->assertEquals($expected_object, $updated_object);
    }

    /**
     * Test search
     */
    public function testSearch() {
        // create
        self::$instance->begin();
        $id = self::$instance->create($this->getObject());
        self::$instance->commit();
        // find 
        $found = self::$instance->search('property', 'value');
        // test
        $this->assertEquals(1, count($found));
        $this->assertNotEmpty($found[$id]);
    }

    /**
     * Test first and last methods
     */
    public function testSorting() {
        // create
        self::$instance->begin();
        $id1 = self::$instance->create('first');
        $id2 = self::$instance->create('middle');
        $id3 = self::$instance->create('last');
        self::$instance->commit();
        // get first
        $first = self::$instance->first();
        // test
        $this->assertEquals('first', $first);
        // get last
        $last = self::$instance->last();
        // test
        $this->assertEquals('last', $last);
    }

    /**
     * Test delete
     */
    public function testDelete() {
        self::$instance->begin();
        $id = self::$instance->create($this->getObject());
        self::$instance->commit();
        // delete
        $deleted_object = self::$instance->delete($id);
        // test
        $this->assertEquals('value2', $deleted_object->object->property2);
        // throw Error
        $this->setExpectedException('Error');
        self::$instance->read($id);
    }

}