<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * 
 * @requires extension mysqli
 */
class StorageCouchTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        // create settings
        $settings = new Settings(array(
            'location' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ));
        // create instance
        self::$instance = new StorageCouch($settings);
        // test only if couch database available
        try {
            WebHttp::request(self::$instance->url);
        } catch (Error $e) {
            self::markTestSkipped('A Couch database must be available using HTTP.');
        }
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

}
