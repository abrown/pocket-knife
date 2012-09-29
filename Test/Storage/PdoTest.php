<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
require_once '../start.php';

class StoragePdoTest extends StorageGeneric {

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        test_autoload('StoragePdo');
        // create settings
        $settings = new Settings(array(
                    'location' => 'localhost',
                    'username' => 'root',
                    'password' => '',
                    'database' => 'pocket_knife_test',
                    'table' => 'test',
                    'primary' => 'id'
                ));
        // create instance
        self::$instance = new StoragePdo($settings);
        // create testing artifacts
        try {
            $dsn = "mysql:host={$settings->location}";
            self::$instance = new PDO($dsn, $settings->username, $settings->password);
            self::$instance->query("CREATE DATABASE {$settings->database}");
            self::$instance->query("USE {$settings->database}");
            self::$instance->query("CREATE TABLE `{$settings->table}` ( `{$settings->primary}` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`{$settings->primary}`) ) ENGINE=InnoDB");
            self::$instance->query("ALTER TABLE {$settings->table} ADD COLUMN a DATETIME DEFAULT NULL");
            self::$instance->query("ALTER TABLE {$settings->table} ADD COLUMN b TEXT");
        } catch (PDOError $e) {
            throw new Error($e->getMessage(), 500);
        }
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

}
