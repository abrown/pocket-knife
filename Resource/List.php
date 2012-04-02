<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a generic template for objects that are lists. Used in web 
 * services, specifically Service.
 * @uses Resource
 */
class ResourceList extends Resource {

    /**
     * Contains a list of ResourceItems corresponding to this ResourceList
     * @var array
     */
    public $items = array();

    /**
     * Class of item to create in $items
     * @var string 
     */
    protected $item_type = 'ResourceItem';

    /**
     * Constructor
     */
    public function __construct() {
        
    }

    /**
     * Returns this object's URI
     * @return string
     */
    public function getURI() {
        return '/' . strtolower(get_class($this));
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
     * Sets storage configuration
     * @param Settings $settings 
     */
    public function setStorage($settings) {
        $this->storage = $settings;
    }

    /**
     * GET a list of resources; retrieves item information identified 
     * by request URI (RFC2616, p.53).
     * @return ResourceList
     */
    public function GET() {
        $this->getStorage()->begin();
        foreach ($this->getStorage()->all() as $id => $data) {
            $item = new $this->item_type();
            $item->bind($data);
            $this->items[$id] = $item;
        }
        $this->getStorage()->commit();
        return $this;
    }

    /**
     * POST a list of entities; request to accept the list enclosed as a new 
     * subordinate (RFC2616, p.54); synonym for "create".
     * @param stdClass $list with "items" property as array of entities
     * @return array list of IDs created
     */
    public function POST($list) {
        if (!isset($list->items)) {
            throw new Error('POST "items" field must be set', 400);
        }
        if (!is_array($list->items)) {
            throw new Error('POST requires list items', 400);
        }
        // create
        $this->getStorage()->begin();
        $ids = array();
        foreach ($list as $item) {
            $ids[] = $this->getStorage()->create($item);
        }
        $this->getStorage()->commit();
        // return
        return $ids;
    }

    /**
     * PUT a list; requests that the enclosed entity be stored under 
     * the supplied request URI (RFC2616, p.54); does not bind the properties 
     * to this object and rejects non-public properties; synonym for "update"
     * @param stdClass $list
     * @return array list of IDs updated
     */
    public function PUT($list = null) {
        if (!isset($list->items)) {
            throw new Error('PUT "items" field must be set', 400);
        }
        if (!is_array($list->items)) {
            throw new Error('PUT requires list items', 400);
        }
        // update
        $this->getStorage()->begin();
        $ids = array();
        foreach ($list as $id => $item) {
            $ids[] = $this->getStorage()->update($item, $id);
        }
        $this->getStorage()->commit();
        return $ids;
    }

    /**
     * DELETE a resource; request to delete the resource identified by 
     * the request URI (RFC2616, p.55)
     * @return boolean whether the list was successfully deleted
     */
    public function DELETE() {
        $this->getStorage()->begin();
        $success = $this->getStorage()->deleteAll();
        $this->getStorage()->commit();
        return $success;
    }

    /**
     * Returns an almost blank message if the resource exists (HTTP code 200) 
     * or an exception (HTTP code 404) if it does not; similar to "count".
     * @return int 
     */
    public function HEAD() {
        $this->getStorage()->begin();
        $count = $this->getStorage()->count();
        $this->getStorage()->commit();
        return $count;
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
