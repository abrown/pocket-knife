<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AppAction{

    /**
     * Class name to instantiate
     * @var <string> 
     */
    private $classname;

    /**
     * Holds instance of classname
     * @var <KAbstractObject>
     */
    private $object;

    /**
     * Method to run in instance
     * @var <string>
     */
    private $method;

    /**
     * Result of running method (will be Exception on error)
     * @var <mixed>
     */
    private $result;

    /**
     * Indicates whether the method threw an exception
     * @var <bool>
     */
    private $hasError = false;

    /**
     *
     * @param <type> $classname
     * @param <type> $method
     */
    public function __construct( $classname ){
        if( !$classname ) throw new Exception('No classname specified', 500);
        $this->classname = $classname;
    }

    /**
     * Get object instance
     * @return <type>
     */
    public function getObject(){
        if( !$this->object ){
            $_class = $this->classname;
            // check for class existence
            if( !class_exists($_class) ){
                __autoload($_class);
            }
            if( !class_exists($_class) ) throw new Exception('Could not find class '.$_class, 404);
            // instantiate
            $this->object = new $_class;
            if( !$this->object ) throw new Exception('Could not start '.$_class, 500);
        }
        return $this->object;
    }

    /**
     * Do action/method, return result
     * @param <string> $method
     * @param <mixed> $data
     * @return <mixed>
     */
    public function getResult($method, $data){
        $this->method = $method;
        $this->getObject()->__prepare();
        // special routing instructions for update
        if($method == 'update' && $data === null) $method = 'read';
        if($method == 'create' && $data === null) return null;
        // check action
        if( !method_exists($this->getObject(), $method) ) throw new Exception('Incorrect action: '.$method, 501);
        if( $method[0] == '_' ) throw new Exception('Method not allowed: '.$method, 405);
        // do action
        try{
            // bind to object
            $this->bindData($data);
            // and pass to method as parameter
            $this->result = $this->getObject()->$method($data);
            $this->getObject()->__commit();
        }
        catch(Exception $e){
            $this->getObject()->__rollback(); // rollback if error found and pass exception up
            throw new Exception($e->getMessage(), $e->getCode());
        }
        // clean result (strip vertical tabs)
        $this->clean($this->result);
        // return
        return $this->result;
    }

    /**
     * Wrapper for getResult, used by SOAP
     * @param <string> $action
     * @param <array> $data
     * @return <type>
     */
    public function __call($action, $data){
        return $this->getResult($action, $data);
    }

    /**
     * Binds data to instance object
     * Object MUST have _bind and _setID methods
     * @param <mixed> $data
     */
    public function bindData($data){
        // if data is scalar, should be an id
        if( is_scalar($data) ){
            $this->getObject()->__setID($data);
        }
        // bind data to object properties
        else{
            $this->getObject()->__bind($data);
        }
    }

    /**
     * Strip vertical tabs
     * @param <mixed> $data
     */
    public function clean(&$data){
        if( is_string($data) ){
            $data = str_replace("\x0B", '', $data);
        }
        elseif( is_object($data) || is_array($data) ){
            foreach($data as &$item){
                $this->clean($item);
            }
        }
    }
}