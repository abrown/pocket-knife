<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a generic template for objects that are lists. Used in web 
 * services, specifically Service.
 * @uses ResourceInterface
 */
class ResourceList implements ResourceInterface{
    
    /**
     * Constructor
     */
    public function __construct(){

    }
    
    /**
     * Returns this object's URI
     * @return string
     */
    public function getURI(){
        return '/'.strtolower(get_class($this));
    }
    
    /**
     * Returns storage
     * @return StorageInterface
     */
    public function getStorage(){
        return $this->storage;
    }
    
    /**
     * Sets storage
     * @param StorageInterface $storage 
     */
    public function setStorage($storage){
        $this->storage = $storage;
    }
    
    /**
     * Inserts a list of items into the stored list
     * @param array $list
     * @return array IDs 
     */
    public function create($list){
        if( !is_array($list)  ) throw new ExceptionService('CREATE requires a list', 400);
        $this->getStorage()->begin();
        $ids = array();
        foreach($list as $item){
            $ids[] = $this->getStorage()->create($item);
        }
        $this->getStorage()->commit();
        return $ids;
    }
    
    /**
     * Reads all items in list
     * @return array 
     */
    public function read(){
        $this->getStorage()->begin();
        $items = $this->getStorage()->all();
        $this->getStorage()->commit();
        return $items;
    }
    
    /**
     * Updates all records named in the given list
     * @param array list
     * @return array IDs
     */
    public function update($list){
        if( !is_array($list)  ) throw new ExceptionService('CREATE requires a list', 400);
        $this->getStorage()->begin();
        $ids = array();
        foreach($list as $id => $item){
            $ids[] = $this->getStorage()->update($item, $id);
        }
        $this->getStorage()->commit();
        return $ids;
    }
    
    /**
     * Deletes all items in a list
     * @return boolean 
     */
    public function delete(){
        $this->getStorage()->begin();
        $success = $this->getStorage()->deleteAll();
        $this->getStorage()->commit();
        return $success;
    }
    
    /**
     * Returns item count in list
     * @return int
     */
    public function count(){
        $this->getStorage()->begin();
        $count = $this->getStorage()->count();
        $this->getStorage()->commit();
        return $count;
    }
}
