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
     * MongoDB Server
     * @var Mongo 
     */
    protected $server;

    /**
     * Current MongoDB Collection (like table)
     * @var MongoCollection 
     */
    protected $collection;

    /**
     * Constructor
     * @param type $settings 
     */
    public function __construct($settings = null){
        if( !$settings || !is_a($settings, 'Settings') ) throw new ExceptionSettings('StoragePdo requires a Settings', 500);
        // determines what Settings must be passed
        $settings_template = array(
            'location' => Settings::MANDATORY,
            'port' => Settings::OPTIONAL | Settings::NUMERIC,
            'database' => Settings::MANDATORY,
            'collection' => Settings::MANDATORY,
            'username' => Settings::OPTIONAL,
            'password' => Settings::OPTIONAL
        );
        // accepts Settings
        $settings->validate($settings_template);
        // copy Settings into this
        $this->Settings = $settings;   
        // connect to server
        try{
            $url = $this->getDatabaseString($settings);
            $this->server = new Mongo($url);
            $this->collection = $this->server->selectCollection($settings->database, $settings->collection);
        }
        catch(Exception $e){
            throw new ExceptionStorage($e->getMessage(), 400);
        }
        
    }
    
    /**
     * Begins transaction
     */
    public function begin(){

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
        if( !is_null($id) ) throw ExceptionStorage('MongoDB create() cannot specify an arbitrary ID', 400);
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
        // check result
        if( is_null($item) ) throw new ExceptionStorage('Could not find ID', 404);
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
        if( !$success ) throw new ExceptionStorage('DELETE action failed: unknown reason', 400); 
        // return
        return $record;
    }
    
    /**
     * Deletes all records
     * @return boolean
     */
    public function deleteAll(){
        // delete all records
        try{
            $success = $this->collection->remove( array() );
        }
        catch(Exception $e){
            throw new ExceptionStorage($e->message, 400);
        }
        if( !$success ) throw new ExceptionStorage('DELETE action failed: unknown reason', 400); 
        // return
        return true;
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
    
    /**
     * Returns a database connection string from a given Settings
     * @return string
     */
    protected function getDatabaseString($settings){
        $url = 'mongodb://';
        if( $settings->username ) $url .= $settings->username.':';
        if( $settings->password ) $url .= $settings->password;
        $url .= ($settings->location) ? $settings->location : 'localhost';
        $url .= ($settings->port) ? ':'.$settings->port : ':27017';
        return $url;
    }
}