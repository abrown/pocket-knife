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
 * @uses Settings, Error, Client, Route, Representation, Cache, Acl, Authentication
 */
class Service {

    /**
     * Represents the client consuming this web service; see Client.php
     * @example
     * $this->client->getUserName(); // a name string for authenticated users or null otherwise
     * $this->client->isMobile(); // test if the client is mobile
     * @var Client 
     */
    public $client;

    /**
     * Defines how to the web service authenticates clients; see 
     * Authentication.php. By default this is turned off and does not
     * authenticate clients.
     * @var Authentication 
     */
    public $authentication;

    /**
     * Route for the current HTTP request
     * @var Route 
     */
    public $route;

    /**
     * Allowed representations for this web service; see Representation.php
     * @example
     * $this->representations = array('application/json', 'text/plain');
     * @var array list of content-type representations or '*' to allow all
     */
    public $representations = '*';

    /**
     * Current incoming HTTP Representation
     * @var Representation
     */
    public $representation;

    /**
     * The access control list for this web service; see Acl.php.
     * Defines access to each object/method/id combination; by default it is 
     * turned off and allows all requests.
     * @example 
     * $this->acl = new Acl('...');
     * @var Acl
     * */
    public $acl;

    /**
     * List of custom or allowed routes ... ?
     * @var Route
     */
    public $routes;

    /**
     * The resource loaded 
     * @var Resource
     */
    public $resource;
    public $cache;

    /**
     * Defines the Resource to interact with; typically set by WebRouting, 
     * but can be overriden manually; is the same as the class name extending
     * ResourceInterface
     * @example $this->class = 'book'; // can be lower-cased
     * @var string
     * */
    //public $resource;

    /**
     * Instance of class; typically set during normal execution
     * @example $this->object = new ClassName(...);
     * @var object
     * */
    //public $object;

    /**
     * Defines the action to perform on the object; must be a public method of the object instance
     * @example $this->action = 'new_action';
     * @var string
     * */
    //public $action;

    /**
     * Defines the ID for the object; to use this feature, the object constructor must look like "function __construct($id){...}"
     * @example $this->id = $new_id;
     * @var mixed
     * */
    //public $id;

    /**
     * Defines the MIME type for the incoming data; is set by
     * the client in the HTTP Content-Type header
     * @var string 
     */
    //public $content_type;

    /**
     * Defines the MIME type for the outgoing data; is set
     * by the client in the HTTP Accept header
     * @var type 
     */
    //public $accept;

    /**
     * Constructor
     * @param Settings $settings
     */
    public function __construct(Settings $settings) {
        // validate during debugging stage
        if (DEBUGGING) {
            try {
                //BasicValidation::with($settings)
                //->withOptionalProperty('authentication');
                //->withProperty('authentication_type')->isString()->upAll();
                //->withOptionalProperty('representations')->isArray()->withKey(0)->isString();
            } catch (Error $e) {
                // send an HTTP response because validation errors may occur before execute() is ever run
                $contentType = (WebHttp::getAccept()) ? WebHttp::getAccept() : 'text/plain';
                $e->send($contentType);
                exit();
            }
        }
        // import settings
        $settings->copyTo($this);
    }

    /**
     * Handles requests, creates instances, and returns result
     */
    public function execute() {
        try {
            // setup; note that you can set authentication
            $this->client = (!$this->client) ? new Client() : $this->client;
            $this->route = (!$this->route) ? new Route() : $this->route;
            $this->representation = (!$this->representation) ? $this->buildRepresentation($this->route) : $this->representation;
            // security-related checks
            if ($this->authentication && !$this->authentication->identify($this->client)) {
                throw new Error("$this->client is not authenticated.", 403);
            }
            if ($this->acl && !$this->acl->allowed($this->client, $this->route)) {
                throw new Error("$this->client cannot access $this->route.", 403);
            }
            // test cache
            $this->cache = (!$this->cache) ? new Cache() : $this->cache;
            if ($this->cache->HEAD($this->route) && !DEBUGGING) {
                return $this->cache->GET($this->route); // caching is disabled while debugging; this prevents the cache from filling up unnecessarily
            }
            // 
            $this->resource = (!$this->resource) ? $this->buildResource($this->route) : $this->resource;
            $resultRepresentation = $this->consumeResource($this->resource, $this->representation, $this->route->method);
            // more caching
            $this->cache->PUT($this->route, $resultRepresentation);
            // return
            $resultRepresentation->send(200, $this->client->getAcceptableContentType($this->route->contentType));
            return $resultRepresentation;
        } catch (Error $e) {
            $e->send($this->client->getAcceptableContentType($this->route->contentType));
        }
    }

