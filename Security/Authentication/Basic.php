<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for authenticating users with HTTP Basic Authentication
 */
class SecurityAuthenticationBasic extends SecurityAuthentication{

    /**
     * Message appears in login window
     * @var string 
     */
    public $message = 'pocket-knife authentication';
    
    /**
     * Returns whether the user is logged in; for HTTP authentication, the
     * logs in with every HTTP request
     * @return boolean 
     */
    public function isLoggedIn(){
        return false;
    }
    
    /**
     * Returns the name of the current user
     * @return type 
     */
    public function getCurrentUser(){
        return $_SERVER['PHP_AUTH_USER'];
    }
    
    /**
     * Returns response from an HTTP Basic Authentication request
     * @param string $content_type
     * @return stdClass 
     */
    public function fromRepresentation($content_type = null) {
        $out = new stdClass();
        // implemented as per http://us2.php.net/manual/en/features.http-auth.php
        $out->username = @$_SERVER['PHP_AUTH_USER'];
        $out->password = @$_SERVER['PHP_AUTH_PW'];
        // return
        return $out;
    }

    /**
     * Challenges the user with a HTTP Basic Authentication challenge
     * @param string $content_type 
     */
    public function toRepresentation($content_type = null, $data = null) {
        // send header
        header('WWW-Authenticate: Basic realm="' . $this->message . '"');
        // return representation
        $data = 'Access denied: login with HTTP Basic Authentication.';
        return parent::toRepresentation($content_type, $data);
    }

}