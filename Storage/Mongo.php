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
        // check for Mongo
        if( !class_exists('Mongo', false) ){
            throw new Error('Mongo storage is not available because the Mongo PHP extension is not installed. See "http://php.net/manual/en/mongo.installation.php" for instructions.', 500);
        }
        // check that settings are of correct type
        if( !$settings || !is_a($settings, 'Settings') ) throw new Error('StoragePdo requires a Settings', 500);
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
        catch(Error $e){
            throw new Error($e->getMessage(), 400);
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
        if( !is_null($id) ) throw Error('MongoDB create() cannot specify an arbitrary ID', 400);
        // create
        $_record = (array) $record;
        try{
            $success = $this->collection->insert($_record);
        }
        catch(Error $e){
            throw new Error($e->message, 400);
        }
        if( !$success ) throw new Error('CREATE action failed: no reason given', 400);
        // return
        return $_record['_id']->{'$id'};
    }
    
    /**
     * Read record
     * @param mixed $id
     * @return mixed 
     */
    public function read($id){
        if( is_null($id) ) throw new Error('READ action requires an ID', 400);
        // get record
        try{
            $item = $this->collection->findOne( array('_id' => new MongoId($id)));
        }
        catch(Error $e){
            throw new Error($e->message, 400);
        }
        // check result
        if( is_null($item) ) throw new Error('Could not find ID', 404);
        // return
        return to_object($item);
    }
    
    /**
     * Update record
     * @param mixed $changes
     * @param mixed $id 
     */
    public function update($changes, $id){
        if( is_null($id) ) throw new Error('UPDATE action requires an ID', 400);
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
        catch(Error $e){
            throw new Error($e->message, 400);
        }
        if( !$success ) throw new Error('UPDATE action failed: unknown reason', 400); 
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
        catch(Error $e){
            throw new Error($e->message, 400);
        }
        if( !$success ) throw new Error('DELETE action failed: unknown reason', 400); 
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
        catch(Error $e){
            throw new Error($e->message, 400);
        }
        if( !$success ) throw new Error('DELETE action failed: unknown reason', 400); 
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
    public function all($number_of_records = null, $page = null){
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
     * Returns a count of all elements
     * @return int
     */
    public function count(){
        // TODO:
    }
    
    /**
     * Returns first element
     * @return mixed
     */
    public function first(){
        // TODO:
    }
    
    /**
     * Returns last element
     * @return mixed
     */
    public function last(){
        // TODO: 
    }
    
    /**
     * Returns whether the specified ID exists
     * @param type $id 
     * @return boolean
     */
    public function exists($id){
        // TODO:
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