<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a framework for editing and storing configurations. By using the 
 * StorageFile storage method, the configuration file is stored in JSON, PHP, or
 * text format (e.g. "property=value"). The format used is determined by the
 * file extension: ".php" for PHP, ".json" for JSON, and ".config" for the plain
 * text format. As well, this class provides the copyTo() method to transfer
 * data from an instantiated configuration into another object.
 * @uses Error, StorageFile
 * @example
 * $config = new Settings('path/to/configuration-file.config');
 * // use properties directly
 * echo $config->some_property;
 * $config->some_property = '...';
 * // use properties using dot-notation
 * echo $config->get('some_property.some_array_index');
 * $config->set('some_property.another_property', '...');
 * // use copyTo() to transfer configuration data into an object
 * $class = new Class();
 * $config->copyTo($class);
 */
class Settings {

    /**
     * Current Settings data
     * @var object 
     */
    protected $data;

    /**
     * Path to load/save Settings file
     * @var string
     */
    protected $path;

    /**
     * Records whether the data has been changed
     * @var boolean
     */
    protected $changed = false;

    /**
     * Stores instance of StorageFile used to read/write files
     * @var StorageFile
     */
    protected $_storage;

    /**
     * Constructor; assume strings are paths, arrays/objects are configurations
     * @param mixed $list_or_file 
     */
    public function __construct($list_or_file = null) {
        $this->data = new stdClass();
        if (is_string($list_or_file)) {
            // assume this is a path
            $this->load($list_or_file);
        } elseif (is_array($list_or_file)) {
            // store as configuration
            $this->data = $list_or_file;
            $this->changed = true;
        } elseif (is_object($list_or_file)) {
            // store as configuration
            $this->data = $list_or_file;
            $this->changed = true;
        }
    }

    /**
     * Load Settings from file; if given a $path parameter, it will 
     * set it in the $path property.
     * @param string $path
     */
    public function load($path = null) {
        if ($path !== null) {
            $this->setPath($path);
        }
        // check existence
        if (!is_file($this->path)) {
            throw new Error('While trying to load the settings file, Settings could not open the file: ' . $this->path . '. Ensure the file exists and is readable.', 404);
        }
        // use StorageFile to get data
        $this->data = $this->getStorage()->read($this->getFileName());
    }

    /**
     * Write the Settings object to file; if given a $path parameter, it will 
     * set it in the $path property.
     * @param string $path
     */
    public function store($path = null) {
        if ($path !== null) {
            $this->setPath($path);
        }
        // only store if data is changed
        if (!$this->changed) {
            return;
        }
        // check existence
        if (!is_file($this->path)) {
            throw new Error('While trying to store the settings file, Settings could not open the file: ' . $this->path . '. Ensure the file exists and is readable.', 404);
        }
        // use StorageFile to get data
        $this->getStorage()->begin();
        $this->getStorage()->create($this->data, $this->getFileName());
        $this->getStorage()->commit();
    }

    /**
     * Copies a given configuration into an object's declared properties
     * @param mixed $settings 
     * @param mixed $object
     */
    public function copyTo(&$object) {
        if(!$this->hasData()) return;
        // add to object
        foreach ($this->getData() as $key => $value) {
            // check if property exists and add to the object
            if (property_exists($object, $key)) {
                $object->$key = $value;
            }
        }
    }

    /**
     * Reset data
     * @return void
     */
    public function reset() {
        $this->changed = true;
        $this->data = new stdClass();
    }

    /**
     * Return current data
     * @return mixed 
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Test whether data is set
     * @return boolean
     */
    public function hasData(){
        return is_object($this->getData()) || is_array($this->getData());
    }

    /**
     * Sets path to Settings file
     * @param string $path
     * @return boolean
     */
    public function setPath($path) {
        if (!is_file($path)) {
            throw new Error('While trying to set the path to a settings file, Settings could not find file given: ' . $path, 500);
        }
        // set new path
        $this->path = realpath($path);
        // unset storage so it is refreshed
        $this->_storage = null;
    }

    /**
     * Return whether the settings have changed.
     * @return boolean
     */
    public function isChanged() {
        return $this->changed;
    }

    /**
     * Checks inaccessible keys in current data for existence
     * @param string $key
     * @return booolean
     * */
    public function __isset($key) {
        if (is_object($this->data) && isset($this->data->$key)) {
            return true;
        } elseif (is_array($this->data) && isset($this->data[$key])) {
            return true;
        }
        return false;
    }

    /**
     * Gets inaccessible key from current data
     * @param string $key
     * @return any 
     * @example when calling $settings->some_property
     */
    public function __get($key) {
        if (is_object($this->data) && isset($this->data->$key)) {
            return $this->data->$key;
        } elseif (is_array($this->data) && isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Gets a key with dot-notation
     * @param string $key 
     * @return any
     * @example when calling $settings->get('prop.prop2.prop3')
     */
    public function get($key) {
        $keys = explode('.', $key);
        $thing = $this->data;
        foreach ($keys as $k) {
            if (is_object($thing) && property_exists($thing, $k)) {
                $thing = &$thing->$k;
            } elseif (is_array($thing) && array_key_exists($k, $thing)) {
                $thing = &$thing[$k];
            } else {
                $thing = null;
            }
        }
        return $thing;
    }

    /**
     * Sets inaccessible key from current data
     * @param string $key
     * @param any $value
     * @return any 
     * @example when calling $settings->some_property = 'value';
     */
    public function __set($key, $value) {
        $this->changed = true;
        if (is_object($this->data)) {
            $this->data->$key = $value;
        } elseif (is_array($this->data)) {
            $this->data[$key] = $value;
        }
    }

    /**
     * Sets a key with dot-notation
     * @param string $key 
     * @param any $value
     * @return boolean
     * @example when calling $settings->get('prop.prop2.prop3')
     */
    public function set($key, $value) {
        $this->changed = true;
        $keys = explode('.', $key);
        $thing = &$this->data;
        foreach ($keys as $k) {
            if (!is_object($thing) && !is_array($thing)) {
                $thing = new stdClass();
            }
            // set
            if (is_object($thing)) {
                $thing = &$thing->$k;
            } elseif (is_array($thing)) {
                $thing = &$thing[$k];
            }
        }
        $thing = $value;
    }

    /**
     * Return StorageFile instance used to read and write configuration files.
     * @return StorageFile
     */
    public function getStorage() {
        if (!isset($this->_storage)) {
            // get format
            $info = pathinfo($this->path);
            $format = 'config';
            if ($info['extension'] == 'json') {
                $format = 'json';
            } elseif ($info['extension'] == 'php') {
                $format = 'php';
            }
            // get location
            $location = dirname($this->path);
            // use StorageFile
            $this->_storage = new StorageFile(new Settings(array('location' => $location, 'format' => $format)));
        }
        return $this->_storage;
    }

    /**
     * Return name of the file, without directories or extensions; this is used
     * as the record ID by StorageFile
     * @return string
     */
    public function getFileName() {
        $info = pathinfo($this->path);
        return $info['filename'];
    }

}