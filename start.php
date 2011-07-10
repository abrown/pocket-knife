<?php
/**
 * @copyright Copyright 2009 Gearbox Studios. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Include Common Functions
 */
require('Basic.Functions.php');
require('Basic.Configuration.php');

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

/**
 * Auto-load classes
 * This function looks first throught the Pocket Knife classes, then searches
 * through the user-defined classes designated in the configuration file in the
 * 'includes' array ($config['includes']); this function will fix trailing
 * slashes. For user-defined classes, the file name should be the class name
 * with the '.php' filename extension.
 * @example Class 'ExampleClass' should be in a file 'ExampleClass.php'
 * @param <string> $class
 */
if( !function_exists('__autoload') ){
    function __autoload( $class ) {
        $file = null;
        // look in pocket-knife directory first
        if( strpos($class, 'App') !== false ){
            $file = preg_replace('/([a-z])([A-Z])/', '$1.$2', $class).'.php';
        }
        elseif( strpos($class, 'Auth') !== false ){
            $file = 'Security.Authentication.php';
        }
        else{
            // TODO: fix this mess
            $basic = array('Cache', 'Configuration', 'Functions', 'Http', 'Inflection', 'Routing', 'Scheduler', 'Set', 'Template', 'Test', 'Timer', 'Validation');
            if( in_array($class, $basic) ) $file = 'Basic.'.$class.'.php';
        }
        if( $file ){
            require get_base_dir().DS.$file;
            return true;
        }
        // then look in configured directories
        $config = Configuration::getInstance();
        if( array_key_exists('includes', $config) ){
            if( !is_array($config['includes']) ) $config['includes'] = array($config['includes']); // TODO: fix this hack
            foreach($config['includes'] as $dir){
                // create path
                $last = $dir[ strlen($dir) - 1 ];
                $dir = $last == DS ? $dir : $dir.DS;
                $file = $dir.$class.'.php';
                // include
                if( is_file($file) ) {
                    require($file);
                    return true;
                }
                // include relative paths within pocket-knife
                elseif( $file[0] != '/' && is_file(get_base_dir().DS.$file) ){
                    require(get_base_dir().DS.$file);
                    return true;
                }
            }
        }
        // could not find file
        trigger_error('Failed to find class: '.$class, E_USER_WARNING);
        return false;
    }
}
else{
    throw_error('__autoload is not available', E_USER_WARNING);
}