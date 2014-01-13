<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
define('CACHE_LOCATION', get_base_dir() . DS . 'writable' . DS . 'cache');

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
        $this->setURI($uri);
        // start transaction processing
        $this->getStorage()->begin();
    }

    /**
     * Test if the client has a current copy of the requested resource; this
     * method uses HTTP ETags combined with the HTTP If-None-Match and
     * If-Modified-Since headers to determine the client's response;
     * @param Route $route
     * @return boolean
     */
    public function isCurrent(Route $route) {
        // check etag
        $etag = self::getEtag($route->uri);
        if (WebHttp::getIfNoneMatch() && WebHttp::getIfNoneMatch() == $etag) {
            return true;
        }
        // check last-modified time
        if (WebHttp::getIfModifiedSince() && WebHttp::getIfModifiedSince() == (int) $this->modified) {
            return true;
        }
        // else
        return false;
    }
    
//        if ($route->method == 'GET' && $this->HEAD($route)) {
//            $cache->GET();
//            // if client has a current resource, use that
//            if ($cache->isClientCurrent()) {
//                header('HTTP/1.1 304 Not Modified');
//                exit();
//            }
//            // otherwise, use the cached copy
//            else {
//                $representation = new Representation($cache->resource, $this->accept);
//                $representation = $this->executeOutputTriggers($cache->resource, $this->action, $representation);
//                // send headers
//                header('Etag: "' . $cache->getEtag() . '"');
//                header('Last-Modified: ' . $cache->getLastModified());
//                // send resource
//                if ($return_as_string) {
//                    return (string) $representation;
//                } else {
//                    $representation->send();
//                    return;
//                }
//            }
//        }
//    }

    /**
     * Allow static access to the cache; every use of this should include a URI.
     * @param $uri the URI in the cache to interact with
     * @staticvar Cache $instance
     * @return Cache
     */
    static public function getInstance($uri) {
        static $instance = null;
        if ($instance === null) {
            $instance = new Cache();
        }
        // set URI
        $instance->setURI($uri);
        // return
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
     * storage object. Overrides changed() in Resource, since that mostly deals
     * with caching.
     */
    public function changed($method = null) {
        // commit transaction
        $this->getStorage()->commit();
    }

    /**
     * Return a boolean message showing whether the given route is cached or
     * not. Requires input.
     * @param mixed a Route or a string URI
     * @return boolean 
     */
    public function HEAD($routeOrUri = null) {
        if($routeOrUri ===  null){
            throw new Error('Cache HEAD must receive a URI string or Route.', 400); 
        }
        if($routeOrUri instanceof Route){
            return $this->getStorage()->exists($routeOrUri->uri);
        }
        return $this->getStorage()->exists($routeOrUri);
    }
    
    /**
     * Proxy method for HEAD; easier to read.
     * @param string $routeOrUri
     */
    public function isCached($routeOrUri = null){
        return $this->HEAD($routeOrUri);
    }
    

    /**
     * GET the cached resource; loads an instance of the resource into the 
     * 'resource' property of Cache.
     * @return Resource
     */
    public function GET() {
        // retrieve the cached resource, if it exists
        if ($this->HEAD($this->getURI())) {
            // retrieve from cache
            $this->bind($this->getStorage()->read($this->getURI()));
            // cast resource
            $temp = $this->resource;
            $this->resource = new $this->resource_type;
            $this->resource->bind($temp);
            // if necessary, set ID
            if ($this->resource instanceof ResourceItem) {
                $pos = strpos($this->getURI(), '/');
                if ($pos !== false) {
                    $id = substr($this->getURI(), $pos + 1);
                    if (is_numeric($id))
                        $id = (int) $id;
                    $this->resource->setID($id);
                }
            }
        }
        // return
        return $this->resource;
    }

    /**
     * Create a Resource in the cache
     * @param stdClass $resource 
     * @return mixed
     */
    public function POST($resource) {
        if ($resource === null || !($resource instanceof Resource)) {
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
        if ($resource === null || !($resource instanceof Resource)) {
            return null;
        }
        $already_cached = $this->isCached($resource->getURI());
        // set ID
        $this->setURI($resource->getURI());
        // read
        if ($this->isCached($resource->getURI())) {
            $this->GET();
        }
        // set properties
        $this->version = $this->version + 1;
        $this->modified = microtime(true);
        $this->resource = $resource;
        $this->resource_type = get_class($resource);
        // update/create
        if ($already_cached) {
            $this->bind($this->getStorage()->update($this, $this->getURI()));
        } else {
            $this->bind($this->getStorage()->create($this, $this->getURI()));
        }
        // mark changed
        $this->changed();
        // return
        return $this->resource;
    }

    /**
     * DELETE a cached resource
     * @return Resource
     */
    public function DELETE() {
        // delete the cached resource, if it exists
        if ($this->HEAD($this->getURI())) {
            // load the resource, then delete it
            $this->bind($this->getStorage()->delete($this->getURI()));
            // mark changed
            $this->changed();
        }
        // return
        return $this->resource;
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
    public function getLastModified() {
        return date('r', (int) $this->modified);
    }

    /**
     * Determine whether the client has a current copy of the cached
     * Resource based on the IfNoneMatch and LastModified HTTP headers. 
     * @return boolean
     */
    public function isClientCurrent() {
        // check etag
        $etag = self::getEtag($this->getURI());
        if (WebHttp::getIfNoneMatch() && WebHttp::getIfNoneMatch() == $etag) {
            return true;
        }
        // check last-modified time
        if (WebHttp::getIfModifiedSince() && WebHttp::getIfModifiedSince() == (int) $this->modified) {
            return true;
        }
        // else
        return false;
    }

    /**
     * Clear the entire cache
     */
    static public function clearAll() {
        $cache = self::getInstance();
        $cache->getStorage()->deleteAll();
        $cache->changed();
    }

}
