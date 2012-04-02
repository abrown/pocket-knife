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
 * @uses ExceptionSettings, ResourceGeneric, ResourceItem, ResourceList
 */
abstract class Resource {

    /**
     * Defines storage method for the resource; see classes in Storage for specific parameters required
     * @example $this->storage = array('type'=>'mysql', 'username'=>'test', 'password'=>'password', 'location'=>'localhost', 'database'=>'db');
     * @var array
     */
    protected $storage = array('type' => 'json', 'location' => 'db.json');

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
    protected $template;
    
    /**
     * Validation settings...
     * @var array 
     */
    protected $validation = false;

    /**
     * Returns the object URI
     */
    public abstract function getURI();
    
    /**
     * Returns the request object given a content type;
     * should be overloaded in descendant classes to 
     * accommodate content type differences and possible 
     * resource-to-data binding
     * @param string $content_type
     * @throws Error
     * @return stdClass
     */
    public function fromRepresentation($content_type) {
        if (!array_key_exists($content_type, Representation::$MAP))
            throw new Error('Unknown request content-type.', 500);
        $class = Representation::$MAP[$content_type];
        $representation = new $class;
        $representation->receive();
        return $representation;
    }

    /**
     * Returns a representation of the resource given a content type 
     * and data; should be overloaded in descendant classes to 
     * accommodate content type differences and possible 
     * resource-to-data binding
     * @param string $content_type
     * @throws Error
     * @return Representation
     */
    public function toRepresentation($content_type, $data) {
        if (!array_key_exists($content_type, Representation::$MAP))
            throw new Error('Unknown request content-type.', 500);
        $class = Representation::$MAP[$content_type];
        $representation = new $class;
        // templating
        if ($this->template && method_exists($representation, 'setTemplate')) {
            $template = new WebTemplate($this->template[0], $this->template[1]);
            $representation->setTemplate($template);
        }
        // set data
        $representation->setData($data);
        // return
        return $representation;
    }

    /**
     * Initializes the storage object
     * @param Settings $settings
     */
    public abstract function setStorage($settings);

    /**
     * Returns the storage object
     */
    public abstract function getStorage();
    
    /**
     * Binds the given properties to $this; checks if properties exist and
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
