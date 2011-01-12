<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Cache{

    /**
     * Get path
     * @return <string>
     */
    function path(){
        return realpath('../cache/');
    }

    /**
     * Check if cache is still valid
     * @param <string> $key
     * @param <int> $interval (seconds)
     * @return <bool>
     */
    function valid($key, $interval = null){
        if( is_null($interval) ){
            $interval = self::getDefaultInterval();
        }
        // get file
        $file = self::path().DS.$key;
        if( !is_file($file) ){
            return false;
        }
        // check interval
        $valid = (filectime($file) + $interval) > time() ? true : false;
        // if debugging, always refresh cache
        if( self::isDebug() ) $valid = false;
        // return
        return $valid;
    }

    /**
     * Read from cache
     * @param <string> $key
     * @return <mixed>
     */
    function read($key){
        $file = self::path().DS.$key;
        if( !is_file($file) ) return null;
        $content = file_get_contents($file);
        return unserialize($content);
    }

    /**
     * Write to cache
     * @param <string> $key
     * @param <mixed> $data
     * @return <bool>
     */
    function write($key, $data){
        $file = self::path().DS.$key;
        pr($file);
        return file_put_contents($file, serialize($data));
    }

     /**
     * Delete from cache
     * @param <string> $key
     * @return <bool>
     */
    function delete($key){
        $file = self::path().DS.$key;
        return unlink($file);
    }

    /**
     * Get default cache interval
     * @staticvar <int> $interval (seconds)
     * @return <int> Seconds
     */
    function getDefaultInterval(){
        static $interval = null;
        if( !$interval ){
            $config = Configuration::getInstance();
            $interval = $config['default_cache_interval'];
        }
        return $interval;
    }

    /**
     * Tests whether we are in debug mode
     * @staticvar string $debug
     * @return <boolean>
     */
    function isDebug(){
        static $debug = null;
        if( is_null($debug) ){
            $config = Configuration::getInstance();
            $debug = $config['debug'];
        }
        return ($debug) ? true : false;
    }
}