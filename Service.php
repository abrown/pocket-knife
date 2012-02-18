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
 * @uses Settings, WebHttp, WebTemplate, ExceptionFile, ExceptionSettings
 */
class Service {

    /**
     * Defines access to each object/method/id combination; set to true to allow all, false to deny all
     * @example $this->acl = array('user/29 can access [create, read] by posts/get_allowed/29 in posts');
     * @var mixed
     * */
    public $acl = true;

    /**
     * Defines storage method for the object exposed; see classes in Storage for specific parameters required
     * @example $this->storage = array('type'=>'mysql', 'username'=>'test', 'password'=>'password', 'location'=>'localhost', 'database'=>'db');
     * @var array
     * */
    public $storage = array('type' => 'json', 'location' => 'db.json');

    /**
     * Defines the representation formats to be used by this 
     * web service; see Representation for a complete listing
     * of the mapping from 'Content-Type' to Representation[Type]
     * @example 
     * 	$this->representation['request'] = 'application/json';
     * @var array
     * */
    public $representation = array('request' => 'application/x-www-form-urlencoded', 'response' => 'text/html');

    /**
     * Template to apply to the output after processing
     * @var string
     * */
    public $template;

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
     * @example $this->method = 'new_action';
     * @var string
     * */
    public $method;

    /**
     * Defines the ID for the object; to use this feature, the object constructor must look like "function __construct($id){...}"
     * @example $this->id = $new_id;
     * @var mixed
     * */
    public $id;

    /**
     * Constructor
     * @param Settings $settings
     */
    public function __construct($settings) {
        // determines what settings must be passed
        $settings_template = array(
            'acl' => Settings::MANDATORY,
            'storage' => Settings::OPTIONAL | Settings::MULTIPLE,
            'input' => Settings::OPTIONAL | Settings::STRING,
            'output' => Settings::OPTIONAL | Settings::STRING,
            'template' => Settings::OPTIONAL | Settings::PATH,
            'class' => Settings::OPTIONAL | Settings::STRING,
            'object' => Settings::OPTIONAL | Settings::MULTIPLE,
            'method' => Settings::OPTIONAL | Settings::STRING,
            'id' => Settings::OPTIONAL
        );
        // accepts settings
        if (!$settings || !is_a($settings, 'Settings'))
            throw new ExceptionSettings('Incorrect settings given.', 500);
        $settings->validate($settings_template);
        // copy Settings into this
        foreach ($this as $key => $value) {
            if (isset($settings->$key))
                $this->$key = $settings->$key;
        }
    }

    /**
     * Handles requests, creates instances, and returns result
     */
    public function execute($return_as_string = false) {

        // find what we act upon
        list($resource, $id, $method) = $this->getRouting();
        if (!$this->action)
            $this->action = $method;
        if (!$this->resource)
            $this->resource = $resource;
        if (!$this->id)
            $this->id = $id;
        
        // content type
        if (!$this->content_type)
            $this->content_type = $_SERVER['CONTENT_TYPE'];

        // serve a response
        try {

            // check whether the client can access what he requested
            if( $this->acl !== true ){
                $user = SecurityAuthentication::getUser();
                $group = SecurityAuthentication::getGroup();
                if( !SecurityAcl::isAllowed($group, $user, $this->action, $this->resource, $this->id) ){
                    throw new ExceptionAccess("'$group.$user' cannot perform the action '{$this->action}' on the resource '$resource.$id'", 403);
                }
            }
            
            // create object instance if necessary
            if (!$this->object) {
                if (!class_exists($this->resource))
                    throw new ExceptionFile('Could not find Resource class: ' . $this->resource, 404);
                if (!in_array('ResourceInterface', class_implements($this->resource)))
                    throw new ExceptionSettings('Class must implement ResourceInterface.', 500);
                $this->object = new $this->resource($this->resources[$this->resource]);
            }

            // get representation and incoming data
            $input_representation = $this->object->fromRepresentation($content_type);
            $input_representation->receive();
            
            // call method
            if (!method_exists($this->object, $this->action))
                throw new ExceptionSettings("Action '{$this->action}' does not exist", 404);
            $result = call_user_func_array(array($this->object, $this->action), array($input_representation->getData()));

            // get representation and send data
            $output_representation = $this->object->toRepresentation($content_type, $result);
            $output_representation->send();
        } 
        catch (Exception $e) {
            // get representation and send data
            $output_representation = $e->toRepresentation($content_type, $result);
            $output_representation->send();
        }
    }

