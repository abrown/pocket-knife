<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for authenticating users
 * @uses ResourceList, SecurityUser
 */
class SecurityAuthentication extends ResourceList {

    /**
     * Forces the session to move to HTTPS
     * @var boolean 
     */
    public $enforce_https = true;

    /**
     * Type of authentication to start
     * @var int 
     */
    public $type = self::DIGEST;
    const BASIC = 0;
    const DIGEST = 1;
    const SESSION = 2;
    const TOKEN = 3;

    /**
     * Default message to display for Basic/Digest authentication
     * @var string 
     */
    public $message = 'pocket-knife authentication';

    /**
     * Secret key for hashing and encrypting passwords
     * @var string 
     */
    public $secret_key = '###';

    /**
     * One of: PLAINTEXT, HASHED, ENCRYPTED
     * @var int
     */
    public $password_security = self::ENCRYPTED;
    const PLAINTEXT = 0;
    const HASHED = 1;
    const ENCRYPTED = 2;

    /**
     * Returns a user object given a username
     * @param string $username
     * @return AuthenticationUser 
     */
    public function getUser($username) {
        $users = $this->getStorage()->search('username', $username);
        if ($users)
            return current($users);
        else
            return null;
    }

    /**
     * Logs a user in
     * @param type $username
     * @param type $password
     * @return type 
     */
    public function login($username, $password) {
        // get user
        $user = $this->getUser($username);
        if (!$user) {
            return false;
        }
        // check password
        if (!$user->isPassword($password, $this->password_security)) {
            return false;
        }
        // return 
        return true;
    }

    public function fromRepresentation($content_type) {
        $representation = null;
        switch ($context_type) {
            case 'text/html':
            case 'application/x-www-urlencoded':

                break;
            // Basic | Digest
            default:
                $representation = new RepresentationText();
                // Basic HTTP Authentication
                if ($this->basic) {
                    $representation->setData($this->fromBasicAuthentication());
                }
                // Digest HTTP Authentication
                elseif ($this->digest) {
                    $representation->setData($this->fromDigestAuthentication());
                }
                break;
        }
        // return
        return $representation;
    }

    /**
     * Types
     *  session
     *  token
     *  basic
     *  digest
     * 
     * 
     * @param type $content_type
     * @param type $data 
     */
    public function toRepresentation($content_type, $data) {
        switch ($context_type) {
            case 'text/html':
                $form = '<form action="">';
                $form .= '<input type="text" name="u" />';
                $form .= '<input type="password" name="p" />';
                $form .= '</form>';
                break;
            case 'application/json':

                break;
            // Basic | Digest
            default:
                if ($this->basic) {
                    header('WWW-Authenticate: Basic realm="' . $this->message . '"');
                } elseif ($this->digest) {
                    $options[] = 'realm="' . $this->message . '"';
                    $options[] = 'qop="auth"';
                    $options[] = 'nonce="' . uniqid('pocket-knife:', true) . '"';
                    $options[] = 'opaque="' . md5($this->message) . '"';
                    header('WWW-Authenticate: Digest ' . implode(',', $options));
                }
                break;
        }
    }

    /**
     * Returns username and password from an HTTP basic authentication response
     * @return stdClass 
     */
    protected function fromBasicAuthentication() {
        $out = new stdClass();
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Basic (.*)/i', $_SERVER['HTTP_AUTHORIZATION'], $match)) {
                $string = trim($match[1]);
                $string = base64_decode($string);
                list($out->username, $out->password) = explode(':', $string, 2);
            }
        }
        // return
        return $out;
    }

    /**
     * Returns username and password from an HTTP digest authentication response
     * @return stdClass 
     */
    protected function fromDigestAuthentication() {
        $out = new stdClass();
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            // from http://us3.php.net/manual/en/features.http-auth.php
            $needed_parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
            $data = array();
            $keys = implode('|', array_keys($needed_parts));
            preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $_SERVER['HTTP_AUTHORIZATION'], $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $data[$m[1]] = $m[3] ? $m[3] : $m[4];
                unset($needed_parts[$m[1]]);
            }
            if ($needed_parts || !$data) {
                return null;
            }
            // parse $data
            extract($data);
            // create valid response
            $password = @$this->getUser($username)->getPassword($this->password_security);
            $A1 = md5($username . ':' . $this->message . ':' . $password);
            $A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $uri);
            $valid = md5($A1 . ':' . $nonce . ':' . $nc . ':' . $cnonce . ':' . $qop . ':' . $A2);
            // check
            if ($response === $valid) {
                $out->username = $username;
                $out->password = $password;
            }
        }
        // return
        return $out;
    }

}