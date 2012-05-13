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
     * @example $this->acl = array('user/29 can access [create, read] by posts/get_allowed/29 in posts');
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
     * Defines the Content-Type for the current request
     * @var string 
     */
    public $content_type;

    /**
     * Constructor
     * @param Settings $settings
     */
    public function __construct($settings) {
        // validate
        BasicValidation::with($settings)
                ->withOptionalProperty('authentication')
                ->withProperty('authentication_type')
                ->isString()
                ->upAll()
                ->withProperty('representations')
                ->isArray()
                ->withKey(0)
                ->isString();
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

        // find what we act upon
        list($resource, $id, $action) = $this->getRouting();
        if (!$this->resource)
            $this->resource = $resource;
        if (!$this->action)
            $this->action = $action;
        if (!$this->id)
            $this->id = $id;
        // content type
        if (!$this->content_type)
            $this->content_type = WebHttp::getContentType();

        // authenticate user
        if ($this->getAuthentication() && !$this->getAuthentication()->isLoggedIn()) {
            // get credentials
            $credentials = $this->getAuthentication()->fromRepresentation($this->content_type);
            // challenge, if necessary
            if (!$this->getAuthentication()->isValidCredential($credentials)) {
                $output_representation = $this->getAuthentication()->toRepresentation($this->content_type);
                $output_representation->send();
                exit();
            }
        }

        // authorize request
        if( $this->acl === false ){
            throw new Error("No users can perform the action '{$this->action}' on the resource '$resource.$id'", 403);
        }
        elseif ($this->acl !== true) {
            $user = $this->getAuthentication()->getCurrentUser();
            $roles = $this->getAuthentication()->getCurrentRoles();
            if (!$this->getAcl()->isAllowed($user, $roles, $this->action, $this->resource, $this->id)) {
                throw new Error("'$user' cannot perform the action '{$this->action}' on the resource '$resource.$id'", 403);
            }
        }

        // special mappping
        switch ($this->resource) {
            case 'admin':
                $this->resource = 'SiteAdministration';
                break;
            case 'config':
                $this->resource = 'SiteConfiguration';
                break;
            case 'auth':
                $this->resource = 'SecurityAuthentication';
                break;
        }

        // serve a response
        try {

            // create object instance if necessary
            if (!$this->object) {
                if (!class_exists($this->resource))
                    throw new Error('Could not find Resource class: ' . $this->resource, 404);
                $resource_class = $this->resource;
                $this->object = new $resource_class();
            }

            // get representation and incoming data
            $input_representation = $this->object->fromRepresentation($this->content_type);
            $input_representation->receive();

            // set ID
            if( isset($this->id) && method_exists($this->object, 'setID') ){
                $this->object->setID($this->id);
            }
            // call method
            if (!method_exists($this->object, $this->action)){
                throw new Error("Action '{$this->action}' does not exist", 404);
            }
            $callback = array($this->object, $this->action);
            $data = $input_representation->getData();
            $result = call_user_func_array($callback, array($data));

            // get representation and send data
            $output_representation = $this->object->toRepresentation($this->content_type, $result);
            $output_representation->send();
        } catch (Error $e) {
            // get representation and send data
            WebHttp::setCode($e->getCode());
            $output_representation = $e->toRepresentation($this->content_type, null);
            $output_representation->send();
        }
    }

    /**
     * Returns SecurityAuthentication object for this request
     * @staticvar string $authentication
     * @return SecurityAuthentication 
     */
    public function getAuthentication() {
        static $authentication = null;
        if (is_null($authentication)) {
            if ($this->authentication === false) {
                $authentication = false;
            } else {
                $class = 'SecurityAuthentication' . ucfirst($this->authentication->authentication_type);
                $authentication = new $class($this->authentication);
            }
        }
        return $authentication;
    }

    /**
     * Returns SecurityAcl object for this request
     * @staticvar string $authentication
     * @return SecurityAuthentication 
     */
    public function getAcl() {
        static $acl = null;
        if (is_null($acl)) {
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
            // get id or method
            $undecided = next($tokens);
            if (method_exists($object, $undecided)) {
                // found method in class
                $method = $undecided;
                $id = null;
            } else {
                // default to order object/id/method
                $id = $undecided;
                $method = next($tokens);
            }
            // method default
            if (!$method) {
                $method = WebHttp::getMethod();
            }
            // do not count '*' as id
            if ($id == '*')
                $id = null;
            // set routing
            $routing = array($object, $id, $method);
        }
        // return
        return $routing;
    }

