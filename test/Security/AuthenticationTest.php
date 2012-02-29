<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AuthenticationTest extends PHPUnit_Framework_TestCase {

    /**
     * Authentication Instance
     * @var SecurityAuthentication
     */
    public $auth;

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
        BasicClass::autoloadAll('SecurityAuthentication');
    }

    /**
     * Setup
     */
    public function setUp() {
        // create ACL
        $this->auth = new SecurityAuthentication();
        $db = new StorageMemory();
        $db->create(new SecurityUser('alice', '123'));
        $db->create(new SecurityUser('bob', '456'));
        $db->create(new SecurityUser('charles', '789'));
        $this->auth->setStorage($db);
        $this->auth->password_security = SecurityAuthentication::PLAINTEXT;
    }
    
    /**
     * Demonstrates password security
     */
    public function testPasswordSecurity(){
        $expected = 'password';
        // encrypted
        $user = new SecurityUser('alice', null);
        $user->setPassword($expected, SecurityAuthentication::ENCRYPTED, 'secret_key');
        pr($user->password);
        $actual = $user->getPassword(SecurityAuthentication::ENCRYPTED, 'secret_key');
        $this->assertEquals($expected, $actual);
        // plaintext
        $user->setPassword($expected, SecurityAuthentication::PLAINTEXT);
        pr($user->password);
        $actual = $user->getPassword(SecurityAuthentication::PLAINTEXT);
        $this->assertEquals($expected, $actual);
        // hashed
        $user->setPassword($expected, SecurityAuthentication::HASHED, 'secret_key');
        pr($user->password);
        $expected = md5('secret_key'.'password');
        $actual = $user->password;
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Demonstrates getUser()
     */
    public function testGetUser(){
        $expected = new SecurityUser('bob', '456');
        $actual = $this->auth->getUser('bob');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Demonstrates use of login()
     */
    public function testLogin() {
        // test user
        $expected = true;
        $actual = $this->auth->login('alice', '123');
        $this->assertEquals($expected, $actual);
        // test user
        $expected = false;
        $actual = $this->auth->login('alice', '234');
        $this->assertEquals($expected, $actual);
    }

}