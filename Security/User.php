<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Represents a loggeded-in (authenticated) user; uses AES (Rijndael) to encrypt
 * and decrypt and MD5 to hash.
 * @uses ResourceItem, Error
 */
class SecurityUser extends ResourceItem {

    public $username;
    public $password;
    public $roles = array();

    /**
     * Constructor
     * @param string $username
     * @param string $password 
     */
    function __construct($username, $password, $roles = array()) {
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles;
    }

    /**
     * Returns the URI for this resource
     * @return type 
     */
    public function getUri() {
        return 'user';
    }

    /**
     * Checks whether a password is valid
     * @param string $password
     * @param int $encryption
     * @param string $key encryption/decryption key
     * @return boolean 
     */
    public function isPassword($password, $encryption, $key = null) {
        switch ($encryption) {
            case 'plaintext':
                return $password == $this->password;
                break;
            case 'hashed':
                return $this->hash($password) == $this->password;
                break;
            case 'encrypted':
                return $password == $this->decrypt($this->password, $key);
                break;
            default:
                throw new Error('Password encryption option not available: ' . $encryption);
                break;
        }
    }

    /**
     * Returns the password
     * @param string $password
     * @param int $encryption
     * @param string $key encryption/decryption key
     * @return string 
     */
    public function setPassword($password, $encryption, $key = null) {
        switch ($encryption) {
            case 'plaintext':
                $this->password = $password;
                break;
            case 'hashed':
                $this->password = $this->hash($password, $key);
                break;
            case 'encrypted':
                $this->password = $this->encrypt($password, $key);
                break;
            default:
                throw new Error('Password encryption option not available: ' . $encryption);
                break;
        }
    }

    /**
     * Returns the password
     * @param int $encryption
     * @param string $key encryption/decryption key
     * @return string 
     */
    public function getPassword($encryption, $key = null) {
        switch ($encryption) {
            case 'plaintext':
                return $this->password;
                break;
            case 'hashed':
                throw new Error('Cannot get password from hash.');
                break;
            case 'encrypted':
                return $this->decrypt($this->password, $key);
                break;
            default:
                throw new Error('Password encryption option not available: ' . $encryption);
                break;
        }
    }

    /**
     * Hashes a string with the MD5 algorithm
     * @param string $data
     * @param string $key
     * @return string 
     */
    protected function hash($data, $key) {
        return md5($key . $data);
    }

    /**
     * Encrypts a string with a system-defined key
     * @param string $data
     * @return string
     */
    protected function encrypt($data, $key) {
        // check for mcrypt
        if (!function_exists('mcrypt_encrypt')) {
            throw new Error('User password encryption is not available because php-mcrypt is not installed. On a Debian-based Linux distro, run "sudo apt-get install php5-mcrypt" and restart the web server.', 500);
        }
        // encrypt
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_CBC, md5(md5($key)));
        return base64_encode($encrypted);
    }

    /**
     * Decrypts a string with a system-defined string
     * @param string $data 
     * @return string
     */
    protected function decrypt($data, $key) {
        // check for mcrypt
        if (!function_exists('mcrypt_decrypt')) {
            throw new Error('User password encryption is not available because php-mcrypt is not installed. On a Debian-based Linux distro, run "sudo apt-get install php5-mcrypt" and restart the web server.', 500);
        }
        // decrypt
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($data), MCRYPT_MODE_CBC, md5(md5($key)));
        return rtrim($decrypted, "\0");
    }

}