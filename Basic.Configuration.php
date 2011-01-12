<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Configuration{

    static private $path;
    const default_cache_interval = 3600;

    /**
     * Sets path to configuration file
     * @param <string> $path
     * @return <boolean>
     */
    public function setPath($path){
        if( is_file($path) ){
            self::$path = $path;
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Gets configuration path
     * @return <string>
     */
    public function getPath(){
        return self::$path;
    }

    /**
     * Get Configuration instance
     * @staticvar <array> $instance
     * @return <array>
     */
    // TODO: test whether caching Configuration is any faster
    static public function getInstance(){
        static $instance = null;
        if( !$instance ){
            $instance = self::read();
        }
        return $instance;
    }

    /**
     * Read configuration from path
     * @return <array>
     */
    static private function read(){
        if( self::$path && is_file(self::$path)){
            include(self::$path);
            // get vars
            $result = get_defined_vars();
            // remove some
            unset($result['_'], $result['_SERVER'], $result['argv']);
        }
        // return
        $result['base_dir'] = get_base_dir();
        return $result;
    }

    /**
     * Get configuration array from files
     * @return <array>
     */
    static public function __install(){
        $configuration_pattern = KNIFE_BASE_PATH.DS.'data'.DS.'config'.DS.'*';
        $configuration_files = glob($configuration_pattern);
        // get configuration files
        foreach($configuration_files as $file){
            if( is_file($file) ){
                include($file);
            }
        }
        unset($file);
        // get vars
        $result = get_defined_vars();
        // remove some
        unset($result['_'], $result['_SERVER'], $result['argv']);
        // return
        return $result;
    }

}