<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for authenticating users
 * @uses ResourceList, SecurityUser
 */
abstract class SecurityAuthentication extends ResourceList {

    /**
     * Forces the session to move to HTTPS
     * @var boolean
     */
    public $enforce_https = true;

    /**
     * Type of authentication to start
     * @var string one of [Basic, Digest, Header, Session]
     */
    public $authentication_type = 'Digest';

    /**
     * Secret key for hashing and encrypting passwords
     * @var string
     */
    public $password_secret_key = '###';

    /**
     * Describes how to store passwords; one of [plaintext, hashed, encrypted].
     * See User.php for more details.
     * @var int
     */
    public $password_security = 'encrypted';

    /**
     * Constructor
     * @param Settings
     */
    public function __construct($settings) {
        parent::__construct();
        // validate
        BasicValidation::with($settings)
                // https
                ->withOptionalProperty('enforce_https')
                ->isBoolean()
                // type
                ->upOne()->withOptionalProperty('authentication_type')
                ->isString()
                ->oneOf('basic', 'digest', 'header', 'session')
                // secret key
                ->upOne()->withOptionalProperty('password_secret_key')
                ->isString()
                // password security
                ->upOne()->withOptionalProperty('password_security')
                ->oneOf('plaintext', 'hashed', 'encrypted')
                // storage
                ->upOne()->withProperty('storage')
                ->isObject()
                ->withProperty('type')
                ->isString();
        // import settings
        foreach ($this as $property => $value) {
            if (isset($settings->$property)) {
                $this->$property = $settings->$property;
            }
        }
        // enforce HTTPS
        if ($this->enforce_https) {
            $first = substr(WebUrl::getUrl(), 0, 5);
            $first = strtoupper($first);
            if ($first !== 'HTTPS') {
                $url = 'https:' . substr(WebUrl::getUrl(), 5);
                WebHttp::redirect($url);
            }
        }
        //
    }

    /**
     * Returns the URI for the authentication resource
     * @return string 
     */
    public function getURI() {
        return 'authentication';
    }

    /**
     * Authentication methods receive data from the client and return a stdClass
     * object with a username and password
     * @return object 
     */
    abstract public function receive($content_type);
    
    /**
     * Authentication methods send a challenge to the client in the requested
     * content-type. 
     */
    abstract public function send($content_type);
    
    /**
     * Returns whether the user is logged in.
     * @return boolean 
     */
    abstract public function isLoggedIn();

    /**
     * Returns the name of the current user
     * @return string 
     */
    abstract public function getCurrentUser();

    /**
     * Tests credentials; credentials are returned by fromRepresentation()
     * and should contain a username and password.
     * @param stdClass $credentials
     * @return boolean 
     */
    public function isValidCredential($credentials) {
        if (!$credentials)
            return false;
        // check existence
        if (!isset($credentials->username))
            return false;
        if (!isset($credentials->password))
            return false;
        // check validity
        if (!$this->getUser($credentials->username))
            return false;
        if ($credentials->password !== $this->getPassword($credentials->username))
            return false;
        // return
        return true;
    }
    
    /**
     * Returns the current user's roles
     * @return array 
     */
    public function getCurrentRoles(){
        $user = $this->getUser($this->getCurrentUser());
        if( !isset($user->roles) || !$user->roles ){
            return array();
        }
        else{
            return $user->roles;
        }
    }

    /**
     * Returns a user object given a username
     * @param string $username
     * @return AuthenticationUser
     */
    protected function getUser($username) {
        $users = $this->getStorage()->search('username', $username);
        if ($users) {
            $u = current($users);
            $roles = explode(',', $u->roles);
            $user = new SecurityUser($u->username, $u->password, array_map('trim', $roles));
            return $user;
        }
        return null;
    }

    /**
     * Returns a user's password
     * @param string $username 
     */
    protected function getPassword($username) {
        $user = $this->getUser($username);
        if (is_a($user, 'SecurityUser')) {
            return $user->getPassword($this->password_security, $this->password_secret_key);
        }
        return null;
    }

}