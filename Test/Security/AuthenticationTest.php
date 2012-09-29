<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
if (!class_exists('TestCase'))
    require '../Case.php';

class SecurityAuthenticationTest extends TestCase {

    /**
     * Authentication Instance
     * @var SecurityAuthentication
     */
    public $auth;

    /**
     * Setup
     */
    public function setUp() {
        // load
        BasicClass::autoloadAll('StorageMemory');
        BasicClass::autoloadAll('Settings'); 
        BasicClass::autoloadAll('SecurityAuthenticationBasic'); 
        // create ACL
        $settings = new Settings(array('enforce_https'=>'false', 'password_secret_key' => '12345...', 'password_security' => 'plaintext', 'storage'=> array('type'=>'memory')));
        $this->auth = new SecurityAuthenticationBasic($settings);
        $this->auth->getStorage()->create(new SecurityUser('alice', '123'));
        $this->auth->getStorage()->create(new SecurityUser('bob', '456'));
        $this->auth->getStorage()->create(new SecurityUser('charles', '789'));
    }

    /**
     * Demonstrates password security
     */
    public function testPasswordSecurity() {
        $expected = 'password';
        // encrypted
        $user = new SecurityUser('alice', null);
        $user->setPassword($expected, 'encrypted', 'secret_key');
        $actual = $user->getPassword('encrypted', 'secret_key');
        $this->assertEquals($expected, $actual);
        // plaintext
        $user->setPassword($expected, 'plaintext');
        $actual = $user->getPassword('plaintext');
        $this->assertEquals($expected, $actual);
        // hashed
        $user->setPassword($expected, 'hashed', 'secret_key');
        $expected = md5('secret_key' . 'password');
        $actual = $user->password;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Demonstrates getUser()
     */
    public function testGetUser() {
        $expected = new SecurityUser('bob', '456');
        $actual = $this->auth->getUser('bob');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Demonstrates use of login()
     */
    public function testLogin() {
        // create credentials
        $credentials = new stdClass();
        $credentials->username = 'alice';
        $credentials->password = '123';
        // test user
        $expected = true;
        $actual = $this->auth->isValidCredential($credentials);
        $this->assertEquals($expected, $actual);
        // test user
        $expected = false;
        $credentials->password = '234';
        $actual = $this->auth->isValidCredential($credentials);
        $this->assertEquals($expected, $actual);
    }

}