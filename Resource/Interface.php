<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ResourceInterface
 */
interface ResourceInterface{
    
    /**
     * Returns the object URI
     */
    public function getURI();
       
    /**
     * Initializes the storage object
     */
    public function setStorage($Settings);
    
    /**
     * Returns the storage object
     */
    public function getStorage();

}
