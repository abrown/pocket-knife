<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/** 
 * Provides a generic template for an object that is neither a list nor an item
 * in a list, but must be accessed by a web service. Does not implement ResourceInterface
 */
class ResourceStatic{
    
    /**
     * Constructor
     */
    public function __construct(){
        $properties = get_public_vars($this);
        foreach($properties as $property => $value){
            if( array_key_exists($property, $_GET) ){
                $this->$property = $_GET[$property];
            }
        }
    }
    
    /**
     * Returns the object URI
     * @return string
     */
    public function getURI(){
        return '/'.strtolower(get_class($this));
    }
       
    /**
     * Initializes the storage object; uses class variables to store data
     * @return this
     */
    public function setStorage($settings){
        return $this;
    }
    
    /**
     * Returns the storage object; uses class variables to store data
     * @return this
     */
    public function getStorage(){
        return $this;
    }
}
