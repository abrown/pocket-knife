<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
define('CACHE_LOCATION', get_base_dir() . DS . 'cache');

/**
 * Caches resources using a prioritized list: APC, memcache, and
 * the file system.
 * @uses Resource, Error, StorageFile
 */
class Cache extends Resource {

    public $uri;
    public $version;
    public $modified;
    public $resource;
    public $resource_type;
    protected $storage = array('type' => 'file', 'format' => 'json', 'location' => CACHE_LOCATION);

    /**
     * Constructor
     */
    public function __construct($uri = null) {
        // set ID; * is a wildcard ID
        if ($uri !== null) {
            $this->setID($uri);
        }
        // start transaction processing
        $this->getStorage()->begin();
    }

    /**
     * Allow static access to the cache
     * @staticvar null $instance
     * @return Cache
     */
    static public function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new Cache();
        }
        return $instance;
    }

    /**
     * Returns the cache's target URI
     * @return string
     */
    public function getURI() {
        return $this->uri;
    }

    /**
     * Set the cache target URI
     * @param string $uri
     */
    public function setURI($uri) {
        $this->uri = $uri;
    }

    /**
     * Mark the resource changed; saves changes to the cache
     * storage object.
     */
    public function changed() {
        // commit transaction
        $this->getStorage()->commit();
    }

    /**
     * GET the cached resource
     * @return Resource
     */
    public function GET($uri = null) {
        if ($uri === null) {
            if ($this->getURI() === null) {
                throw new Error('No URI given to retrieve from cache.', 400);
            }
        } else {
            $this->setURI($uri);
        }
        // retrieve from cache
        $this->bind($this->getStorage()->read($this->getURI()));
        // cast resource
        $temp = $this->resource;
        $this->resource = new $this->resource_type;
        $this->resource->bind($temp);
        // return
        return $this->resource;
    }

    /**
     * Create a Resource in the cache
     * @param stdClass $resource 
     * @return mixed
     */
    public function POST($resource) {
        if ($resource === null || !is_a($resource, 'Resource')) {
            throw new Error('No resource given to update', 400);
        }
        // set ID
        $this->setURI($resource->getURI());
        // set properties
        $this->version = 1;
        $this->modified = microtime(true);
        $this->resource = $resource;
        $this->resource_type = get_class($resource);
        // create
        $id = $this->getStorage()->create($this, $this->getURI());
        // mark changed
        $this->changed();
        // return
        return $id;
    }

    /**
     * PUT an entity; requests that the enclosed entity be stored under 
     * the supplied request URI (RFC2616, p.54); does not bind the properties 
     * to this object and rejects non-public properties; synonym for "update"
     * @param stdClass $entity 
     */
    public function PUT($resource = null) {
        if ($resource === null || !is_a($resource, 'Resource')) {
            throw new Error('No resource given to update', 400);
        }
        // set ID
        $this->setURI($resource->getURI());
        // read
        if ($this->HEAD()) {
            $this->GET();
        }
        // set properties
        $this->version = $this->version + 1;
        $this->modified = microtime(true);
        $this->resource = $resource;
        $this->resource_type = get_class($resource);
        // update
        $this->bind($this->getStorage()->update($this, $this->getURI()));
        // mark changed
        $this->changed();
        // return
        return $this->resource;
    }

    /**
     * DELETE a cached resource
     * @return Resource
     */
    public function DELETE($uri = null) {
        if ($uri === null) {
            if ($this->getURI() === null) {
                throw new Error('No URI given to retrieve from cache.', 400);
            }
        } else {
            $this->setURI($uri);
        }
        // load the resource, then delete it
        $this->bind($this->getStorage()->delete($this->getURI()));
        // mark changed
        $this->changed();
        // return
        return $this->resource;
    }

    /**
     * Returns a boolean message informing the client whether
     * the Resource is cached or not.
     * @return boolean 
     */
    public function HEAD() {
        return $this->getStorage()->exists($this->getURI());
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

    /**
     * Build Etag for this resource; the Etag consists of an MD5
     * hash of "[uri]:[last modified time]:[modification number]";
     * the addition of the modification number makes this a strong
     * Etag.
     * @return string
     */
    public function getEtag() {
        return md5("{$this->getURI()}:{$this->modified}:{$this->version}");
    }

    /**
     * Return the RFC2822 time the resource was last changed.
     * @return string
     */
    public function getLastModified($uri) {
        return date('r', (int) $this->modified);
    }

    /**
     * Determine whether the client has a current copy of the cached
     * Resource based on the IfNoneMatch and LastModified HTTP headers. 
     * @return boolean
     */
    public function isClientCurrent() {
        // check etag
        $etag = self::getEtag($uri);
        if (WebHttp::getIfNoneMatch() && WebHttp::getIfNoneMatch() == $etag) {
            return false;
        }
        // check last-modified time
        if (WebHttp::getIfModifiedSince() && WebHttp::getIfModifiedSince() >= (int) $this->modified) {
            return false;
        }
        // else
        return true;
    }

}
