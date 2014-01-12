<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for authenticating users with HTTP Digest Authentication
 * @uses SecurityAuthentication, Representation
 */
class SecurityAuthenticationDigest extends SecurityAuthentication {

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
    public function isLoggedIn() {
        return false;
    }

    /**
     * Returns the name of the current user
     * @return type 
     */
    public function getCurrentUser() {
        $data = $this->parse($_SERVER['PHP_AUTH_DIGEST']);
        return $data['username'];
    }

    /**
     * Returns response from an HTTP Digest Authentication request
     * @param string $content_type
     * @return stdClass 
     */
    public function receive($content_type) {
        $out = new stdClass();
        if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $data = $this->parse($_SERVER['PHP_AUTH_DIGEST']);
            if ($data == false)
                return $out;
            // create valid response
            $password = $this->getPassword($data['username']);
            $A1 = md5($data['username'] . ':' . $this->message . ':' . $password);
            $A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);
            $valid_response = md5($A1 . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);
            // check
            if ($data['response'] === $valid_response) {
                $out->username = $data['username'];
                $out->password = $password;
            }
        }
        // return
        return $out;
    }

    /**
     * Parses request, see http://us2.php.net/manual/en/features.http-auth.php
     * @param string $string
     * @return mixed 
     */
    protected function parse($string) {
        // protect against missing data
        $needed_parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
        $data = array();
        $keys = implode('|', array_keys($needed_parts));
        preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $string, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $data[$m[1]] = $m[3] ? $m[3] : $m[4];
            unset($needed_parts[$m[1]]);
        }
        return $needed_parts ? false : $data;
    }

    /**
     * Challenges the user with a HTTP Digest Authentication challenge
     * @param string $content_type 
     */
    public function send($content_type) {
        // send header
        $options[] = 'realm="' . $this->message . '"';
        $options[] = 'qop="auth"';
        $options[] = 'nonce="' . uniqid('pocket-knife:', true) . '"';
        $options[] = 'opaque="' . md5($this->message) . '"';
        header('WWW-Authenticate: Digest ' . implode(',', $options));
        // return representation
        $representation = new Representation('Access denied: login with HTTP Digest Authentication.', $content_type);
        $representation->send(401);
    }

}