    /**
     * Build a representation of incoming data from the HTTP request based on
     * the current route. This method defaults to the application/json content
     * type when it cannot discover it from the HTTP Content-Type or Accept
     * headers.
     * @param Route $route
     * @return Representation
     */
    public function buildRepresentation(Route $route) {
        $representation = new Representation();
        $representation->setContentType($route->contentType);
        $representation->receive();
        // return
        return $representation;
    }

    /**
     * Build a resource from the given route. This method does not perform
     * security checks on what methods can be built; these checks must be
     * performed before in ACL.
     * @param Route $route
     * @throws Error
     */
    public function buildResource(Route $route) {
        $class = $route->getResource();
        if (!$class) {
            throw new Error("No resource identified by this request. The URI is '{$route}' but should be '[resource]/.../...'.", 400);
        }
        if (!class_exists($class)) {
            $message = "Could not find the Resource class '{$class}'.";
            if (DEBUGGING) {
                $message .= ' The include paths are: ' . get_include_path();
            }
            throw new Error($message, 404);
        }
        if (!is_subclass_of($class, 'Resource')) {
            throw new Error("The given class is not a Resource; all exposed resources must descend from Resource.", 500);
        }
        $resource = new $class();
        $resource->bindFromRoute($route);
        return $resource;
    }

    /**
     * Consume the resource, returning an output representation of the result;
     * triggers callbacks. The input data coming from the HTTP request is passed
     * to the HTTP method (e.g. GET, POST...) in the resource as a single 
     * standard PHP object.
     * @param Resource $resource
     * @param string $method HTTP method
     * @param Representation $input
     * @return Representation
     */
    public function consumeResource(Resource $resource, Representation $input, $method = 'GET') {
        // input triggers
        $this->trigger($resource, 'INPUT_TRIGGER', $input);
        $this->trigger($resource, $method . '_INPUT_TRIGGER', $input);
        // execute HTTP method: GET, POST, PUT, etc.
        if (!method_exists($resource, $method)) {
            throw new Error("Method '{$method}' does not exist for this resource; request OPTIONS for valid methods.", 405);
        }
        $_output = $resource->$method($input->getData()); // TODO: possibly use call_user_func_array() to pass a list of parameters?
        $output = new Representation($_output, $input->getContentType()); // match the input content type by default
        // output triggers
        $this->trigger($resource, 'OUTPUT_TRIGGER', $output);
        $this->trigger($resource, $method . '_OUTPUT_TRIGGER', $output);
        // return
        return $output;
    }

    /**
     * Trigger a callback method within the Resource class; this method should
     * modify the given representation directly as these changes will allow 
     * @param Resource $resource
     * @param string $name
     * @param Representation $representation
     */
    public function trigger(Resource $resource, $name, Representation $representation) {
        if (method_exists($resource, $name)) {
            $resource->$name($representation);
        }
    }

