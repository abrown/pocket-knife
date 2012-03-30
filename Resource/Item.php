<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a generic template for objects that are items in a list. Used in web 
 * services, specifically Service.
 * @uses Resource
 */
class ResourceItem extends Resource {

    /**
     * Stored ID for this item
     * @var type 
     */
    protected $id;

    /**
     * Stores the storage method
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Constructor
     */
    public function __construct($id = null) {
        if ($id !== null && $id !== '*')
            $this->setID($id);
    }

    /**
     * Returns this object's URI
     * @return string
     */
    public function getURI() {
        return '/' . strtolower(get_class($this)) . '/' . $this->getID();
    }

    /**
     * Creates and returns storage object
     * @return StorageInterface
     */
    public function getStorage() {
        static $storage = null;
        if (!$storage) {
            $settings = new Settings($this->storage);
            // check Settings
            if (!isset($settings->type))
                throw new ExceptionSettings('Storage type is not defined', 500);
            // get class
            $class = 'Storage' . ucfirst($settings->type);
            // check parents
            if (!in_array('StorageInterface', class_implements($class)))
                throw new ExceptionSettings($class . ' must implement StorageInterface.', 500);
            // create object
            $storage = new $class($settings);
        }
        return $storage;
    }

    /**
     * Sets storage configuration
     * @param Settings $settings 
     */
    public function setStorage($settings) {
        $this->storage = $settings;
    }

    /**
     * Returns item ID
     * @return mixed 
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Sets item ID
     * @param mixed $id 
     */
    public function setID($id) {
        $this->id = $id;
    }

    /**
     * GET a resource; retrieves item information identified by request URI
     * (RFC2616, p.53)
     * @return ResourceItem
     */
    public function GET() {
        $this->getStorage()->begin();
        $this->bind($this->getStorage()->read($this->getID()));
        $this->getStorage()->commit();
        return $this;
    }

    /**
     * POST an entity; request to accept the entity enclosed as a new 
     * subordinate (RFC2616, p.54); synonym for "create".
     * @param stdClass $entity 
     * @return mixed
     */
    public function POST($entity) {
        if ($entity === null)
            throw new ExceptionService('No item given to create', 400);
        // bind
        $this->bind($entity);
        // create
        $this->getStorage()->begin();
        $id = $this->getStorage()->create($this, $this->getID());
        $this->getStorage()->commit();
        return $id;
    }

    /**
     * PUT an entity; requests that the enclosed entity be stored under 
     * the supplied request URI (RFC2616, p.54); does not bind the properties 
     * to this object and rejects non-public properties; synonym for "update"
     * @param stdClass $entity 
     */
    public function PUT($entity = null) {
        if ($entity === null)
            throw new ExceptionService('No item given to create', 400);
        // get properties
        $public_properties = array();
        foreach (get_public_properties($this) as $property => $value) {
            $public_properties[] = $property;
        }
        // check properties
        if (is_object($entity)) {
            foreach ($entity as $property => $value) {
                if (!in_array($property, $public_properties)) {
                    unset($entity->$property);
                }
            }
        }
        // update
        $this->getStorage()->begin();
        $this->bind($this->getStorage()->update($entity, $this->getID()));
        $this->getStorage()->commit();
        return $this;
    }

    /**
     * DELETE a resource; request to delete the resource identified by 
     * the request URI (RFC2616, p.55)
     * @return Resource
     */
    public function DELETE() {
        $this->getStorage()->begin();
        $this->bind($this->getStorage()->delete($this->getID()));
        $this->getStorage()->commit();
        return $this;
    }

    /**
     * Returns a blank message if the resource exists (HTTP code 200) or 
     * an exception (HTTP code 404) if it does not; similar to "exists".
     * @return null 
     */
    public function HEAD() {
        $this->getStorage()->begin();
        if (!$this->getStorage()->exists($this->getID())) {
            throw new ExceptionService($this->getUri() . " does not exist.", 404);
        }
        $this->getStorage()->commit();
    }

    /**
     * Returns an object describing all upper-case methods (i.e. HTTP verbs)
     * defined in the class and all public properties.
     * @return stdClass 
     */
    public function OPTIONS() {
        $response = new stdClass();
        $response->methods = array();
        $response->properties = array();
        // get methods
        foreach (get_class_methods($this) as $method) {
            if (ctype_upper($method)) {
                $response->methods[] = $method;
            }
        }
        // get properties
        foreach (get_public_vars($this) as $property => $value) {
            $response->properties[] = $property;
        }
        // return
        return $response;
    }

}
