<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for managing a user access control list and
 * restricting/permitting access to resources
 * @uses ResourceList, SecurityRule, BasicValidation
 */
class SecurityAcl extends ResourceList {

    /**
     * Do not cache the ACL
     * @var boolean
     */
    protected $cacheable = false;

    /**
     * Sets whether the ACL will be restrictive or permissive by default
     * @var string either "deny" or "allow"
     */
    public $default_access = 'deny'; // deny | allow

    /**
     * Constructor
     * @param Settings $settings 
     */

    public function __construct($settings) {
        // add default memory storage if settings is a list of rules
        if (is_array($settings)) {
            $this->storage = array('type' => 'memory', 'data'=> $settings);
        }
        // validate
        else {
            BasicValidation::with($settings)
                    ->isSettings()
                    ->withProperty('storage')
                    ->isObject()
                    ->withProperty('type')
                    ->isString();
            // import settings
            $this->bindProtected($settings->getData());
        }
        // execute ResourceList constructor
        parent::__construct();
        // reformat rules if necessary
        if (is_string($this->getStorage()->first())) {
            foreach ($this->getStorage()->all() as $id => $rule) {
                $rule = self::parse($rule);
                $this->getStorage()->update($rule, $id);
            }
            $this->changed();
        }
    }

    /**
     * Parse a string into a list of rules based on the pattern: 
     * @param type $string 
     * @return SecurityRule
     */
    public static function parse($string) {
        preg_match('@(\S+) (can|cannot) (\S+) (\S+)/(\S*)@', $string, $match);
        if ($match) {
            $name = $match[1];
            if ($match[2] == 'can') {
                $access = true;
            } else {
                $access = false;
            }
            $action = $match[3];
            $resource = $match[4];
            if (!strlen($match[5])) {
                $id = '*';
            } else {
                $id = $match[5];
            }
            // return
            return new SecurityRule($name, $action, $resource, $id, $access);
        }
        // return
        throw new Error("SecurityRule does not conform to the pattern: [name] can|cannot [action] [resource]/[id]", 500);
        return null;
    }

    /**
     * Determines whether a user has access to perform an action; will search
     * more specific identifiers first (e.g. user name, then user role, then
     * '*'), returning the first match it finds.
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