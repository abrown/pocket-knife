<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Prints formatted contents of a variable (cakePHP-style)
 * @param <mixed> $thing
 * @return <boolean> true
 */
function pr($thing) {
    echo '<pre>';
    if( is_null($thing) ) echo 'NULL';
    elseif( is_bool($thing) ) echo $thing ? 'TRUE' : 'FALSE';
    else print_r($thing);
    echo '</pre>';
    return ($thing) ? true : false; // for testing purposes
}

/**
 * Get publicly accessible properties of an object
 * Works by calling get_object_vars from outside the class scope
 * @param <object> $object
 * @return <array>
 */
function get_public_vars($object){
    return get_object_vars($object);
}

/**
 *
 * @return <type> 
 */
function get_base_dir(){
    return dirname(__FILE__);
}
