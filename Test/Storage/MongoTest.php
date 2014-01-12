<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StorageMongoTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        // create settings
        $settings = new Settings(array(
            'location' => 'localhost',
            'database' => 'test',
            'collection' => 'test',
            'username' => 'test',
            'property' => 'test'
        ));
        // create instance
        try {
            self::$instance = new StorageMongo($settings);
        } catch (Error $e) {
            self::markTestSkipped('The Mongo extension must be turned on to test StorageMongo.');
        }
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

}
