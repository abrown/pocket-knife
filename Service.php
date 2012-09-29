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

            // do security tasks (authenticate, authorize); throws Error
            self::performSecurityCheck($this, $this);

            // create object instance if necessary
            if (!$this->object) {
                if (!class_exists($this->resource))
                    throw new Error('Could not find Resource class: ' . $this->resource, 404);
                $resource_class = $this->resource;
                $this->object = new $resource_class();
            }

            // cache
            if (!method_exists($this->object, 'isCacheable')) {
                throw new Error('Resource must display cacheability using isCacheable() method', 500);
            }
            if (!$return_as_string && $this->object->isCacheable()) {
                $uri = WebUrl::getURI();
                $cache = Cache::getInstance();
                $cache->GET($uri);
                // if client has a current resource, use that
                if ($cache->isClientCurrent()) {
                    header('HTTP/1.1 304 Not Modified');
                    exit();
                } 
                // if the action is GET, use the cached copy
                elseif($this->action == 'GET') {
                    $representation = new Representation($cache->resource, $this->accept);
                    $representation = $this->executeOutputTriggers($cache->resource, $this->action, $representation);
                    // send headers
                    header('Etag: "' . $cache->getEtag() . '"');
                    header('Last-Modified: ' . $cache->getLastModified());
                    // send resource
                    $representation->send();
                }
            }

            // receive incoming data
            $representation->receive();

            // input triggers
            $representation = $this->executeInputTriggers($this->object, $this->action, $representation);

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
            $representation = $this->executeOutputTriggers($this->object, $this->action, $representation);

            // output
            if ($return_as_string) {
                return (string) $representation;
            } else {
                // caching
                if ($this->object->isCacheable() && $this->action == 'GET') {
                    $cache = Cache::getInstance();
                    $cache->PUT($this->object);
                    // send headers
                    header('Etag: "' . $cache->getEtag() . '"');
                    header('Last-Modified: ' . $cache->getLastModified());
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
     * Perform a security check on the current request using an ACL and authorization
     * scheme defined in $settings. In Service context, this can be passed as the 
     * second parameter.
     * @example 
     * // pass $configuration as the first parameter
     * $configuration = new Settings();
     * $configuration->representations = array('text/html', 'application/json');
     * $configuration->set('authentication.enforce_https', false);
     * $configuration->set('authentication.authentication_type', 'digest');
     * $configuration->set('authentication.password_security', 'plaintext');
     * $admin_user = array('username' => 'admin', 'roles' => 'administrator', 'password' => '...');
     * $configuration->set('authentication.users', array($admin_user));
     * $configuration->acl = array(
     *  'admin can * some_resource/*',
     *  '* can GET another_resource/23 ',
     * );
     */
    public static function performSecurityCheck($settings, $request) {
        // validate settings
        BasicValidation::with($settings)
                ->withProperty('acl')
                ->upAll()
                ->withProperty('authentication')
                ->withProperty('authentication_type')
                ->oneOf('basic', 'digest', 'facebook', 'header', 'session');
        // validate request
        BasicValidation::with($request)
                ->withOptionalProperty('action')->isString()->upAll()
                ->withOptionalProperty('resource')->isString()->upAll()
                ->withOptionalProperty('id')->upAll()
                ->withProperty('content_type')->isString()->upAll()
                ->withProperty('accept')->isString();
        // create URI; used to identify the request in thrown Errors
        $uri = ($request->resource !== null) ? $request->resource : 'UnnamedResource';
        $uri .= ($request->id !== null) ? '/' . $request->id : '';
        // test ACL for 'deny all'
        if ($settings->acl === false) {
            throw new Error("No users can perform the action '{$request->action}' on the resource '$uri'", 403);
        }
        // load ACL
        $acl = new SecurityAcl($settings->acl);
        // test ACL for 'allow' all
        if ($acl->isAllowed('*', '*', $request->action, $request->resource, $request->id)) {
            return true;
        }
        // add default memory storage if 'users' list exists
        if (property_exists($settings->authentication, 'users') && is_array($settings->authentication->users)) {
            $settings->authentication->storage = new stdClass();
            $settings->authentication->storage->type = 'memory';
            $settings->authentication->storage->data = to_object($settings->authentication->users);
        }
        // load authentication
        $class = 'SecurityAuthentication' . ucfirst($settings->authentication->authentication_type);
        $auth = new $class($settings->authentication);
        // authenticate user
        if ($auth && !$auth->isLoggedIn()) {
            // get credentials
            $credentials = $auth->receive($request->content_type);
            // challenge, if necessary
            if (!$auth->isValidCredential($credentials)) {
                $auth->send($request->accept);
                exit();
            }
        }
        // authorize request
        if ($acl !== true) {
            $user = $auth->getCurrentUser();
            $roles = $auth->getCurrentRoles();
            if (!$acl->isAllowed($user, $roles, $request->action, $request->resource, $request->id)) {
                throw new Error("'$user' cannot perform the action '{$request->action}' on the resource '$uri'", 403);
            }
        }
        // return 
        return true;
    }

    /**
     * Execute applicable input triggers in the given Resource
     * @param Resource $object
     * @param string $action
     * @param Representation $representation
     * @return Representation
     * @throws Error
     */
    public function executeInputTriggers($object, $action, $representation) {
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
        return $representation;
    }
    
    /**
     * Execute applicable output triggers in the given Resource
     * @param Resource $object
     * @param string $action
     * @param Representation $representation
     * @return Representation
     * @throws Error
     */
    public function executeOutputTriggers($object, $action, $representation) {
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
        return $representation;
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
