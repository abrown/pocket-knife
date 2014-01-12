<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StorageMemoryTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        // create settings
        $settings = new Settings();
        // create instance
        self::$instance = new StorageMemory($settings);
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

}
