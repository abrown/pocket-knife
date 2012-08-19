<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods to run a web service. 
 * @example
 * $settings = new Settings(array(
 * 	'acl' => '* can access * in *',
 * 	'storage' => array('type' => 'mysql', 'username' => '...', 'password' => '...', 'location' => 'localhost', 'database' => '...', 'table' => '...'),
 * 	...
 * ));
 * $service = new Service($settings);
 * $service->execute();
 * @uses Settings, WebHttp, WebTemplate, Error, Error
 */
class Service {

    /**
     * Settings for the authentication section; see Authentication.php
     * @var Settings 
     */
    public $authentication = false;

    /**
     * Settings for the ACL section; see Acl.php.
     * Defines access to each object/method/id combination; set to true to allow all, false to deny all
     * @example 
     * $this->acl = array('admin can * posts/*');
     * $this->acl = array('user/53 cannot POST posts/29');
     * @var mixed
     * */
    public $acl = true;

    /**
     * 
     * @var mixed
     */
    public $representations = '*';

    /**
     * Defines the resource to interact with; typically set by WebRouting, 
     * but can be overriden manually; is the same as the class name extending
     * ResourceInterface
     * @example $this->class = 'book'; // can be lower-cased
     * @var string
     * */
    public $resource;

    /**
     * Instance of class; typically set during normal execution
     * @example $this->object = new ClassName(...);
     * @var object
     * */
    public $object;

    /**
     * Defines the action to perform on the object; must be a public method of the object instance
     * @example $this->action = 'new_action';
     * @var string
     * */
    public $action;

    /**
     * Defines the ID for the object; to use this feature, the object constructor must look like "function __construct($id){...}"
     * @example $this->id = $new_id;
     * @var mixed
     * */
    public $id;

    /**
     * Defines the MIME type for the incoming data; is set by
     * the client in the HTTP Content-Type header
     * @var string 
     */
    public $content_type;

    /**
     * Defines the MIME type for the outgoing data; is set
     * by the client in the HTTP Accept header
     * @var type 
     */
    public $accept;

    /**
     * Constructor
     * @param Settings $settings
     */
    public function __construct($settings) {
        // validate
        try {
            BasicValidation::with($settings)
                    ->withOptionalProperty('authentication')
                    ->withProperty('authentication_type')
                    ->isString()
                    ->upAll()
                    ->withProperty('representations')
                    ->isArray()
                    ->withKey(0)
                    ->isString();
        } catch (Error $e) {
            // send an HTTP response because validation errors may occur before execute() is ever run
            $e->send(WebHttp::getAccept());
            exit();
        }
        // import settings
        foreach ($this as $property => $value) {
            if (isset($settings->$property)) {
                $this->$property = $settings->$property;
            }
        }
    }

