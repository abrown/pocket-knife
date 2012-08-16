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
 * @uses Error
 */
abstract class Resource {

    /**
     * Defines storage method for the resource; see classes in Storage for specific parameters required
     * @example $this->storage = array('type'=>'mysql', 'username'=>'test', 'password'=>'password', 'location'=>'localhost', 'database'=>'db');
     * @var array
     */
    protected $storage = array('type' => 'json', 'location' => 'db.json');

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
     * Initialize the storage object
     * @param Settings $settings
     */
    public abstract function setStorage($settings);

    /**
     * Return the storage object
     */
    public abstract function getStorage();
    
    /**
     * Return the cacheable status of the resource
     * @return boolean
     */
    public function isCacheable(){
        return $this->cacheable;
    }
    
    /**
     * Mark the resource changed; though primarily to trigger cache
     * events, this method can also be overriden to handle transaction-
     * based processing in the storage layer.
     */
    public function changed(){
        if( $this->isCacheable()){
            StorageCache::markModified($this->getURI());
        }
    }
    
    /**
     * Bind the given properties to $this; checks if properties exist and
     * if values are valid according to the validation scheme
     * @param stdClass $object 
     */
    protected function bind($object){
        foreach(get_public_vars($this) as $property => $value){
            if( isset($object->$property) ){
                if( $this->validation ){
                    // todo
                }
                $this->$property = $object->$property;
            }
        }
    }
}
