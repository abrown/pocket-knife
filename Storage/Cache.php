<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
// TODO: check if overwriting another class cache with the same name; if so, throw warning
class StorageCache{

    /**
     * Default Cache Interval
     */
    const DEFAULT_CACHE_INTERVAL = 86400; // if no configuration set, cache for a day

    /**
     * Get path
     * @return <string>
     */
    function path(){
        chdir(dirname(__FILE__));
        return realpath('./cache');
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
        return file_put_contents($file, serialize($data));
    }

     /**
     * Delete from cache
     * @param <string> $key
     * @return <bool>
     */
    function delete($key){
        $file = self::path().DS.$key;
        $success = true;
        if( is_file($file) ) $success = unlink($file);
        return $success;
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
            if( array_key_exists('default_cache_interval', $config) ) $interval = $config['default_cache_interval'];
            else $interval = self::DEFAULT_CACHE_INTERVAL;
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
            if( array_key_exists('debug', $config) ) $debug = $config['debug'];
        }
        return ($debug) ? true : false;
    }

    /**
     * Clear cache folder
     */
    function clear(){
        $handle = opendir(self::path());
        if( !is_resource($handle) ) throw new Exception('Could not find cache path.', 404);
        // loop through dir
        while (false !== ($file = readdir($handle)) ){
            if( $file == '.htaccess' ) continue;
            if( $file == '.' || $file == '..' ) continue;
            unlink(self::path().DS.$file);
        }
        // close
        closedir($handle);
    }
}