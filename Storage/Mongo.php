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
        // create
        $_record = (array) $record;
        try{
            $success = $this->collection->insert($_record);
        }
        catch(Exception $e){
            throw new ExceptionStorage($e->message, 400);
        }
        if( !$success ) throw new ExceptionStorage('CREATE action failed: no reason given', 400);
        // return
        return $_record['_id']->{'$id'};
    }
    
    /**
     * Read record
     * @param mixed $id
     * @return mixed 
     */
    public function read($id){
        if( is_null($id) ) throw new ExceptionStorage('READ action requires an ID', 400);
        // get record
        try{
            $item = $this->collection->findOne( array('_id' => new MongoId($id)));
        }
        catch(Exception $e){
            throw new ExceptionStorage($e->message, 400);
        }
        // return
        return to_object($item);
    }
    
    /**
     * Update record
     * @param mixed $changes
     * @param mixed $id 
     */
    public function update($changes, $id){
        if( is_null($id) ) throw new ExceptionStorage('UPDATE action requires an ID', 400);
        // read
        $record = $this->read($id);
        // change each field
        foreach($changes as $key => $value){
            $record->$key = $value;
            $this->isChanged = true;
        } 
        // save
        try{
            $success = $this->collection->update( array('_id' => new MongoId($id)), $record );
        }
        catch(Exception $e){
            throw new ExceptionStorage($e->message, 400);
        }
        if( !$success ) throw new ExceptionStorage('UPDATE action failed: unknown reason', 400); 
        // return
        return $record;
    }
    
    /**
     * Delete record
     * @param mixed $id 
     */
    public function delete($id){
        $record = $this->read($id);
        // remove
        try{
            $success = $this->collection->remove( array('_id' => new MongoId($id)) );
        }
        catch(Exception $e){
            throw new ExceptionStorage($e->message, 400);
        }
        if( !$success ) throw new ExceptionStorage('UPDATE action failed: unknown reason', 400); 
        // return
        return $record;
    }
    
    /**
     * Search for records
     * @param string $key
     * @param mixed $value 
     */
    public function search($key, $value){
        // TODO
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
        // TODO: can we even do this?
    }
}