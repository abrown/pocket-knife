<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Service
 * @uses Configuration, WebRouting, WebHttp, WebTemplate, ExceptionFile, ExceptionConfiguration 
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
    public $storage = 'Json';

    /**
     * Defines the input data type for the request; should be a class implementing ServiceType
     * @example $this->input = 'Xml';
     * @var string
     * */
    public $input = 'Html';

    /**
     * Defines the output data and content-type of the response; should be a class implementing ServiceType
     * @example $this->output = 'Html';
     * @var string
     * */
    public $output = 'Html';

    /**
     * Template to apply to the output after processing
     * @var string
     * */
    public $template;

    /**
     * Defines the class to interact with; typically set by WebRouting, but can be overriden manually
     * @example $this->class = 'post';
     * @var string
     * */
    public $class;

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
     * var mixed
     * */
    public $id;

    /**
     * Constructor
     * @param Configuration $configuration 
     */
    public function __construct($configuration) {
        // determines what configuration must be passed
        $configuration_template = array(
            'acl' => Configuration::MANDATORY,
            'storage' => Configuration::OPTIONAL | Configuration::MULTIPLE,
            'input' => Configuration::OPTIONAL | Configuration::STRING,
            'output' => Configuration::OPTIONAL | Configuration::STRING,
            'template' => Configuration::OPTIONAL | Configuration::PATH,
            'class' => Configuration::OPTIONAL | Configuration::STRING,
            'object' => Configuration::OPTIONAL | Configuration::MULTIPLE,
            'method' => Configuration::OPTIONAL | Configuration::STRING,
            'id' => Configuration::OPTIONAL
        );
        // accepts configuration
        if (!$configuration || !is_a($configuration, 'Configuration'))
            throw new ExceptionConfiguration('Incorrect configuration given.', 500);
        $configuration->validate($configuration_template);
        // copy configuration into this
        foreach ($this as $key => $value) {
            if (isset($configuration->$key))
                $this->$key = $configuration->$key;
        }
    }

    /**
     * Handles requests, creates instances, and returns result
     */
    public function execute() {
        // what we act upon
        list($class, $id, $method) = $this->getRouting();
        if (!$this->{'class'})
            $this->{'class'} = $class;
        if (!$this->method)
            $this->method = $method;
        if (!$this->id)
            $this->id = $id;
        try {
            // apply acl
            if (!$this->allowed('class', $class))
                throw new ExceptionAccess('Class is not allowed', 403);
            if (!$this->allowed('method', $method))
                throw new ExceptionAccess('Method is not allowed', 403);
            //if( !$this->allowed('id', $id) ) throw new ExceptionAccess('ID is not allowed', 403);
            // get instance
            if (!$this->object) {
                if (!class_exists($this->{'class'}))
                    throw new ExceptionFile('Could not find class: ' . $this->{'class'}, 404);
                if (!in_array('ServiceObject', class_implements($this->{'class'})))
                    throw new ExceptionConfiguration('Class must implement ServiceObject.', 500);
                $this->object = new $this->{'class'}($id);
            }
            // set storage (if necessary)
            if ($this->storage || $this->storage_map) {
                $this->object->setStorage($this->getStorage());
            }
            // get input data
            $input = $this->getInput()->getData();
            // do method
            if (!method_exists($this->object, $this->method))
                throw new ExceptionConfiguration('Method does not exist', 404);
            $result = $this->object->{$this->method}($input);
            // get output data
            $this->getOutput()->setData($result);
            // apply template (if necessary)
            if ($this->template || $this->template_map) {
                $this->getTemplate()->replace('content', $this->getOutput()->getResponse());
                $this->getOutput()->setResponse($this->getTemplate()->toString());
            }
            // send data
            $this->getOutput()->send();
        } catch (Exception $e) {
            $this->getOutput()->setData($e);
            $this->getOutput()->sendError();
        }
    }

    /**
     * Returns object/method/id for this Http request
     * @return array
     * */
    public function getRouting() {
        $tokens = WebRouting::getTokens();
        // parse
        reset($tokens);
        $object = current($tokens);
        $id = next($tokens);
        $method = next($tokens);
        if (!$method) {
            // case: object/method
            if ($id && !is_numeric($id))
                $action = $id;
            // case: object/numeric_id
            else if ($id) {
                $http_method = WebRouting::getMethod(); // use HTTP method
                if ($http_method)
                    $$method = $http_method;
                else
                    $method = 'read';
            }
            // case: entities/
            else
                $method = 'read';
        }
        // check ID type
        if (is_numeric($id))
            $id = (int) $id;
        // return
        return array($object, $id, $method);
    }

    /**
     * Checks whether the specified type and value are allowed by the configured ACL
     * @return boolean
     * */
    public function allowed($type, $value) {
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
                $r['class'] = explode(',', $matches[3]);
                $r['class'] = array_map('trim', $r['class']);
                // test
                if (
                        ($r[$type][0] === '*' || in_array($value, $r[$type])) &&
                        !$r['deny']
                ) {
                    return true;
                }
            } else {
                throw new ExceptionConfiguration('Poorly worded ACL rule: ' . $rule, 500);
            }
        } while (prev($list) !== false);
        // return
        return false;
    }

    /**
     * Returns the storage configuration for this request
     * @var array
     * */
    protected function getStorage() {
        static $object = null;
        if (!$object) {
            $configuration = $this->storage;
            if (!array_key_exists('type', $configuration))
                throw new ExceptionConfiguration('Storage type is not defined', 500);
            $class = $configuration['type'];
            unset($configuration['type']);
            $object = new $class($configuration);
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
            $content_type = $this->input;
            if (is_null($content_type))
                $content_type = $this->input;
            $class = $this->getContentClass($content_type);
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
            $content_type = $this->output;
            if (is_null($content_type))
                $content_type = $this->output;
            $class = $this->getContentClass($content_type);
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
            $object = new WebTemplate($template_file);
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
            throw new ExceptionConfiguration('Attempting to use unknown content type: ' . $content_type, 500);
    }
}