<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * A resource tree accommodates a hierarchy of resources: like ResourceLists,
 * each resource is identified by a type and an ID. Trees are formed by creating
 * classes extending ResourceTree and editing the $allowed_children field with
 * a list of types allowed as children. These classes are accessed with URLs
 * like "index.php/level1/35/level2/xyz/level3/92". See the ResourceTree test
 * file for further details.
 * @uses Resource, WebUrl, Error
 */
class ResourceTree extends Resource {

    /**
     * Resource ID
     * @var mixed 
     */
    protected $id;

    /**
     * Parent
     * @var ResourceTree 
     */
    protected $parent;

    /**
     * Child
     * @var ResourceTree 
     */
    protected $child;

    /**
     * List of allowed children; also, set true to allow all, or false to deny
     * all
     * @var mixed 
     */
    protected $allowed_children = true;

    /**
     * Storage settings
     * @var type 
     */
    protected $storage;

    /**
     * Constructor; takes a URL like '/level1/32/level2/...' and creates the
     * hierarchy of resources automatically
     */
    public function __construct($in_recursion = false) {
        // get tokens
        static $tokens;
        if ($in_recursion === false) {
            $tokens = WebUrl::getTokens();
        }
        // pattern: /[resource]/[id]/[resource]/[id]/...
        $_resource = array_shift($tokens);
        $_id = array_shift($tokens);
        // create child
        if ($_resource) {
            $this->child = new $_resource(true);
            $this->child->setParent($this);
            if ($_id) {
                $this->child->setID($_id);
            }
        }
        // begin transaction processing
        $this->getStorage()->begin();
    }

    /**
     * Return this child's parent
     * @return type 
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set this child's parent
     * @param type $parent
     * @throws Error 
     */
    public function setParent(&$parent) {
        if (!($parent instanceof ResourceTree)) {
            throw new Error('Resource is not a tree.', 400);
        }
        $this->parent = &$parent;
    }

    /**
     * Return child
     * @return type 
     */
    public function getChild() {
        return $this->child;
    }

    /**
     * Set the next child (branch) in the hierarchy
     * @param type $child
     * @throws Error 
     */
    public function setChild(&$child) {
        if (!($child instanceof ResourceTree)) {
            throw new Error('Resource is not a tree.', 400);
        }
        // check if allowed
        $child_name = get_class($child);
        if (!$this->isAllowedChild($child_name)) {
            throw new Error("Resource '$child_name' is not allowed as a child.", 400);
        }
        // set
        $this->child = &$child;
    }

    /**
     * Return the last child (leaf) in the hierarchy
     * @return ResourceTree 
     */
    public function getLastChild() {
        if ($this->child === null) {
            return $this;
        } else {
            return $this->child->getLastChild();
        }
    }

    /**
     * Determine whether the given class name can be added as a child
     * @param string $child
     * @return boolean 
     */
    public function isAllowedChild($child) {
        if ($this->allowed_children === true) {
            return true;
        } else if ($this->allowed_children === false) {
            return false;
        } else if (in_array($child, $this->allowed_children)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return number of layers beneath the current one
     * @param int $count
     * @return int 
     */
    public function getDepth($count = 0) {
        if ($this->child === null) {
            return $count;
        } else {
            $count++;
            return $this->child->getDepth($count);
        }
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
     * Returns the object URI
     * @return string
     */
    public function getURI() {
        $uri = get_class($this) . '/';
        $uri .= ($this->getID() === null ) ? '*' : $this->getID();
        if ($this->child) {
            $uri .= $this->getChild()->getURI();
        }
        return $uri;
    }

    /**
     * GET a resource; retrieves information identified by request URI
     * (RFC2616, p.53)
     * @return ResourceTree
     */
    public function GET() {
        // get data
        $this->bind($this->getStorage()->read($this->getID()));
        // GET children
        $this->getChild()->GET();
        // return
        return $this;
    }

    /**
     * POST an entity; request to accept the entity enclosed as a new 
     * subordinate (RFC2616, p.54); synonym for "create".
     * @param stdClass $entity 
     * @return mixed
     */
    public function POST($entity) {
        if ($entity === null) {
            throw new Error('No item given to create', 400);
        }
        // last child
        $last_child = &$this->getLastChild();
        // bind
        $last_child->bind($entity);
        // create
        $id = $last_child->getStorage()->create($last_child, $last_child->getID());
        // mark changed
        $last_child->changed();
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
        // last child
        $last_child = &$this->getLastChild();
        // get properties
        $public_properties = array();
        foreach (get_public_vars($last_child) as $property => $value) {
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
        $last_child->getStorage()->update($last_child, $last_child->getID());
        // mark changed
        $last_child->changed();
        // return
        return $this;
    }

    /**
     * DELETE a resource; request to delete the resource identified by 
     * the request URI (RFC2616, p.55)
     * @return Resource
     */
    public function DELETE() {
        // last child
        $last_child = &$this->getLastChild();
        // create
        $last_child->bind($last_child->getStorage()->delete($last_child->getID()));
        // mark changed
        $last_child->changed();
        // return
        return $last_child;
    }

    /**
     * Returns a blank message if the resource exists (HTTP code 200) or 
     * an exception (HTTP code 404) if it does not; similar to "exists".
     * @return null 
     */
    public function HEAD() {
        // last child
        $last_child = &$this->getLastChild();
        // create
        if (!$last_child->getStorage()->exists($last_child->getID())) {
            throw new Error($this->getUri() . " does not exist.", 404);
        }
    }

    /**
     * Returns an object describing all upper-case methods (i.e. HTTP verbs)
     * defined in the class and all public properties; additionally, as a tree,
     * it specifies properties and methods for child resources.
     * @return stdClass 
     */
    public function OPTIONS() {
        $response = new stdClass();
        $response->methods = array();
        $response->properties = array();
        $response->children = array();
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
        // get children
        if (is_array($this->allowed_children)) {
            foreach ($this->allowed_children as $child) {
                $response->children[$child] = $this->getChild()->OPTIONS();
            }
        }
        /*else if ($this->allowed_children === true && $this->child !== null) {
            $class_name = get_class($this->getChild());
            $response->children[$class_name] = $this->getChild()->OPTIONS();
        }*/
        // return
        return $response;
    }

}
