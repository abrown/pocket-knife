<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * BasicFile
 * @uses
 */
class BasicFile{
    
    /**
     * Recursively loads classes
     * @param type $class
     * @return type 
     */
    public static function autoloadAll($class){
        // check
        if( !$class ) return array();
        if( class_exists($class, false) ) return array();
        // setup
        $autoloaded = array();
        // autoload class
        autoload($class);
        $autoloaded[] = $class;
        // get file name so we can look at '@uses'
        $replaced = preg_replace('/([a-z])([A-Z])/', '$1/$2', $class);
        $replaced = str_replace('/', DS, $replaced);
        $path = get_base_dir() . DS . $replaced . '.php';
        if (!is_file($path)) throw new ExceptionFile('Class '.$class.' not found at: '.$path, 500);
        // get dependencies from '@uses' annotation
        $content = file_get_contents($path);
        if( preg_match('/@uses (.*)/', $content, $matches) ){
            $classes = explode(',', $matches[1]);
            foreach($classes as $class){
                $class = trim($class);
                $dependencies = BasicFile::autoloadAll($class);
                $autoloaded = array_merge($autoloaded, $dependencies);
            }
        }
        return $autoloaded;
    }
}