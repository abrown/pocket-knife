<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ServiceMap
 * @uses Configuration, WebRouting, WebHttp, WebTemplate, ExceptionFile, ExceptionConfiguration 
 */
class ServiceMap extends Service {

    /**
     * Maps storage methods to requests; used for complex services
     * @example $this->storage_map = array('posts/read'=>array('type'=>'s3', ...), 'posts/update'=>...);
     * @var array
     * */
    public $storage_map;

    /**
     * Maps input data types to requests; used for complex services
     * @example $this->input_map = array('posts/delete'=>'Json', ...);
     * @var array
     * */
    public $input_map;

    /**
     * Maps output data types to requests; used for complex services
     * @example $this->output = 'Json'; $this->output_map('posts/read'=>'Html', ...);
     * @var array
     * */
    public $output_map;

    /**
     * Maps templates to requests, used for complex services
     * @var array
     * */
    public $template_map;

    /**
     * Constructor
     * @param Configuration $configuration 
     */
    public function __construct($configuration) {
        // determines what configuration must be passed
        $configuration_template = array(
            'acl' => Configuration::MANDATORY,
            'storage_map' => Configuration::OPTIONAL | Configuration::MULTIPLE,
            'input_map' => Configuration::OPTIONAL | Configuration::MULTIPLE,
            'output_map' => Configuration::OPTIONAL | Configuration::MULTIPLE,
            'template_map' => Configuration::OPTIONAL | Configuration::MULTIPLE,
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
     * Returns the storage configuration for this request
     * @var array
     * */
    protected function getStorage() {
        static $object = null;
        if (!$object) {
            $configuration = ( $this->storage_map ) ? $this->getMapped($this->storage_map) : $this->storage;
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
            $content_type = ( $this->input_map ) ? $this->getMapped($this->input_map) : $this->input;
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
            $content_type = ( $this->output_map ) ? $this->getMapped($this->output_map) : $this->output;
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
            $template_file = ( $this->template_map ) ? $this->getMapped($this->template_map) : $this->template;
            $object = new WebTemplate($template_file);
        }
        return $object;
    }

    /**
     * Returns last value from a list of request signatures (e.g. posts/23/read) mapped to values
     * @return mixed
     * */
    protected function getMapped($list) {
        list($class, $id, $method) = $this->getRouting();
        $class = strtoupper($class);
        $id = strtoupper($id);
        $method = strtoupper($method);
        // walk backwards through list
        end($list);
        while (prev($list) !== false) {
            $key = key($list);
            $key = strtoupper($key);
            list($_class, $_id, $_method) = explode('/', $key);
            // compare
            if (
                    ($_class === '*' || $class === $_class) &&
                    ($_id === '*' || $id === $_id) &&
                    ($_method === '*' || $method === $_method)
            ) {
                // return
                return current($list);
            }
        }
        // return
        return null;
    }
}