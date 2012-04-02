<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * BasicClass
 * @uses BasicDocumentation, Error
 */
class BasicClass{
    
    /**
     * Recursively loads all dependent classes
     * @param string $class
     * @return boolean 
     */
    public static function autoloadAll($class){
        // check
        if( class_exists($class, false) ) return true;
        // get dependencies
        $dependencies = self::findDependencies($class);
        // get interfaces first
        $interfaces = preg_grep('/interface/i', $dependencies);
        foreach($interfaces as $_class){
            if( !interface_exists($_class) ) autoload($_class);
        }
        // then rest of classes
        foreach($dependencies as $_class){
            if( !class_exists($_class) && !interface_exists($_class) ) autoload($_class);
        }      
        // return
        return true;
    }
    
    /**
     * Performs breadth-first search of dependencies
     * @param string $class
     * @param array $dependencies
     * @return array 
     */
    public static function findDependencies( $class, $dependencies = array() ){
        if( !$class ) return array();
        // add self to dependencies
        if( !in_array($class, $dependencies) ) $dependencies[] = $class;
        // get path to class
        $path = self::getPathToClass($class);
        // get dependencies from '@uses' annotation
        $content = file_get_contents($path);
        if( preg_match('/@uses (.*)/', $content, $matches) ){
            $classes = explode(',', $matches[1]);
            foreach($classes as $_class){
                $_class = trim($_class);
                if( !$_class ) continue;
                if( in_array($_class, $dependencies) ) continue;
                else $dependencies[] = $_class;
                $dependencies = BasicClass::findDependencies($_class, $dependencies);
            }
        }
        return $dependencies;
    }
    
    /**
     * Returns absolute path to the class file
     * @param string $class
     * @return string 
     */
    public static function getPathToClass($class){
        $replaced = preg_replace('/([a-z])([A-Z])/', '$1/$2', $class);
        $replaced = str_replace('/', DS, $replaced);
        $path = get_base_dir() . DS . $replaced . '.php';
        if (!is_file($path)){
            $message = 'Class '.$class.' not found at: '.$path;
            if( class_exists('Error') ) throw new Error($message, 500);
            else trigger_error($message, E_USER_ERROR);
        }
        return $path;
    }
    
    /**
     * Returns HTML-formatted documentation for a given class
     * @param string $class
     * @return string HTML
     */
    public static function getDocumentation($class){
    	$documentation = new BasicDocumentation($class);
    	return $documentation->getHtml($class);
    }
}