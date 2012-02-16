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
class SecurityAcl extends ResourceList{
    
    /**
     * Sets whether the ACL will be restrictive or permissive by default
     * @var string either "deny" or "allow"
     */
    public $default_access = 'deny'; // deny | allow
    
    /**
     * Type of ResourceItem in this ResourceList
     * @var ResourceItem 
     */
    public $item_type = 'SecurityRule';
    
    /**
     * Determines whether a user has access to perform an action
     * @param string $group
     * @param string $user
     * @param string $action
     * @param string $resource Resource type
     * @param string $id Resource ID
     * @return boolean
     */
    public function isAllowed($group, $user, $action, $resource, $id){
        // false = deny | true = allow
        $default = ($this->default_access == 'allow');
        // search through levels to find a proof of the default
        $levels = array('*', $group, $user);
        foreach($levels as $level){
            // get rules for this level
            foreach($this->getStorage()->search("name", $level) as $rule){
                // only consider rule if it matches the current context
                if( $this->matches($action, $resource, $id, $rule) ){
                    if( $rule->access == $default ) return $default;
                }
            }
        }
        // if no rule found, send the opposite
        return !$default;
    }
    
    /**
     * Matches a context (resource, action, id) to a rule, returning
     * true if they match
     * @param string $action
     * @param string $resource Resource type
     * @param string $id Resource ID
     * @param stdClass $rule
     * @return boolean
     */
    protected function matches($action, $resource, $id, $rule){
        return 
            ( $action == $rule->action || $rule->action == '*' ) &&
            ( $resource == $rule->resource || $rule->resource == '*' ) &&
            ( $id == $rule->id || $rule->id == '*' || $rule->id == null );
    }
}