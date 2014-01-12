<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StoragePdoTest extends StorageGeneric {

    static $settings;

    /**
     * Set up before class
     */
    public static function setUpBeforeClass() {
        if (!extension_loaded('pdo') && !extension_loaded('sqlite')) {
            self::markTestSkipped('The PHP and SQLite extensions must be enable to test StoragePdo');
        }
        // create settings
        $settings = new Settings(array(
            'driver' => 'sqlite',
            'location' => get_writeable_dir() . DS . 'pdo-sqlite.db',
            'username' => 'root',
            'password' => '',
            'database' => 'pocket_knife_test',
            'table' => 'test',
            'primary' => 'id'
        ));
        // create instance
        self::$instance = new StoragePdo($settings);
        // create testing artifacts in SQLite
        self::db($settings);
        self::db()->query("CREATE TABLE `{$settings->table}` ( `{$settings->primary}` INTEGER PRIMARY KEY AUTOINCREMENT )");
        self::db()->query("ALTER TABLE {$settings->table} ADD COLUMN property TEXT");
        self::db()->query("ALTER TABLE {$settings->table} ADD COLUMN array TEXT");
        self::db()->query("ALTER TABLE {$settings->table} ADD COLUMN object TEXT");
        // save settings for later
        self::$settings = $settings;
    }

    /**
     * Set up before each test
     */
    public function setUp() {
        
    }

    public function tearDown() {
        self::$instance->begin();
        self::$instance->deleteAll();
        //self::db()->query('DELETE FROM sqlite_sequence'); // reset the AUTOINCREMENT counter
        self::$instance->commit();
    }

    public static function tearDownAfterClass() {
        // remove tables
        $settings = self::$settings;
        self::db()->query("DROP TABLE `{$settings->test}`");
        // delete SQLite file?
        //unlink(get_writeable_dir().DS.'pdo-sqlite.db');
    }

    /**
     * Test describe
     */
    public function testDescribe() {
        $fields = self::$instance->describe();
        $this->assertEquals(array('id', 'property', 'array', 'object'), $fields);
    }

    /**
     * Test forcing to schema
     */
    public function testForceSchema() {
        $expected = new stdClass();
        $expected->id = null;
        $expected->property = '...';
        // these won't be set: $expected->array = null; $expected->object = null;
        $this->assertEquals($expected, self::$instance->forceToSchema('...'));
    }

    /**
     * Override create test so we can reset AUTOINCREMENT
     */
    public function testCreate() {
        self::db()->query('DELETE FROM sqlite_sequence');
        parent::testCreate();
    }

    /**
     * Override read test because StoragePDO adds an ID property to all
     * results
     */
    public function testRead() {
        // create
        self::$instance->begin();
        self::$instance->create($this->getObject(), 999);
        self::$instance->commit();
        // read one
        $object = self::$instance->read(999);
        // test
        $expected = $this->getObject();
        $expected->id = 999;
        $this->assertEquals($expected, $object);
    }

    /**
     * Override update test because StoragePDO adds an ID property to all
     * results
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
        $expected_object->id = $id;
        // update
        self::$instance->begin();
        $updated_object = self::$instance->update($object_changes, $id);
        self::$instance->commit();
        // test
        $this->assertEquals($expected_object, $updated_object);
    }

    /**
     * Override update test because StoragePDO adds an ID property to all
     * results
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

    /**
     * Straight PDO object
     * @param Settings $settings
     * @throws Error
     */
    protected static function db(Settings $settings = null) {
        static $db = null;
        if ($db === null) {
            try {
                $dsn = "sqlite:{$settings->location}";
                $db = new PDO($dsn, $settings->username, $settings->password);
            } catch (PDOError $e) {
                throw new Error($e->getMessage(), 500);
            }
        }
        return $db;
    }

}
