<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StorageFileTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        // create settings
        $settings = new Settings(array(
            'location' => get_writable_dir(),
            'format' => 'json'
        ));
        // create instance
        self::$instance = new StorageFile($settings);
    }

    /**
     * Tear down after class
     */
    public static function tearDownAfterClass() {
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

}
