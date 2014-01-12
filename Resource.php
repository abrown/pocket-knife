<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Resource defines a standard way for the service to interact with the classes
 * created in an application. Service uses fromRepresentation() and toRepresentation()
 * to handle data input and output and calls a class method to do the processing.
 * @example
 * // step 1: receive
 * $input = $resource->fromRepresentation($content_type);
 * // step 2: process
 * $output = $resource->HTTP_METHOD($input);
 * // step 3: send
 * $resource->toRepresentation($content_type, $output);
 * @uses Error, Settings
 */
abstract class Resource {

    protected $route = null;
    
    /**
     * Defines storage method for the resource; see classes in Storage for specific parameters required
     * @example $this->storage = array('type'=>'mysql', 'username'=>'test', 'password'=>'password', 'location'=>'localhost', 'database'=>'db');
     * @var array
     */
    protected $storage = array('type' => 'json', 'location' => 'db.json');

    /**
     * Used to store instantiated storage objects for later re-use.
     * @var StorageInterface 
     */
    protected $_storage;

    /**
     * By default, resources are cached on the client-side using
     * ETags. Caching only takes effect on idempotent methods (GET,
     * HEAD, OPTIONS).
     * @var boolean 
     */
    protected $cacheable = true;

    /**
     * Defines the representations allowed by this resource; '*' indicates that
     * any content-type may be used to access this resource
     * @example $this->representation = array('text/html', 'application/json');
     * @var array 
     */
    protected $representation = '*';

    /**
     * Template settings to apply to the output after processing; see 
     * WebTemplate for order of constructor parameters
     * @example $this->template = array('some/file.php', WebTemplate::PHP_FILE);
     * @var array
     */
    //protected $template;

    /**
     * Validation settings...
     * @TODO implement
     * @var array 
     */
    protected $validation = false;

    /**
     * Return the object URI
     */
    public abstract function getURI();

    /**
     * Sets storage configuration
     * @param Settings $settings 
     */
    public function setStorage($settings) {
        $this->storage = $settings;
    }

    /**
     * Creates and returns storage object
     * @return StorageInterface
     */
    public function getStorage() {
        if (!property_exists($this, '_storage') || !$this->_storage) {
            $settings = new Settings((object) $this->storage);
            // check Settings
            if (!isset($settings->type))
                throw new Error('Storage type is not defined', 500);
            // get class
            $class = 'Storage' . ucfirst($settings->type);
            // check parents
            if (!in_array('StorageInterface', class_implements($class)))
                throw new Error($class . ' must implement StorageInterface.', 500);
            // create object
            $this->_storage = new $class($settings);
        }
        return $this->_storage;
    }

    /**
     * Return the cacheable status of the resource
     * @return boolean
     */
    public function isCacheable() {
        return $this->cacheable;
    }

    /**
     * Mark the resource changed; though primarily to trigger cache
     * events, this method can also be overriden to handle transaction-
     * based processing in the storage layer.
     */
    public function changed($method = null) {
        // update cache
        if ($this->isCacheable()) {
            if (WebHttp::getMethod() == 'DELETE') {
                Cache::getInstance($this->getURI())->DELETE();
            } else {
                Cache::getInstance($this->getURI())->PUT($this);
            }
        }
        // commit transaction
        $this->getStorage()->commit();
    }
  
    /**
     * Bind values from the RESTful URL to the Resource; a common example would
     * be setting an ID
     * @example 
     * // in a ResourceItem
     * protected $id;
     * protected $route = 'resource/[id]';
     * // and a URL like
     * http://example.com/api.php/book/27/version...
     * // would set the $id property to the number 27
     * @param Route $route
     */
    public function bindFromRoute(Route $route){
        if (isset($this->route)) {
            $template = new Route($route->method, $this->route, $route->contentType);
            $values = $route->extractValuesWith($template);
            $this->bindProtected($values);
        }
    }
    
    /**
     * Bind the given properties to $this; checks if properties exist and
     * if values are valid according to the validation scheme
     * @param stdClass $object 
     */
    protected function bind($object) {
        foreach (get_public_vars($this) as $property => $value) {
            if (isset($object->$property)) {
                if ($this->validation) {
                    // todo
                }
                $this->$property = $object->$property;
            }
        }
    }

    /**
     * Bind the given properties to $this; in constrast to bind() this method
     * will bind to even protected properties, therefore it must be used with
     * caution.
     * @param stdClass|array $object
     */
    protected function bindProtected($object) {
        foreach ($object as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }


}
