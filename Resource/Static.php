<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/* 
 * ResourceStatic: 
 * @uses ResourceInterface
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
    public function setStorage($Settings){
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
