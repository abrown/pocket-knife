<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

// start session
session_name('pocket-knife');
session_start();

/**
 * Provides static methods to help with web sessions.
 * @example
 * // first request
 * WebSession::put('var', 5);
 * 
 * // second request
 * $var = WebSession::get('var');
 */
class WebSession {

    /**
     * Returns a session key. This method is a wrapper for the PHP
     * $_SESSION array.
     * @param mixed $key
     * @return mixed
     */
    static public function get($key) {
        if (array_key_exists($key, $_SESSION))
            return $_SESSION[$key];
        else
            return null;
    }

    /**
     * Saves a session key. This method is a wrapper for the PHP
     * $_SESSION array.
     * @param string $key
     * @param mixed $value
     */
    static public function put($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Clears a session key or the entire session
     * @param mixed $key 
     */
    static public function clear($key = null){
        if( is_null($key)){
            $_SESSION = array();
            session_destroy();
        }
        else{
            unset($_SESSION[$key]);
        }
    }

}