    /**
     * Handles requests, creates instances, and returns result
     */
    public function execute($return_as_string = false) {
        // handle request
        try {
            // get input/output MIME types
            if (!$this->content_type) {
                $this->content_type = $this->getContentType();
            }
            if (!$this->accept) {
                $this->accept = $this->getAccept();
            }

            // create input representation
            $representation = new Representation(null, $this->content_type);

            // find what we act upon
            list($resource, $id, $action) = $this->getRouting();
            if (!$this->resource)
                $this->resource = $resource;
            if (!$this->action)
                $this->action = $action;
            if (!$this->id)
                $this->id = $id;

            // do security tasks (authenticate, authorize)
            $this->executeSecurity();

            // clear session
            if ($this->resource == 'clear_session') {
                WebSession::clear();
                $representation = new Representation("Session cleared.", $this->accept);
                $representation->send();
                exit();
            } elseif ($this->resource == 'clear_cache') {
                // @TODO do not open this to the public
                // StorageCache::clear();
                $representation = new Representation("Session cleared.", $this->accept);
                $representation->send();
                exit();
            }

            // special mappping
            /*
              switch ($this->resource) {
              case 'admin':
              $this->resource = 'SiteAdministration';
              break;
              case 'config':
              $this->resource = 'SiteConfiguration';
              break;
              }
             */

            // create object instance if necessary
            if (!$this->object) {
                if (!class_exists($this->resource))
                    throw new Error('Could not find Resource class: ' . $this->resource, 404);
                $resource_class = $this->resource;
                $this->object = new $resource_class();
            }

            // test cache
            if (!method_exists($this->object, 'isCacheable')) {
                throw new Error('Resource must display cacheability using isCacheable() method', 500);
            }
            if (!$return_as_string && $this->object->isCacheable()) {
                $uri = WebUrl::getURI();
                if (!StorageCache::isModified($uri)) {
                    StorageCache::sendNotModified();
                    exit();
                }
            }

            // receive incoming data
            $representation->receive();

            // input triggers
            $any_trigger = 'INPUT_TRIGGER';
            if (method_exists($this->object, $any_trigger)) {
                $representation = $this->object->$any_trigger($representation);
                if (!is_a($representation, 'Representation')) {
                    throw new Error('Input triggers must return a Representation', 500);
                }
            }
            $action_trigger = $this->action . '_INPUT_TRIGGER';
            if (method_exists($this->object, $action_trigger)) {
                $representation = $this->object->$action_trigger($representation);
                if (!is_a($representation, 'Representation')) {
                    throw new Error('Input triggers must return a Representation', 500);
                }
            }

            // set ID
            if (isset($this->id) && method_exists($this->object, 'setID')) {
                $this->object->setID($this->id);
            }
            // call method
            if (!in_array($this->action, array('GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'OPTIONS'))) {
                throw new Error("Method '{$this->action}' is an invalid HTTP method; request OPTIONS for valid methods.", 405);
            }
            if (!method_exists($this->object, $this->action)) {
                throw new Error("Method '{$this->action}' does not exist; request OPTIONS for valid methods.", 405);
            }
            $callback = array($this->object, $this->action);
            $result = call_user_func_array($callback, array($representation->getData()));

            // get representation
            $representation = new Representation($result, $this->accept);

            // output triggers
            $any_trigger = 'OUTPUT_TRIGGER';
            if (method_exists($this->object, $any_trigger)) {
                $representation = $this->object->$any_trigger($representation);
                if (!is_a($representation, 'Representation')) {
                    throw new Error('Input triggers must return a Representation', 500);
                }
            }
            $action_trigger = $this->action . '_OUTPUT_TRIGGER';
            if (method_exists($this->object, $action_trigger)) {
                $representation = $this->object->$action_trigger($representation);
                if (!is_a($representation, 'Representation')) {
                    throw new Error('Input triggers must return a Representation', 500);
                }
            }

            // output
            if ($return_as_string) {
                return (string) $representation;
            } else {
                // caching
                if ($this->object->isCacheable()) {
                    StorageCache::sendModified(WebUrl::getURI());
                }
            }
            $representation->send();
        } catch (Error $e) {
            if ($return_as_string) {
                return (string) $e;
            }
            // send error
            if (!$this->accept) {
                $this->accept = 'text/html';
            }
            $e->send($this->accept);
        }
    }

    /**
     * 
     */
    public function executeSecurity() {
        // test ACL for 'deny all'
        if ($this->acl === false) {
            throw new Error("No users can perform the action '{$this->action}' on the resource '$resource.$id'", 403);
        }
        // test ACL for 'allow'
        if ($this->getAcl()->isAllowed('*', '*', $this->action, $this->resource, $this->id)) {
            return true;
        }
        // authenticate user
        if ($this->getAuthentication() && !$this->getAuthentication()->isLoggedIn()) {
            // get credentials
            $credentials = $this->getAuthentication()->receive($this->content_type);
            // challenge, if necessary
            if (!$this->getAuthentication()->isValidCredential($credentials)) {
                $this->getAuthentication()->send($this->accept);
                exit();
            }
        }
        // autorize request
        if ($this->acl !== true) {
            $user = $this->getAuthentication()->getCurrentUser();
            $roles = $this->getAuthentication()->getCurrentRoles();
            if (!$this->getAcl()->isAllowed($user, $roles, $this->action, $this->resource, $this->id)) {
                throw new Error("'$user' cannot perform the action '{$this->action}' on the resource '$resource/$id'", 403);
            }
        }
        // return 
        return true;
    }

    /**
     * Returns SecurityAuthentication object for this request
     * @staticvar string $authentication
     * @return SecurityAuthentication 
     */
    public function getAuthentication() {
        static $auth = null;
        if (is_null($auth)) {
            if ($this->authentication === false) {
                $authentication = false;
            } else {
                // add default memory storage if 'users' list exists
                if (property_exists($this->authentication, 'users') && is_array($this->authentication->users)) {
                    $this->authentication->storage = new stdClass();
                    $this->authentication->storage->type = 'memory';
                    $this->authentication->storage->data = to_object($this->authentication->users);
                }
                // instantiate authentication
                $class = 'SecurityAuthentication' . ucfirst($this->authentication->authentication_type);
                $auth = new $class($this->authentication);
            }
        }
        return $auth;
    }

    /**
     * Returns SecurityAcl object for this request
     * @staticvar string $authentication
     * @return SecurityAuthentication 
     */
    public function getAcl() {
        static $acl = null;
        if (is_null($acl)) {
            // add default memory storage if 'acl' is a list
            if (is_array($this->acl)) {
                $rules = $this->acl;
                $this->acl = new stdClass();
                $this->acl->storage = new stdClass();
                $this->acl->storage->type = 'memory';
                $this->acl->storage->data = to_object($rules);
            }
            // instantiate ACL
            $acl = new SecurityAcl($this->acl);
        }
        return $acl;
    }

    /**
     * Returns object/method/id for this Http request
     * @return array
     * */
    static public function getRouting() {
        static $routing = null;
        if (is_null($routing)) {
            $tokens = WebUrl::getTokens();
            // get object (always first)
            reset($tokens);
            $object = current($tokens);
            // get id
            $id = next($tokens);
            // get method
            $method = WebHttp::getMethod();
            // do not count '*' as id
            if ($id == '*')
                $id = null;
            // set routing
            $routing = array($object, $id, $method);
        }
        // return
        return $routing;
    }

    /**
     * Calculate the MIME type of the data the client is sending; this setting
     * is affected by the 'representations' property--this list of MIME types will
     * limit allowed Content-Types. If no type is given in the HTTP request, the 
     * application will default to the first type given in 'representations'.
     * @return string
     * @throws Error 
     */
    public function getContentType() {
        // get content-type
        $content_type = WebHttp::getContentType();
        // ensure a representation is available
        if (!array_key_exists($content_type, Representation::$MAP)) {
            $content_type = 'text/html';
        }
        // test for wildcard
        if ($this->representations == '*') {
            return $content_type;
        }
        // test for header existence
        if (!$content_type) {
            return $this->representations[0];
        }
        // test if header is valid
        if (!in_array($content_type, $this->representations)) {
            throw new Error("This application does not allow the following Content-Type: {$content_type}", 400);
        }
        // return
        return $content_type;
    }

    /**
     * Calculate the MIME type to send to the client; this setting
     * is affected by the 'representations' property--this list of MIME types will
     * limit allowed Accept types. If no type is given in the HTTP request, the 
     * application will default to the first type given in 'representations'.
     * @return string
     * @throws Error 
     */
    public function getAccept() {
        // get content-type
        $accept = WebHttp::getAccept();
        // ensure a representation is available
        if (!array_key_exists($accept, Representation::$MAP)) {
            $accept = 'text/html';
        }
        // test for wildcard
        if ($this->representations == '*') {
            return $accept;
        }
        // test for header existence
        if (!$accept) {
            return $this->representations[0];
        }
        // test if header is valid
        if (!in_array($accept, $this->representations)) {
            throw new Error("This application does not allow the following Accept: {$accept}", 400);
        }
        // return
        return $accept;
    }

}
