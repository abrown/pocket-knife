<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Start tracking sessions
 */
session_start();

/**
 * WebSession
 * @uses
 */
class WebSession {
    function get($key){
        if( array_key_exists($key, $_SESSION) ) return $_SESSION[$key];
        else return null;
    }
    function put($key, $value){
        $_SESSION[$key] = $value;
    }
}