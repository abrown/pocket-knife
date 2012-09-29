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
     * Constructor
     */
    public function __construct() {
        // start transaction processing
        $this->getStorage()->begin();
    }

    /**
     * Returns the object URI
     * @return string
     */
    public function getURI() {
        return strtolower(get_class($this));
    }

    /**
     * Mark the resource changed; updates the cache and commits
     * to storage. Must be called only after all storage 
     * modifications are complete.
     */
    public function changed() {
        // update cache
        if ($this->isCacheable()) {
            Cache::getInstance()->PUT($this);
        }
        // commit transaction
        $this->getStorage()->commit();
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
