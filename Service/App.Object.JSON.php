<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AppObjectJSON extends AppObjectAbstract{

    /**
     * Path to valid JSON file
     * @var <string>
     */
    protected $__path;

    /**
     * Complete database instance
     * @var <mixed>
     */
    protected $__db;

    /**
     * Whether action has changed the record
     * @var <boolean>
     */
    protected $__changed;

    /**
     * Stores primary keys in an index for faster searching in search/index
     * @var <array>
     */
    protected $__index = null;

    /**
     * Constructor: access DB
     */
    public function __construct(){
        if( !$this->__getPath() ) throw new Exception('Database path not set.', 404);
        // get db from file
        if( is_file($this->__getPath()) ){
            $json = file_get_contents($this->__getPath());
            // could not access file
            if( $json === false ) throw new Exception('Could not access database file.', 404);
            // empty file
            elseif( !$json ) $this->__db = array();
            // decode
            else{
                $this->__db = json_decode($json);
                // failed to decode
                if( is_null($this->__db) ) throw new Exception('Database file is corrupted.', 404);
            }
        }
        // assume db file not created
        else{
            $this->__db = array();
        }
    }

    /**
     * Set database file path
     * @param <string> $path
     */
    public function __setPath($path){
        $this->__path = $path;
    }

    /**
     * Get database file path
     * @return <string>
     */
    public function __getPath(){
        return $this->__path;
    }

    /**
     * Search for an item in the database by its primary key
     * @param <type> $id
     * @return <mixed>
     */
    public function search($id){
        $index = $this->index($id);
        if( is_null($index) ){
            return null;
        }
        else{
            return $this->__db[$index];
        }
    }

    /**
     * Returns index of object in database
     * @param <int> $id
     * @return string
     */
    public function index($id){
        // use __index if possible
        if( $this->__index && array_key_exists($id, $this->__index) ){
            $index = $this->__index[$id];
            return $index;
        }
        // or search through database
        else{
            foreach($this->__db as $index => $item){
                // cache
                $this->__index[$item->{$this->__primary}] = $index;
                // check
                if( $item->{$this->__primary} == $id ) return $index;
            }
        }
        return null;
    }

    /**
     * Test item existence
     * @return <boolean>
     */
    public function exists(){
        $id = $this->__getID();
        $result = $this->search($id);
        return ($result) ? true : false;
    }

    /**
     * List objects
     * @return <array>
     */
    public function enumerate(){
        return $this->__db;
    }

    /**
     * Create item
     * @return <int>
     */
    public function create(){
        // set new id
        if( !$this->__getID() ){
            $id = 1;
            if( $last = end($this->__db) && $last_id = $last->{$this->__primary} ){
                if( is_string($last_id) ) $id = (string) md5(rand());
                else $id = $last_id + 1;
            }
            $this->__setID($id);
        }
        // autofill
        foreach($this->getFields() as $field){
            if( $this->isEmpty($field) ) $this->autoFill($field, true);
        }
        // add to db
        $length = array_push($this->__db, $this->toClone());
        if( !$length ){ throw new Exception('Failed to create record', 400); }
        // changed
        $this->__changed = true;
        // return
        return $this->__getID();
    }

    /**
     * Read an object
     * @return AppObjectJSON
     */
    public function read(){
        if( !$this->exists() ){ throw new Exception('Could not find record to read', 404); }
        // return
        $this->__bind( $this->search($this->__getID()) );
        return $this;
    }

    /**
     * Update an object
     * @return AppObjectJSON
     */
    public function update(){
        if( !$this->exists() ){ throw new Exception('Could not find record to update', 404); }
        // autofill
        foreach($this->getFields() as $field){
            if( $this->isEmpty($field) ) $this->autoFill($field, false);
        }
        // add to db
        $index = $this->index($this->__getID());
        $this->__db[$index] = $this->toClone();
        // object changed
        $this->__changed = true;
        // return
        return $this;
    }

    /**
     * Delete object from database
     * @return <boolean>
     */
    public function delete(){
        if( !$this->exists() ){ throw new Exception('Could not find record to delete', 404); }
        // delete
        $index = $this->index( $this->__getID() );
        unset( $this->__db[$index] );
        $this->__reset();
        // object changed
        $this->__changed = true;
        // return
        return true;
    }

    /**
     * Prepare for incoming action
     */
    public function __prepare(){
        // nothing yet
    }

    /**
     * Save db to file
     */
    public function __commit(){
        if( !$this->__isChanged() ) return;
        // to clone
        $bytes = file_put_contents($this->__getPath(), json_encode($this->__db));
        if( !$bytes ){ throw new Exception('Could not save to JSON database file.', 500); }
    }

    /**
     * Rollback changes on error
     */
    public function __rollback(){
        // do nothing
    }

    /**
     * Resets index after changes made to __db
     */
    public function __reset(){
        $this->__index = null;
    }
}