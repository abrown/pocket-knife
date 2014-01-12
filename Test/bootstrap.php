<?php

/**
 * Determine and return the absolute location of the Test directory
 * @return string
 */
function get_test_dir() {
    return dirname(__FILE__);
}

/**
 * Return the location of the test folder to write to; use get_writeable_dir()
 * throughout test classes when reading/writing to files is required.
 * @return string
 */
function get_writeable_dir() {
    return get_test_dir() . DS . 'Sandbox' . DS . 'writeable';
}

/**
 * Override 'get_http_body' to mock HTTP inputs; to use, simply set
 * $_SERVER['REQUEST_BODY'] with the data sent in the mock HTTP request.
 */
if (!function_exists('get_http_body')) {
    function get_http_body() {
        return isset($_SERVER['REQUEST_BODY']) ? $_SERVER['REQUEST_BODY'] : null;
    }
}

/**
 * Override 'apache_request_headers' to mock HTTP inputs; to use, simply set
 * $_SERVER['REQUEST_HEADERS'] with the headers sent in the mock HTTP request.
 */
if(!function_exists('apache_request_headers')){
    function apache_request_headers(){
        return isset($_SERVER['REQUEST_HEADERS']) ? $_SERVER['REQUEST_HEADERS'] : array();
    }
}
/**
 * Set debugging to show error messages before pocket-knife/start.php gets to this
 */
define('DEBUGGING', 1);

/**
 * Load pocket-knife framework
 */
$path = dirname(get_test_dir());
require_once $path . '/start.php';

/**
 * Add include paths for autoloading
 */
add_include_path(get_base_dir());
add_include_path(get_test_dir());
add_include_path(get_test_dir().DS.'Sandbox');