    public function old_execute($return_as_string = false) {

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
            // set object ID
            if (isset($this->id) && method_exists($this->object, 'setID')) {
                $this->object->setID($this->id);
            }

            // check cache
            if (!method_exists($this->object, 'isCacheable')) {
                throw new Error('Resource must display cacheability using isCacheable() method', 500);
            }
            if (!$return_as_string && !DEBUGGING && $this->object->isCacheable()) {
                $cache = Cache::getInstance(WebUrl::getURI());
                if ($cache->HEAD() && $this->action == 'GET') {
                    $cache->GET();
                    // if client has a current resource, use that
                    if ($cache->isClientCurrent()) {
                        header('HTTP/1.1 304 Not Modified');
                        exit();
                    }
                    // otherwise, use the cached copy
                    else {
                        $representation = new Representation($cache->resource, $this->accept);
                        $representation = $this->executeOutputTriggers($cache->resource, $this->action, $representation);
                        // send headers
                        header('Etag: "' . $cache->getEtag() . '"');
                        header('Last-Modified: ' . $cache->getLastModified());
                        // send resource
                        if ($return_as_string) {
                            return (string) $representation;
                        } else {
                            $representation->send();
                            return;
                        }
                    }
                }
            }

            // receive incoming data
            $representation->receive();

            // input triggers
            $representation = $this->executeInputTriggers($this->object, $this->action, $representation);

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
            } elseif (!DEBUGGING) {
                // caching
                if ($this->object->isCacheable() && $this->action == 'GET') {
                    $cache = Cache::getInstance(WebUrl::getURI());
                    $cache->PUT($this->object);
                    // send headers
                    header('Etag: "' . $cache->getEtag() . '"');
                    header('Last-Modified: ' . $cache->getLastModified());
                } elseif ($this->object->isCacheable() && $this->action == 'DELETE') {
                    $cache = Cache::getInstance(WebUrl::getURI());
                    $cache->DELETE();
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
     * // create request
     * $request = new stdClass();
     * list($request->resource, $resource->id, $resource->action) = Service::getRouting();
     * $request->content_type = WebHttp::getContentType();
     * $request->accept = WebHttp::getAccept();
     */
    public static function performSecurityCheck($settings, $request) {
        // validate settings
//        BasicValidation::with($settings)
//                ->withOptionalProperty('acl')->upAll()
//                ->withOptionalProperty('authentication')
//                ->withOptionalProperty('authentication_type')->oneOf('basic', 'digest', 'facebook', 'header', 'session');
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
        // test ACL for 'allow all'
        if ($settings->acl === true) {
            return true;
        }
        // load ACL
        $acl = new SecurityAcl($settings->acl);
        // test ACL for 'allow' all
        if ($acl->isAllowed('*', '*', $request->action, $request->resource, $request->id)) {
            return true;
        }
        // add default memory storage if 'users' list exists
//        if (property_exists($settings->authentication, 'users') && is_array($settings->authentication->users)) {
//            $settings->authentication->storage = new stdClass();
//            $settings->authentication->storage->type = 'memory';
//            $settings->authentication->storage->data = to_object($settings->authentication->users);
//        }
        // load authentication
        $class = 'SecurityAuthentication' . ucfirst($settings->authentication->authentication_type);
        $auth = new $class(new Settings($settings->authentication));
        // authenticate user
        if ($auth && !$auth->isLoggedIn()) {
            // get credentials
            $credentials = $auth->receive($request->contentType);
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
     * 
     * @return type
     * @throws Error
     */
//    public static function checkCache($object) {
//        if (!method_exists($object, 'isCacheable')) {
//            throw new Error('Resource must display cacheability using isCacheable() method', 500);
//        }
//        if (!$return_as_string && $this->object->isCacheable()) {
//            $cache = Cache::getInstance(WebUrl::getURI());
//            if ($cache->HEAD() && $this->action == 'GET') {
//                $cache->GET();
//                // if client has a current resource, use that
//                if ($cache->isClientCurrent()) {
//                    header('HTTP/1.1 304 Not Modified');
//                    exit();
//                }
//                // otherwise, use the cached copy
//                else {
//                    $representation = new Representation($cache->resource, $this->accept);
//                    $representation = $this->executeOutputTriggers($cache->resource, $this->action, $representation);
//                    // send headers
//                    header('Etag: "' . $cache->getEtag() . '"');
//                    header('Last-Modified: ' . $cache->getLastModified());
//                    // send resource
//                    if ($return_as_string) {
//                        return (string) $representation;
//                    } else {
//                        $representation->send();
//                        return;
//                    }
//                }
//            }
//        }
//    }
}
