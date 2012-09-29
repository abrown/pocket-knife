<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
require_once '../start.php';

class StorageMemoryTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        test_autoload('StorageMemory');
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
