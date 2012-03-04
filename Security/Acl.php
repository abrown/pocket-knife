<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for managing a user access control list and
 * restricting/permitting access to resources
 * @uses SecurityRule, ResourceList
 */
class SecurityAcl extends ResourceList {

    /**
     * Sets whether the ACL will be restrictive or permissive by default
     * @var string either "deny" or "allow"
     */
    public $default_access = 'deny'; // deny | allow

    public function __construct($settings) {
        // validate
        BasicValidation::with($settings)
                ->withProperty('storage')
                ->isObject()
                ->withProperty('type')
                ->isString();
        // import settings
        foreach ($this as $property => $value) {
            if (isset($settings->$property)) {
                $this->$property = $settings->$property;
            }
        }
    }

    /**
     * Determines whether a user has access to perform an action
     * @param string $name
     * @param array $roles
     * @param string $action
     * @param string $resource Resource type
     * @param string $id Resource ID
     * @return boolean
     */
    public function isAllowed($name, $roles, $action, $resource, $id) {
        // false = deny | true = allow
        $default = ($this->default_access == 'allow');
        // search through levels to find a proof of the default
        $levels = array_merge((array) $name, (array) $roles, (array) '*');
        foreach ($levels as $level) {
            // get rules for this level
            foreach ($this->getRulesFor($level) as $rule) {
                // only consider rule if it matches the current context
                if ($rule->matches($action, $resource, $id)) {
                    return $rule->access;
                }
            }
        }
        // if no rule found, send the default
        return $default;
    }

    /**
     * Returns rules applying to this name
     * @param string $name 
     */
    public function getRulesFor($name) {
        $rules = $this->getStorage()->search('name', $name);
        // to SecurityRules
        $out = array();
        foreach ($rules as $rule) {
            $out[] = new SecurityRule($rule->name, $rule->action, $rule->resource, $rule->id, $rule->access);
        }
        // sort by specificity; most specific rules on top (i.e. those with the least number of '*')
        usort($out, array('SecurityRule', 'compare'));
        // return
        return $out;
    }

}