    /**
     * Returns object/method/id for this Http request
     * @return array
     * */
    static public function getRouting() {
        static $routing = null;
        if (is_null($routing)) {
            $tokens = WebRouting::getTokens();
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
                $method = WebRouting::getMethod();
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
    
    /**
     * Checks whether the specified type and value are allowed by the configured ACL
     * @return boolean
     * */
    public function allowed($type, $value) {
        $value = strtolower($value);
        // allow/deny settings
        if (is_bool($this->acl))
            return $this->acl;
        // get list
        static $list = null;
        if (is_null($list)) {
            $list = (array) $this->acl;
        }
        // walk backwards through list
        end($list);
        do {
            $rule = current($list);
            if (preg_match('/^(.+) can(not)? access (.+) in (.+)$/i', $rule, $matches)) {
                // parse
                $r = array();
                $r['user'] = explode(',', $matches[1]);
                $r['user'] = array_map('trim', $r['user']);
                $r['deny'] = $matches[2];
                $r['method'] = explode(',', $matches[3]);
                $r['method'] = array_map('trim', $r['method']);
                $r['class'] = explode(',', $matches[4]);
                $r['class'] = array_map('trim', $r['class']);
                // test
                if (
                        ($r[$type][0] === '*' || in_array($value, $r[$type])) &&
                        !$r['deny']
                ) {
                    return true;
                }
            } else {
                throw new ExceptionSettings('Poorly worded ACL rule: ' . $rule, 500);
            }
        } while (prev($list) !== false);
        // return
        return false;
    }

    /**
     * Returns the storage object for this request
     * @var array
     * */
    protected function getStorage() {
        static $object = null;
        if (!$object) {
            $settings = $this->storage;
            // check Settings
            if (!isset($settings->type))
                throw new ExceptionSettings('Storage type is not defined', 500);
            // get class
            $class = 'Storage' . ucfirst($settings->type);
            // check parents
            if (!in_array('StorageInterface', class_implements($class)))
                throw new ExceptionSettings($class . ' must implement StorageInterface.', 500);
            // create object
            $object = new $class($settings);
        }
        return $object;
    }

    /**
     * Returns the applicable input handler for this request
     * @return object
     * */
    protected function getInput() {
        static $object = null;
        if (!$object) {
            // get class
            $content_type = $this->input;
            if (is_null($content_type))
                $content_type = $this->input;
            $class = $this->getContentClass($content_type);
            // check parents
            if (!in_array('ServiceFormatInterface', class_implements($class)))
                throw new ExceptionSettings($class . ' must implement ServiceFormatInterface.', 500);
            // create object
            $object = new $class();
        }
        return $object;
    }

    /**
     * Returns the applicable output handler for this request
     * @return object
     * */
    protected function getOutput() {
        static $object = null;
        if (!$object) {
            // get class
            $content_type = $this->output;
            if (is_null($content_type))
                $content_type = $this->output;
            $class = $this->getContentClass($content_type);
            // check parents
            if (!in_array('ServiceFormatInterface', class_implements($class)))
                throw new ExceptionSettings($class . ' must implement ServiceFormatInterface.', 500);
            // create object
            $object = new $class();
        }
        return $object;
    }

    /**
     * Return the applicable template for this request
     * @return object
     * */
    protected function getTemplate() {
        static $object = null;
        if (!$object) {
            $template_file = $this->template;
            $object = new WebTemplate($template_file, WebTemplate::PHP_FILE);
            $object->setVariable('service', $this); // TODO: does this violate simplicity?
        }
        return $object;
    }

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
            throw new ExceptionSettings('Attempting to use unknown content type: ' . $content_type, 500);
    }

}