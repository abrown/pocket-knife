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
class SecurityUser extends ResourceItem{
    
    public $username;
    public $password;
    public $password_encryption;
    
    /**
     * Checks whether a password is valid
     * @param string $password
     * @return boolean 
     */
    public function checkPassword($password){
        if( $this->password_encryption == 'plain' ) return $password == $this->password;
        else if( $this->password_encryption == 'hashed' ) return $this->hash($password) == $this->password;
        else if( $this->password_encryption == 'encrypted' ) return $password == $this->decrypt($this->password);
        else throw new ExceptionSettings('Password encryption option not available: '.$this->password_encryption);
    }
    
    /**
     * Hashes a string with the MD5 algorithm
     * @param string $data
     * @return string 
     */
    protected function hash($data){
        return md5($data);
    }
    
    /**
     * Encrypts a string with a system-defined key
     * @param string $data
     * @return string
     */
    protected function encrypt($data){
        $key = Settings::get('encryption_key');
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_CBC, md5(md5($key)));
        return base64_encode($encrypted);
    }
    
    /**
     * Decrypts a string with a system-defined string
     * @param string $data 
     * @return string
     */
    protected function decrypt($data){
        $key = Settings::get('encryption_key');
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($key)));
        return rtrim($decrypted, "\0");
    }
}