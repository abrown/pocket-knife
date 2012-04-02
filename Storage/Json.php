<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Stores JSON records in a JSON file on the local server; uses indexes
 * @uses StorageInterface, Error, Error
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
     * @param Settings $settings 
     */
    public function __construct($settings){        
        // check settings
        if( !$settings || !is_a($settings, 'Settings') ) throw new Error('StorageJson requires a Settings object', 500);
        // determines what settings must be passed
        $settings_template = array(
            'location' => Settings::MANDATORY | Settings::STRING,
            'schema' => Settings::OPTIONAL
        );
        // validate settings
        $settings->validate($settings_template);
        // copy settings into this object
        foreach ($this as $key => $value) {
            if (isset($settings->$key))
                $this->$key = $settings->$key;
        }
        // create database if necessary
        $this->data = new stdClass();
        if( !is_file($this->location) ){
        	file_put_contents($this->location, '{}');
        }
        // ensure location is accessible
        elseif( !is_writable($this->location) ){
        	throw new Error("The file '$this->location' is not writable", 500);
        }
        // read database
        else{
        	$json = file_get_contents($this->location);
        	$this->data = json_decode($json);
        }
    }
    
    /**
     * Begins transaction
     */
    public function begin(){
        // TODO: lock records
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
        if( is_null($id) ) throw new Error('READ action requires an ID', 400);
        if( property_exists($this->data, $id) ) return $this->data->$id;
        //elseif( array_key_exists($id, $this->data) ) return $this->data[$id];
        else throw new Error("READ action could not find ID '$id'", 404);
    }
    
    /**
     * Update record
     * @param mixed $record
     * @param mixed $id 
     */
    public function update($record, $id){
        if( is_null($id) ) throw new Error('UPDATE action requires an ID', 400);
        if( !property_exists($this->data, $id) ) throw new Error("UPDATE action could not find ID '$id'", 400);
        // change each field
        foreach($record as $key => $value){
            $this->data->$id->$key = $value;
            $this->isChanged = true;
        } 
        return $this->data->$id;
    }
    
    /**
     * Deletes a record
     * @param mixed $id 
     */
    public function delete($id){
        if( is_null($id) ) throw new Error('DELETE action requires an ID', 400);
        if( !property_exists($this->data, $id) ) throw new Error("DELETE action could not find ID '$id'", 400);
        $record = $this->data->$id;
        unset($this->data->$id);
        $this->isChanged = true;
        return $record;
    }
    
    /**
     * Tests whether an object with the given ID exists
     * @param mixed $id
     * @return boolean 
     */
    public function exists($id){
        return property_exists($this->data, $id);
    }
    
    /**
     * Returns all records
     * @return array
     */
    public function all($number_of_records = null, $page = null){
        return $this->data;
    }
    
    /**
     * Deletes all records
     * @return boolean
     */
    public function deleteAll(){
        $this->data = new stdClass();
        $this->isChanged = true;
        return true;
    }
    
    /**
     * Returns the number of items
     * @return int 
     */
    public function count(){
        return count( (array) $this->data );
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
     * Returns the first element
     * @return mixed
     */
    public function first(){
        foreach($this->data as $i => $v){
            return $v;
        }
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
    
    /**
     * Returns last time the database was modified in unix-time
     * @return int
     */
    public function getLastModified(){
    	return filectime($this->location);
    }
}