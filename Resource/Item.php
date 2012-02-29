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
    private $id;

    /**
     * Stores the storage method
     * @var StorageInterface
     */
    private $storage;

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
     * Returns storage
     * @return StorageInterface
     */
    public function getStorage() {
        return $this->storage;
    }

    /**
     * Sets storage
     * @param StorageInterface $storage 
     */
    public function setStorage($storage) {
        $this->storage = $storage;
    }

    /**
     * Returns item ID
     * @return mixed 
     */
    protected function getID() {
        return $this->id;
    }

    /**
     * Sets item ID
     * @param mixed $id 
     */
    protected function setID($id) {
        $this->id = $id;
    }

    /**
     * Creates an item
     * @param mixed $item
     * @return mixed 
     */
    public function create($item = null) {
        if ($item === null)
            throw new ExceptionService('No item given to create', 400);
        $this->getStorage()->begin();
        $id = $this->getStorage()->create($item, $this->getID());
        $this->getStorage()->commit();
        return $id;
    }

    /**
     * Reads an item
     * @param mixed $id
     * @return mixed 
     */
    public function read() {
        $this->getStorage()->begin();
        $item = $this->getStorage()->read($this->getID());
        $this->getStorage()->commit();
        return $item;
    }

    /**
     * Updates an item
     * @param mixed $item
     * @param mixed $id
     * @return mixed 
     */
    public function update($item = null) {
        if ($item === null)
            throw new ExceptionService('No item given to create', 400);
        $this->getStorage()->begin();
        $item = $this->getStorage()->update($item, $this->getID());
        $this->getStorage()->commit();
        return $item;
    }

    /**
     * Deletes an item
     * @param mixed $id
     * @return mixed 
     */
    public function delete() {
        $this->getStorage()->begin();
        $item = $this->getStorage()->delete($this->getID());
        $this->getStorage()->commit();
        return $item;
    }

    /**
     * Determines whether an item exists
     * @param mixed $id
     * @return boolean 
     */
    public function exists() {
        $this->getStorage()->begin();
        $exists = $this->getStorage()->exists($this->getID());
        $this->getStorage()->commit();
        return $exists;
    }

    /**
     * Returns editable fields for an item
     * @return mixed
     */
    public function fields() {
        $this->getStorage()->begin();
        $first = $this->getStorage()->first();
        $this->getStorage()->commit();
        // extract
        $properties = array();
        foreach ($first as $property => $value) {
            $properties[] = $property;
        }
        // return
        return $properties;
    }

}
