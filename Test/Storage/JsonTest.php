<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StorageJsonTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        // create settingsF
        $settings = new Settings(array(
            'location' => get_writeable_dir() . DS . 'StorageJson.json'
        ));
        // create instance
        self::$instance = new StorageJson($settings);
    }

    /**
     * Tear down after class
     */
    public static function tearDownAfterClass() {
        unlink(get_writeable_dir() . DS . 'StorageJson.json');
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

    /**
     * Verify that the JSON file has been created
     */
    public function testLocationExists() {
        $this->assertFileExists(get_writeable_dir() . DS . 'StorageJson.json');
    }

}
