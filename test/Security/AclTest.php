<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
if (!class_exists('TestCase'))
    require '../Case.php';

class SecurityAclTest extends TestCase {

    /**
     * ACL Instance
     * @var SecurityAcl
     */
    public $acl;

    /**
     * Setup
     */
    public function setUp() {
        // load
        BasicClass::autoloadAll('StorageMemory');
        BasicClass::autoloadAll('Settings');
        // create ACL
        $settings = new Settings(array('storage' => array('type' => 'memory', 'data' => array())));
        $this->acl = new SecurityAcl($settings);
        $this->acl->default_access = 'deny';
        $this->acl->getStorage()->create(new SecurityRule('administrator', '*', '*', '*', true));
        $this->acl->getStorage()->create(new SecurityRule('user', 'read', '*', '*', true));
        $this->acl->getStorage()->create(new SecurityRule('user', 'read', 'secret_record', '*', false));
        $this->acl->getStorage()->create(new SecurityRule('alice', 'delete', '*', 23, true));
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

    public function testGetRulesFor() {
        $rules = $this->acl->getRulesFor('user');
        $expected = new SecurityRule('user', 'read', 'secret_record', '*', false);
        $actual = $rules[0];
        $this->assertEquals($expected, $actual);
    }

}