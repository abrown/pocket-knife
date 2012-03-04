<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Represents a loggeded-in (authenticated) user; uses AES (Rijndael) to encrypt
 * and decrypt and MD5 to hash.
 * @uses
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
                throw new ExceptionSettings('Password encryption option not available: ' . $encryption);
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
                throw new ExceptionSettings('Password encryption option not available: ' . $encryption);
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
                throw new ExceptionSettings('Cannot get password from hash.');
                break;
            case 'encrypted':
                return $this->decrypt($this->password, $key);
                break;
            default:
                throw new ExceptionSettings('Password encryption option not available: ' . $encryption);
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
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_CBC, md5(md5($key)));
        return base64_encode($encrypted);
    }

    /**
     * Decrypts a string with a system-defined string
     * @param string $data 
     * @return string
     */
    protected function decrypt($data, $key) {
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($data), MCRYPT_MODE_CBC, md5(md5($key)));
        return rtrim($decrypted, "\0");
    }

}