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
    $replaced = preg_replace('/([a-z])([A-Z])/', '$1/$2', $class);
    $replaced = str_replace('/', DS, $replaced);
    $path = get_base_dir() . DS . $replaced . '.php';
    if (!is_file($path)) {
        throw new Exception('Class '.$class.' not found at: '.$path, 500);
        return false;
    }
    require $path;
    return true;
}

/**
 * Hooks autoload function into PHP __autoload; will not work in PHP CLI mode
 */
if( !function_exists('__autoload') ){
    function __autoload( $class ) {
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
    echo '</pre>'."\n";
    return ($thing) ? true : false; // for testing purposes
}

/**
 * Get publicly accessible properties of an object
 * Works by calling get_object_vars from outside the class scope
 * @param <object> $object
 * @return <array>
 */
function get_public_vars($object) {
    return get_object_vars($object);
}

/**
 * Report errors
 */
ini_set('display_errors','2');
ERROR_REPORTING(E_ALL);

/**
 * Directory Separator (convenience)
 */
if( !defined('DS') ){
    define( 'DS', DIRECTORY_SEPARATOR );
}