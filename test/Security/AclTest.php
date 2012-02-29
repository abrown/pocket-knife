<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AclTest extends PHPUnit_Framework_TestCase {

    /**
     * ACL Instance
     * @var SecurityAcl
     */
    public $acl;

    /**
     * Setup
     */
    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(dirname(__FILE__)));
        require $path . '/start.php';
        // get code
        autoload('BasicClass');
        BasicClass::autoloadAll('Resource');
        BasicClass::autoloadAll('StorageMemory');
        BasicClass::autoloadAll('SecurityAcl');
    }
    
    /**
     * Setup
     */
    public function setUp(){
        // create ACL
        $this->acl = new SecurityAcl();
        $this->acl->default_access = 'deny';
        $db = new StorageMemory();
        $db->create(new SecurityRule('administrator', '*', '*', '*', true));
        $db->create(new SecurityRule('user', 'read', '*', '*', true));
        $db->create(new SecurityRule('user', 'read', 'secret_record', '*', false));
        $db->create(new SecurityRule('alice', 'delete', '*', 23, true));
        $this->acl->setStorage($db);        
    }

    /**
     * Demonstrates use of isAllowed()
     */
    public function testIsAllowed() {
        // test user
        $expected = true;
        $actual = $this->acl->isAllowed('alice', null, 'delete', 'some_record', 23);
        $this->assertEquals($expected, $actual);
        // test user
        $expected = false;
        $actual = $this->acl->isAllowed('bob', null, 'delete', 'some_record', 23);
        $this->assertEquals($expected, $actual);
        // test roles
        $expected = true;
        $actual = $this->acl->isAllowed('bob', array('user', 'manager'), 'read', 'some_record', 23);
        $this->assertEquals($expected, $actual);
        // test roles
        $expected = false;
        $actual = $this->acl->isAllowed('bob', 'user', 'read', 'secret_record', 23);
        $this->assertEquals($expected, $actual);
        // test action
        $expected = true;
        $actual = $this->acl->isAllowed(null, 'administrator', 'some_action', 'secret_record', 23);
        $this->assertEquals($expected, $actual);
        // test ID
        $expected = false;
        $actual = $this->acl->isAllowed('alice', null, 'delete', 'secret_record', 1323);
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetRulesFor(){
        $rules = $this->acl->getRulesFor('user');
        $expected = new SecurityRule('user', 'read', 'secret_record', '*', false);
        $actual = $rules[0];
        $this->assertEquals($expected, $actual);
    }

}