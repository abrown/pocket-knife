<?php
session_start();
class Session {
    function get($key){
        if( array_key_exists($key, $_SESSION) ) return $_SESSION[$key];
        else return null;
    }
    function put($key, $value){
        $_SESSION[$key] = $value;
    }
}