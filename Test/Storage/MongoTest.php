<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
require_once '../start.php';

class StorageMongoTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        test_autoload('StorageMongo');
        // create settings
        $settings = new Settings(array(
                    'location' => 'localhost',
                    'database' => 'test',
                    'collection' => 'test'
                ));
        // create instance
        self::$instance = new StorageCouch($settings);
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

}
