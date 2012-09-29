<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
require_once '../start.php';

class StorageCsvTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        test_autoload('StorageCsv');
        // create settings
        $settings = new Settings(array(
                    'location' => get_test_dir() . '/sandbox/StoraceCsv.csv'
                ));
        // create instance
        self::$instance = new StorageCsv($settings);
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

}
