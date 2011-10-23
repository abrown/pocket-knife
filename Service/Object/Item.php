<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ServiceObjectItem
 * @uses ServiceObjectInterface
 */
class ServiceObjectItem implements ServiceObjectInterface{
    
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
    public function __construct(){
        $tokens = WebRouting::getTokens();
        if( isset($tokens[2]) ) $this->setID($tokens[2]);
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
     * Returns item ID
     * @return mixed 
     */
    protected function getID(){
        return $this->id;
    }
    
    /**
     * Sets item ID
     * @param mixed $id 
     */
    protected function setID($id){
        $this->id = $id;
    }
    
    /**
     * Creates an item
     * @param mixed $item
     * @return mixed 
     */
    public function create($item){
        $this->getStorage()->begin();
        $id = $this->getStorage()->create($item);
        $this->getStorage()->commit();
        return $id;
    }
    
    /**
     * Reads an item
     * @param mixed $id
     * @return mixed 
     */
    public function read($id){
        $this->getStorage()->begin();
        $item = $this->getStorage()->read($id);
        $this->getStorage()->commit();
        return $item;
    }
    
    /**
     * Updates an item
     * @param mixed $item
     * @param mixed $id
     * @return mixed 
     */
    public function update($item, $id){
        $this->getStorage()->begin();
        $item = $this->getStorage()->update($item, $id);
        $this->getStorage()->commit();
        return $item;
    }
    
    /**
     * Deletes an item
     * @param mixed $id
     * @return mixed 
     */
    public function delete($id){
        $this->getStorage()->begin();
        $item = $this->getStorage()->delete($id);
        $this->getStorage()->commit();
        return $item;
    }
}
