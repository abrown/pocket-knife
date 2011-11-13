<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * StorageMongo
 * Uses MongoDB native driver to connect with running MongoDB server
 */
class StorageMongo implements StorageInterface{
        
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
     * MongoDB Server
     * @var Mongo 
     */
    protected $server;
    protected $collection;
    
    /**
     * Database data
     * @var mixed 
     */
    protected $data;
    
    /**
     * Constructor
     * @param type $configuration 
     */
    public function __construct($configuration = null){
 
    }
    
    /**
     * Begins transaction
     */
    public function begin(){
        $this->server = new Mongo();
        $this->collection = $this->server->selectCollection('local', 'posts');
    }
    
    /**
     * Completes transaction
     */
    public function commit(){
        if( $this->isChanged() ){

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
        $_record = (array) $record;
        try{
            $status = $this->collection->insert($_record);
        }
        catch(Exception $e){
            throw new ExceptionStorage($e->message, 400);
        }
        return $status['_id']->{'$id'};
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
        // change each field
        foreach($record as $key => $value){
            $this->data->$id->$key = $value;
            $this->isChanged = true;
        } 
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
        $out = array();
        $cursor = $this->collection->find();
        foreach($cursor as $item){
            $_item = to_object($item);
            $id = $_item->_id->{'$id'};
            $out[$id] = $_item;
        }
        return $out;
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