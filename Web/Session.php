<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
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

}