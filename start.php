<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Finds the base directory of the pocket knife installation
 * @return <type> 
 */
function get_base_dir() {
    return dirname(__FILE__);
}

/**
 * Autoloads classes using camel-case
 * @example Class 'ExampleClass' should be found in /base/directory/Class/Example.php
 * @param string $class 
 */
function autoload($class) {
    if (class_exists($class, false)){
        return true;
    }
    $replaced = preg_replace('/([a-z])([A-Z])/', '$1/$2', $class);
    $replaced = str_replace('/', DS, $replaced);
    $path = get_base_dir() . DS . $replaced . '.php';
    if (!is_file($path)) {
        throw new Error('Class \'' . $class . '\' not found at \'' . $path . '\'', 404);
        return false;
    }
    require $path;
    return true;
}

/**
 * Hooks autoload function into PHP __autoload; will not work in PHP CLI mode
 */
if (!function_exists('__autoload')) {

    function __autoload($class) {
        return autoload($class);
    }

}

/**
 * Prints formatted contents of a variable (cakePHP-style)
 * @param <mixed> $thing
 * @return <boolean> true
 */
function pr($thing) {
    echo '<pre>';
    if (is_null($thing))
        echo 'NULL';
    elseif (is_bool($thing))
        echo $thing ? 'TRUE' : 'FALSE';
    else
        print_r($thing);
    echo '</pre>' . "\n";
    return ($thing) ? true : false; // for testing purposes
}

/**
 * Get publicly accessible properties of an object
 * Works by calling get_object_vars from outside the class scope
 * @param mixed $object
 * @return array
 */
function get_public_vars($object) {
    return get_object_vars($object);
}

/**
 * Returns a string containing the body of the HTTP request
 * @return string
 */
if (!function_exists('get_http_body')) {

    function get_http_body() {
        return file_get_contents('php://input');
    }

}

/**
 * Converts arrays into objects
 * From Richard Castera, http://www.richardcastera.com/blog/php-convert-array-to-object-with-stdclass
 * @param array $thing
 * @return stdClass 
 */
function to_object($thing) {
    // case: numeric strings
    if (is_string($thing) && is_numeric($thing)) {
        if (strpos($thing, '.') !== false)
            return floatval($thing);
        else
            return intval($thing);
    }
    // case: boolean
    elseif ($thing === 'true') {
        return true;
    }
    // case: boolean
    elseif ($thing === 'false') {
        return false;
    }
    // case: rest of values objects
    elseif (is_scalar($thing)) {
        return $thing;
    }
    // case: valid array
    elseif (is_array($thing) && count($thing) > 0) {
        // create object
        $object = new stdClass();
        // loop through array
        foreach ($thing as $name => $value) {
            $name = strtolower(trim($name));
            if (strlen($name) > 0) {
                $object->$name = to_object($value);
            }
        }
        // return
        return $object;
    }
    // case: already an object
    elseif (is_object($thing)) {
        return $thing;
    }
    // case: nothing to return
    else {
        return null;
    }
}

/**
 * Report errors
 */
ini_set('display_errors', '2');
ERROR_REPORTING(E_ALL);

/**
 * Directory Separator (convenience)
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}