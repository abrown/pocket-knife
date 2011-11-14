<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class ServiceObjectList implements ServiceObjectInterface{
    
    /**
     * Constructor
     */
    public function __construct(){
        $tokens = WebRouting::getTokens();
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
     * Deletes all items in a list
     * @return boolean 
     */
    public function delete(){
        $this->getStorage()->begin();
        $success = $this->getStorage()->deleteAll();
        $this->getStorage()->commit();
        return $success;
    }
}
