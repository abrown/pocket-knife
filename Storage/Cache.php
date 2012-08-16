<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
define('CACHE_PATH', get_base_dir() . '/Storage/cache.json');

class StorageCache {

    protected static $storage = array('type' => 'json', 'location' => CACHE_PATH);

    /**
     * Creates and returns storage object
     * @return StorageInterface
     */
    public static function getStorage() {
        static $storage = null;
        if (!$storage) {
            $settings = new Settings(self::$storage);
            // check Settings
            if (!isset($settings->type))
                throw new Error('Storage type is not defined', 500);
            // get class
            $class = 'Storage' . ucfirst($settings->type);
            // check parents
            if (!in_array('StorageInterface', class_implements($class)))
                throw new Error($class . ' must implement StorageInterface.', 500);
            // create object
            $storage = new $class($settings);
        }
        return $storage;
    }

    /**
     * Set storage configuration
     * @param Settings $settings 
     */
    public static function setStorage($settings) {
        self::$storage = $settings;
    }

    /**
     * Build Etag for this resource; the Etag consists of an MD5
     * hash of "[uri]:[last modified time]:[modification number]";
     * the addition of the modification number makes this a strong
     * Etag.
     * @param string $uri
     * @return string or null if not exists
     */
    public static function getEtag($uri) {
        if (self::getStorage()->exists($uri)) {
            $o = self::getStorage()->read($uri);
            return md5("{$uri}:{$o->t}:{$o->n}");
        }
        return null;
    }

    /**
     * Return the unix time the resource was last changed.
     * @param string $uri
     * @return int or null
     */
    public static function getLastModified($uri) {
        if (self::getStorage()->exists($uri)) {
            $o = self::getStorage()->read($uri);
            return $o->t;
        }
        return null;
    }

    /**
     * Mark a resource as changed.
     * @param string $uri
     */
    public static function markModified($uri) {
        // start db
        self::getStorage()->begin();
        // create object: uri = {t: last modified time, n: number of modifications}
        $o = new stdClass();
        $o->t = time();
        // create or update
        if (self::getStorage()->exists($uri)) {
            $_o = self::getStorage()->read($uri);
            $o->n = $_o->n + 1;
            self::getStorage()->update($o, $uri);
        } else {
            $o->n = 1;
            self::getStorage()->create($o, $uri);
        }
        // commit
        self::getStorage()->commit();
    }
    
    public static function delete($uri){
        // start db
        self::getStorage()->begin();
        // create or update
        if (self::getStorage()->exists($uri)) {
            self::getStorage()->delete($uri);
        }
        // commit
        self::getStorage()->commit();        
    }

    /**
     * Test whether the resource has been modified since the last 
     * request for it.
     * @param string $uri
     * @return boolean
     */
    public static function isModified($uri) {
        // check etag
        $etag = self::getEtag($uri);
        //pr(WebHttp::getIfNoneMatch());
        //pr($etag);
        if (WebHttp::getIfNoneMatch() && WebHttp::getIfNoneMatch() == $etag) {
            return false;
        }
        // check last-modified time
        $last_modified = self::getLastModified($uri);
        //pr(WebHttp::getIfModifiedSince());
        //pr($last_modified);
        if (WebHttp::getIfModifiedSince() && WebHttp::getIfModifiedSince() >= $last_modified) {
            return false;
        }
        // else
        return true;
    }

    public static function sendModified($uri) {
        // mark if necessary
        if (!self::getStorage()->exists($uri)) {
            self::markModified($uri);
        }
        // retrieve Etag, Last-Modified
        $etag = self::getEtag($uri);
        $last_modified = self::getLastModified($uri);
        // send headers
        header('Etag: "' . $etag . '"');
        header('Last-Modified: ' . $last_modified);
    }

    public static function sendNotModified() {
        header('HTTP/1.1 304 Not Modified');
        //header('Not Modified', true, 304);
        
    }

    /**
      function path() {
      chdir(dirname(__FILE__));
      return realpath('./cache');
      }

      function valid($key, $interval = null) {
      if (is_null($interval)) {
      $interval = self::getDefaultInterval();
      }
      // get file
      $file = self::path() . DS . $key;
      if (!is_file($file)) {
      return false;
      }
      // check interval
      $valid = (filectime($file) + $interval) > time() ? true : false;
      // if debugging, always refresh cache
      if (self::isDebug())
      $valid = false;
      // return
      return $valid;
      }

      function read($key) {
      $file = self::path() . DS . $key;
      if (!is_file($file))
      return null;
      $content = file_get_contents($file);
      return unserialize($content);
      }

      function write($key, $data) {
      $file = self::path() . DS . $key;
      return file_put_contents($file, serialize($data));
      }

      function delete($key) {
      $file = self::path() . DS . $key;
      $success = true;
      if (is_file($file))
      $success = unlink($file);
      return $success;
      }

      function getDefaultInterval() {
      static $interval = null;
      if (!$interval) {
      $config = Settings::getInstance();
      if (array_key_exists('default_cache_interval', $config))
      $interval = $config['default_cache_interval'];
      else
      $interval = self::DEFAULT_CACHE_INTERVAL;
      }
      return $interval;
      }

      function isDebug() {
      static $debug = null;
      if (is_null($debug)) {
      $config = Settings::getInstance();
      if (array_key_exists('debug', $config))
      $debug = $config['debug'];
      }
      return ($debug) ? true : false;
      }

      function clear() {
      $handle = opendir(self::path());
      if (!is_resource($handle))
      throw new Error('Could not find cache path.', 404);
      // loop through dir
      while (false !== ($file = readdir($handle))) {
      if ($file == '.htaccess')
      continue;
      if ($file == '.' || $file == '..')
      continue;
      unlink(self::path() . DS . $file);
      }
      // close
      closedir($handle);
      }
     */
}