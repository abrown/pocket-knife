<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * StorageJson
 * Stores JSON records in a JSON file on the local server; uses indexes
 */
class StorageJson implements StorageInterface{
    
    /**
     * Whether the request changes the data
     * @var boolean 
     */
    public $isChanged = false;
    
    /**
     * Stores the path to the JSON database
     * @var string 
     */
    public $location;
    
    /**
     * Structure model for each record
     * @var mixed 
     */
    public $schema;
    
    /**
     * Database data
     * @var mixed 
     */
    protected $data;
    
    /**
     * Constructor
     * @param type $configuration 
     */
    public function __construct($configuration){
        if( !$configuration ) throw new ExceptionConfiguration('StorageJson requires a configuration', 500);
        if( !is_a($configuration, 'Configuration') ) $configuration_object = new Configuration($configuration);
        // create database if necessary
        if( !is_file($configuration_object->location) ){
            file_put_contents($configuration_object->location, '{}');
        }
        // determines what configuration must be passed
        $configuration_template = array(
            'location' => Configuration::MANDATORY | Configuration::PATH,
            'schema' => Configuration::OPTIONAL
        );
        // accepts configuration
        $configuration_object->validate($configuration_template);
        // copy configuration into this
        foreach ($this as $key => $value) {
            if (isset($configuration_object->$key))
                $this->$key = $configuration_object->$key;
        }
    }
    
    /**
     * Begins transaction
     */
    public function begin(){
        // TODO: lock records
        $json = file_get_contents($this->location);
        $this->data = json_decode($json);
    }
    
    /**
     * Completes transaction
     */
    public function commit(){
        if( $this->isChanged() ){
            $json = json_encode($this->data);
            file_put_contents($this->location, $json);
        }
    }
    
    /**
     * Rolls back transaction
     */
    public function rollback(){
        // TODO: unlock records
    }
    
    /**
     * Returns true if data has been modified
     * @return boolean 
     */
    public function isChanged(){
        return $this->isChanged;
    }
    
    /**
     * Create record
     * @param mixed $record
     * @param mixed $id 
     */
    public function create($record, $id = null){
        if( !is_null($id) ){
            $this->data->$id = $record;
        }
        else{
            $last = $this->last();
            if( is_null($last) ) $id = 1;
            elseif( is_numeric($last) ) $id = (int) $last + 1;
            else $id = $last.'$1';
            $this->data->$id = $record;
        }
        $this->isChanged = true;
        return $id;
    }
    
    /**
     * Read record
     * @param mixed $id
     * @return mixed 
     */
    public function read($id){
        if( is_null($id) ) throw new ExceptionStorage('READ action requires an ID', 400);
        if( property_exists($this->data, $id) ) return $this->data->$id;
        //elseif( array_key_exists($id, $this->data) ) return $this->data[$id];
        else throw new ExceptionStorage("READ action could not find ID '$id'", 404);
    }
    
    /**
     * Update record
     * @param mixed $record
     * @param mixed $id 
     */
    public function update($record, $id){
        if( is_null($id) ) throw new ExceptionStorage('UPDATE action requires an ID', 400);
        if( !property_exists($this->data, $id) ) throw new ExceptionStorage("UPDATE action could not find ID '$id'", 400);
        $this->data->$id = $record;
        $this->isChanged = true;
        return $this->data->$id;
    }
    
    /**
     * Delete record
     * @param mixed $id 
     */
    public function delete($id){
        if( is_null($id) ) throw new ExceptionStorage('DELETE action requires an ID', 400);
        if( !property_exists($this->data, $id) ) throw new ExceptionStorage("DELETE action could not find ID '$id'", 400);
        $record = $this->data->$id;
        unset($this->data->$id);
        $this->isChanged = true;
        return $record;
    }
    
    /**
     * Search for records
     * @param string $key
     * @param mixed $value 
     */
    public function search($key, $value){
        $found = array();
        foreach($this->data as $id => $record){
            if( $record->$key == $value ) $found[$id] = $record;
        }
        return $found;
    }
    
    /**
     * Returns all records
     * @return array
     */
    public function all(){
        return $this->data;
    }
    
    /**
     * Returns last element
     * @return mixed
     */
    public function last(){
        $last = null;
        foreach($this->data as $i => $v){
            if( $i > $last ) $last = $i;
        }
        return $last;
    }
}