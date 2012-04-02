<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for authenticating users with HTTP headers
 */
class SecurityAuthenticationHeader extends SecurityAuthentication{

    /**
     * The header used to pass the 'username'
     * @var string 
     */
    public $username_header = 'X-PK-USERNAME';
    
    /**
     * The header used to pass the 'password'
     * @var string 
     */
    public $password_header = 'X-PK-PASSWORD';
    
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
        return $_SERVER[$this->username_header];
    }
    
    /**
     * Returns response from a PHP session authentication request
     * @param string $content_type
     * @return stdClass 
     */
    public function fromRepresentation($content_type = null) {
        $out = new stdClass();
        $out->username = @$_SERVER[$this->username_header];
        $out->password = @$_SERVER[$this->password_header];
        // return
        return $out;
    }

    /**
     * Challenges the user with a PHP session authentication challenge
     * @param string $content_type 
     */
    public function toRepresentation($content_type = null, $data = null) {
        // set one time key
        WebSession::put('one_time_key', uniqid());
        // create challenge by content types
        switch ($content_type) {
            case 'application/octet-stream':
            case 'text/plain':
                $data = 'Submit authentication request as HTTP headers:'."\n";
                $data .= $this->username_header.': XXX'."\n";
                $data .= $this->password_header.': XXX'."\n";
            case 'application/json':
            case 'application/xml':
                $message = 'Submit authentication request as HTTP headers:'."\n";
                $message .= $this->username_header.': XXX'."\n";
                $message .= $this->password_header.': XXX'."\n";
                $data = new stdClass();
                $data->message = $message;
            case 'application/x-www-form-urlencoded':
            case 'multipart/form-data':
            case 'text/html':
                $data = 'Submit authentication request as HTTP headers:'."<br/>\n";
                $data .= $this->username_header.': XXX'."<br/>\n";
                $data .= $this->password_header.': XXX'."<br/>\n";
                break;
            default:
                throw new Error('Unknown content type', 400);
                break;
        }
        // return
        return parent::toRepresentation($content_type, $data);
    }
}