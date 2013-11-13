<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a generic template for objects that are items in a list. Used in web 
 * services, specifically Service.
 * @uses Resource
 */
class ResourceItem extends Resource {

    /**
     * Stored ID for this item
     * @var type 
     */
    protected $id;

    /**
     * Constructor
     */
    public function __construct($id = null) {
        // set ID; * is a wildcard ID
        if ($id !== null && $id !== '*') {
            if(strpos($id, $this->getURI()) === 0){
                $id = substr($id, strlen($this->getURI()));
            }
            $this->setID($id);
        }
        // start transaction processing
        $this->getStorage()->begin();
    }

    /**
     * Returns this object's URI
     * @return string
     */
    public function getURI() {
        return strtolower(get_class($this)) . '/' . $this->getID();
    }

    /**
     * Returns item ID
     * @return mixed 
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Sets item ID
     * @param mixed $id 
     */
    public function setID($id) {
        $this->id = $id;
    }

    /**
     * GET a resource; retrieves item information identified by request URI
     * (RFC2616, p.53)
     * @return ResourceItem
     */
    public function GET() {
        $this->bind($this->getStorage()->read($this->getID()));
        return $this;
    }

    /**
     * POST an entity; request to accept the entity enclosed as a new 
     * subordinate (RFC2616, p.54); synonym for "create".
     * @param stdClass $entity 
     * @return mixed
     */
    public function POST($entity) {
        if ($entity === null)
            throw new Error('No item given to create', 400);
        // bind
        $this->bind($entity);
        // create
        $id = $this->getStorage()->create($this, $this->getID());
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
    public function PUT($entity = null) {
        if ($entity === null)
            throw new Error('No item given to create', 400);
        // get properties
        $public_properties = array();
        foreach (get_public_vars($this) as $property => $value) {
            $public_properties[] = $property;
        }
        // check properties
        if (is_object($entity)) {
            foreach ($entity as $property => $value) {
                if (!in_array($property, $public_properties)) {
                    unset($entity->$property);
                }
            }
        }
        // update
        $this->bind($this->getStorage()->update($entity, $this->getID()));
        // mark changed
        $this->changed();
        // return
        return $this;
    }

    /**
     * DELETE a resource; request to delete the resource identified by 
     * the request URI (RFC2616, p.55)
     * @return Resource
     */
    public function DELETE() {
        // load the resource, then delete it
        $this->bind($this->getStorage()->delete($this->getID()));
        // mark changed
        $this->changed();
        // return
        return $this;
    }

    /**
     * Returns a blank message if the resource exists (HTTP code 200) or 
     * an exception (HTTP code 404) if it does not; similar to "exists".
     * @return null 
     */
    public function HEAD() {
        if (!$this->getStorage()->exists($this->getID())) {
            throw new Error($this->getUri() . " does not exist.", 404);
        }
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

}
