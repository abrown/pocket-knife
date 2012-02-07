<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for managing a user access control list and
 * restricting/permitting access to resources
 * @uses ExceptionWeb
 */
class SecurityRule extends ResourceItem{
    
    /**
     * Group nanme
     * @var string
     */
    public $group;
    
    /**
     * User name
     * @var string
     */
    public $user;
    
    /**
     * Resource name
     * @var string
     */
    public $resource;
    
    /**
     * Action on resource
     * @var string
     */
    public $action;
    
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
}