//    /**
//     * Checks whether the specified type and value are allowed by the configured ACL
//     * @return boolean
//     * */
//    public function allowed($type, $value) {
//        $value = strtolower($value);
//        // allow/deny settings
//        if (is_bool($this->acl))
//            return $this->acl;
//        // get list
//        static $list = null;
//        if (is_null($list)) {
//            $list = (array) $this->acl;
//        }
//        // walk backwards through list
//        end($list);
//        do {
//            $rule = current($list);
//            if (preg_match('/^(.+) can(not)? access (.+) in (.+)$/i', $rule, $matches)) {
//                // parse
//                $r = array();
//                $r['user'] = explode(',', $matches[1]);
//                $r['user'] = array_map('trim', $r['user']);
//                $r['deny'] = $matches[2];
//                $r['method'] = explode(',', $matches[3]);
//                $r['method'] = array_map('trim', $r['method']);
//                $r['class'] = explode(',', $matches[4]);
//                $r['class'] = array_map('trim', $r['class']);
//                // test
//                if (
//                        ($r[$type][0] === '*' || in_array($value, $r[$type])) &&
//                        !$r['deny']
//                ) {
//                    return true;
//                }
//            } else {
//                throw new Error('Poorly worded ACL rule: ' . $rule, 500);
//            }
//        } while (prev($list) !== false);
//        // return
//        return false;
//    }

    /**
     * Returns the storage object for this request
     * @var array
     * */
//    protected function getStorage() {
//        static $object = null;
//        if (!$object) {
//            $settings = $this->storage;
//            // check Settings
//            if (!isset($settings->type))
//                throw new Error('Storage type is not defined', 500);
//            // get class
//            $class = 'Storage' . ucfirst($settings->type);
//            // check parents
//            if (!in_array('StorageInterface', class_implements($class)))
//                throw new Error($class . ' must implement StorageInterface.', 500);
//            // create object
//            $object = new $class($settings);
//        }
//        return $object;
//    }
//    /**
//     * Returns the applicable input handler for this request
//     * @return object
//     * */
//    protected function getInput() {
//        static $object = null;
//        if (!$object) {
//            // get class
//            $content_type = $this->input;
//            if (is_null($content_type))
//                $content_type = $this->input;
//            $class = $this->getContentClass($content_type);
//            // check parents
//            if (!in_array('ServiceFormatInterface', class_implements($class)))
//                throw new Error($class . ' must implement ServiceFormatInterface.', 500);
//            // create object
//            $object = new $class();
//        }
//        return $object;
//    }
//
//    /**
//     * Returns the applicable output handler for this request
//     * @return object
//     * */
//    protected function getOutput() {
//        static $object = null;
//        if (!$object) {
//            // get class
//            $content_type = $this->output;
//            if (is_null($content_type))
//                $content_type = $this->output;
//            $class = $this->getContentClass($content_type);
//            // check parents
//            if (!in_array('ServiceFormatInterface', class_implements($class)))
//                throw new Error($class . ' must implement ServiceFormatInterface.', 500);
//            // create object
//            $object = new $class();
//        }
//        return $object;
//    }
//
//    /**
//     * Return the applicable template for this request
//     * @return object
//     * */
//    protected function getTemplate() {
//        static $object = null;
//        if (!$object) {
//            $template_file = $this->template;
//            $object = new WebTemplate($template_file, WebTemplate::PHP_FILE);
//            $object->setVariable('service', $this); // TODO: does this violate simplicity?
//        }
//        return $object;
//    }

    /**
     * Returns format class based on content-type
     * @param string $content_type
     */
    protected function getContentClass($content_type) {
        $map = array(
            'text/html' => 'ServiceFormatHtml',
            'application/x-www-form-urlencoded' => 'ServiceFormatHtml',
            'application/json' => 'ServiceFormatJson',
            'application/xml' => 'ServiceFormatXml',
            'application/soap+xml' => 'ServiceFormatSoap'
        );
        // return
        if (array_key_exists($content_type, $map))
            return $map[$content_type];
        else
            throw new Error('Attempting to use unknown content type: ' . $content_type, 500);
    }

}