<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for managing a user access control list and
 * restricting/permitting access to resources
 * @uses ResourceItem, Error
 */
class SecurityRule {

    /**
     * User name or role name
     * @var string
     */
    public $name;

    /**
     * Action on resource
     * @var string
     */
    public $action;

    /**
     * Resource type
     * @var string
     */
    public $resource;

    /**
     * Resource ID
     * @var string
     */
    public $id;

    /**
     * Sets access level for this context; true = allow | false = deny
     * @var boolean
     */
    public $access;

    /**
     * Constructor
     * @param string $name user or role
     * @param string $action
     * @param string $resource
     * @param mixed $id
     * @param boolean $access 
     */
    function __construct($name, $action, $resource, $id, $access) {
        $this->name = $name;
        $this->action = $action;
        $this->resource = $resource;
        $this->id = $id;
        $this->access = $access;
    }

    /**
     * Matches a context (resource, action, id) to this rule, returning
     * true if they match
     * @param string $action
     * @param string $resource Resource type
     * @param string $id Resource ID
     * @return boolean
     */
    public function matches($action, $resource, $id) {
        return
                ( $action == $this->action || $this->action == '*' ) &&
                ( $resource == $this->resource || $this->resource == '*' ) &&
                ( $id == $this->id || $this->id == '*' || $this->id == null );
    }

    /**
     * Compares two security rules for specifity
     * @param SecurityRule $a
     * @param SecurityRule $b
     * @return int 
     */
    static public function compare($a, $b) {
        if (!is_a($a, 'SecurityRule') || !is_a($b, 'SecurityRule')) {
            throw new Error('Only security rules may be compared', 500);
        }
        $result = 0;
        $properties = array('action', 'resource', 'id');
        foreach ($properties as $property) {
            if ($a->$property == '*') {
                $result++;
            }
            if ($b->$property == '*') {
                $result--;
            }
        }
        return $result;
    }

}