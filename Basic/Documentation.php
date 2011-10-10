<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * BasicDocumentation
 * @uses
 */
// TODO: implement recursive call to autoloadAll to get second, third, etc.,-layer dependencies
class BasicDocumentation{
    public static function autoloadAll($class){
        $autoloaded = array();
        // autoload main
        autoload($class);
        $autoloaded[] = $class;
        // get file name
        $replaced = preg_replace('/([a-z])([A-Z])/', '$1/$2', $class);
        $replaced = str_replace('/', DS, $replaced);
        $path = get_base_dir() . DS . $replaced . '.php';
        if (!is_file($path)) throw new ExceptionFile('Class '.$class.' not found at: '.$path, 500);
        // get dependencies
        $content = file_get_contents($path);
        if( preg_match('/@uses (.*)/', $content, $matches) ){
            $classes = explode(',', $matches[1]);
            foreach($classes as $class){
                $class = trim($class);
                autoload($class);
                $autoloaded[] = $class;
            }
        }
        return $autoloaded;
    }
}