<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a generic template for objects that are lists. Used in web 
 * services, specifically Service.
 * @uses Resource, ResourceItem, Settings, WebHttp
 */
class ResourceList extends Resource {

    /**
     * Contains a list of ResourceItems corresponding to this ResourceList
     * @var array
     */
    public $items = array();

    /**
     * Class of item to create in $items
     * @var string 
     */
    protected $item_type = 'ResourceItem';
    
    /**
     * 
     * @var StorageInterface 
     */
    protected $storage;

    /**
     * Constructor
     */
    public function __construct() {
        // start transaction processing
        $this->getStorage()->begin();
    }

    /**
     * Returns this object's URI
     * @return string
     */
    public function getURI() {
        return strtolower(get_class($this));
    }
    
    /**
     * Returns the list's item type
     * @return ResourceItem
     */
    public function getItemType(){
        return $this->item_type;
    }

    /**
     * GET a list of resources; retrieves item information identified 
     * by request URI (RFC2616, p.53).
     * @return ResourceList
     */
    public function GET() {
        // get filtered resources
        if (WebHttp::getParameter('filter_on')) {
            $key = (string) WebHttp::getParameter('filter_on');
            $value = (string) WebHttp::getParameter('filter_with');
            $resources = $this->getStorage()->search($key, $value);
        }
        // get paged resources
        else if (WebHttp::getParameter('page')) {
            $page_size = (int) WebHttp::getParameter('page_size');
            if( $page_size < 1 ){
                throw new Error("Page size is outside of allowed range.", 416);
            }
            if ($page_size === null ) {
                $page_size = 20;
            }
            $page = (int) WebHttp::getParameter('page');
            if ($page < 1) {
                throw new Error("Page number is outside of allowed range.", 416);
            }
            $resources = $this->getStorage()->all($page_size, $page);
        }
        // get all resource
        else {
            $resources = $this->getStorage()->all(); // @TODO: make this an Iterator
        }
        // bind
        $this->items = array();
        foreach ($resources as $id => $data) {
            $item = new $this->item_type($id);
            $item->bind($data);
            $this->items[$id] = $item;
        }
        // return
        return $this;
    }

    /**
     * POST a list of entities; request to accept the list enclosed as a new 
     * subordinate (RFC2616, p.54); synonym for "create".
     * @param stdClass $list with "items" property as array of entities
     * @return array list of IDs created
     */
    public function POST($list) {
        if (!isset($list->items)) {
            throw new Error('POST "items" field must be set', 400);
        }
        if (!is_array($list->items)) {
            throw new Error('POST requires list items', 400);
        }
        // create
        $ids = array();
        foreach ($list as $item) {
            $ids[] = $this->getStorage()->create($item);
        }
        // mark changed
        $this->changed();
        // return
        return $ids;
    }

    /**
     * PUT a list; requests that the enclosed entity be stored under 
     * the supplied request URI (RFC2616, p.54); does not bind the properties 
     * to this object and rejects non-public properties; synonym for "update"
     * @param stdClass $list
     * @return array list of IDs updated
     */
    public function PUT($list = null) {
        if (!isset($list->items)) {
            throw new Error('PUT "items" field must be set', 400);
        }
        if (!is_array($list->items)) {
            throw new Error('PUT requires list items', 400);
        }
        // update
        $ids = array();
        foreach ($list as $id => $item) {
            $ids[] = $this->getStorage()->update($item, $id);
        }
        // mark changed
        $this->changed();
        // return
        return $ids;
    }

    /**
     * DELETE a resource; request to delete the resource identified by 
     * the request URI (RFC2616, p.55)
     * @return boolean whether the list was successfully deleted
     */
    public function DELETE() {
        $success = $this->getStorage()->deleteAll();
        // mark changed
        $this->changed();
        // return
        return $success;
    }

    /**
     * Returns an almost blank message if the resource exists (HTTP code 200) 
     * or an exception (HTTP code 404) if it does not; similar to "count".
     * @return int 
     */
    public function HEAD() {
        $count = $this->getStorage()->count();
        return $count;
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
