<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StorageCsvTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        $file = get_writable_dir() . DS . 'StorageCsv.csv';
        // delete file before we even begin, in case of prior errors
        if (file_exists($file)) {
            unlink($file);
        }
        // create settings
        $settings = new Settings(array(
            'location' => $file,
            'schema' => array('property', 'array', 'object') // this is to allow the generic tests in StorageGeneric::getObject()
        ));
        // create instance
        self::$instance = new StorageCsv($settings);
    }

    /**
     * Tear down after class
     */
    public static function tearDownAfterClass() {
        unlink(get_writable_dir() . DS . 'StorageCsv.csv');
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

    public function testSchemaFlatteningAndForcing() {
        $o = self::$instance->forceToSchema('...');
        $this->assertEquals('...', $o->property);
    }

    /**
     * Override the generic test; because StorageCsv flattens some values,
     * like arrays and objects, the assert would fail
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
        $expected_object->array = '1, 2, three';
        $expected_object->object = 'value2';
        $expected_object->id = 1; // StorageCsv will add an ID property if not present
        // update
        self::$instance->begin();
        $updated_object = self::$instance->update($object_changes, $id);
        self::$instance->commit();
        // test
        $this->assertEquals($expected_object, $updated_object);
    }

    /**
     * Override this method; was failing consistently because StorageCsv returns
     * objects based on its schema
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
        $this->assertEquals('first', $first->property);
        // get last
        $last = self::$instance->last();
        // test
        $this->assertEquals('last', $last->property);
    }

}
