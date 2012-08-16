<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a generic template for an object that is neither a 
 * list nor an item in a list, but must be accessed by 
 * a web service. Remember to mark the resource as changed (with
 * $this->changed()) so caching will update.
 * @uses Resource
 */
class ResourceGeneric extends Resource {

    /**
     * Returns the object URI
     * @return string
     */
    public function getURI() {
        return strtolower(get_class($this));
    }

    /**
     * Initializes the storage object; uses class variables to store data
     * @return this
     */
    public function setStorage($settings) {
        return $this;
    }

    /**
     * Returns the storage object; uses class variables to store data
     * @return this
     */
    public function getStorage() {
        return $this;
    }

    /**
     * GET a resource; retrieves information identified by request URI
     * (RFC2616, p.53)
     * @return ResourceGeneric 
     */
    public function GET() {
        return $this;
    }

}
