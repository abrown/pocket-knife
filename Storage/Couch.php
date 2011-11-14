<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * StorageCouch
 * Stores JSON records in a CouchDB
 * @uses StorageInterface, WebHttp, Configuration, ExceptionStorage
 */
class StorageCouch implements StorageInterface{
    
    /**
     * Whether the request changes the data
     * @var boolean 
     */
    public $isChanged = false;
    
    /**
     * Stores configuration values
     * @var Configuration 
     */
    public $configuration;
    
    /**
     * RESTful CouchDB URL
     * @var string
     */
    public $url;
    
    /**
     * Constructor
     * @param type $configuration 
     */
    public function __construct($configuration){
        if( !$configuration|| !is_a($configuration, 'Configuration') ) throw new ExceptionConfiguration('StoragePdo requires a configuration', 500);
        // determines what configuration must be passed
        $configuration_template = array(
            'location' => Configuration::MANDATORY,
            'port' => Configuration::OPTIONAL | Configuration::NUMERIC,
            'database' => Configuration::MANDATORY,
            'username' => Configuration::OPTIONAL,
            'password' => Configuration::OPTIONAL
        );
        // accepts configuration
        $configuration->validate($configuration_template);
        // copy configuration into this
        $this->configuration = $configuration;   
        // make url
        $this->url = 'http://'.$configuration->location.':';
        $this->url .= (@$configuration->port) ? $configuration->port : 5984;
        $this->url .= '/'.$configuration->database;
    }
    
    /**
     * Begins transaction
     */
    public function begin(){
        // test
        $json = WebHttp::request($this->url);
        $info = json_decode($json);
        //if( !$info->doc_count ) throw new ExceptionStorage('Could not connect to database', 400);
    }
    
    /**
     * Completes transaction
     */
    public function commit(){

    }
    
    /**
     * Rolls back transaction
     */
    public function rollback(){

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
        if( $id ){ 
            $method = 'PUT'; 
            $url = $this->url.'/'.$id;
        }
        else{
            $method = 'POST';
            $url = $this->url;
        }
        // make request
        $response = WebHttp::request($url, $method, json_encode($record), 'application/json');
        // check response
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        $this->isChanged = true;
        // return
        return $_response->_id;
    }
    
    /**
     * Batch-creates a list of records
     * @param array $records
     * @return array 
     */
    public function createAll($records){
        $list = new stdClass();
        $list->docs = $records;
        // make request
        $response = WebHttp::request($this->url.'/_bulk_docs', 'POST', json_encode($list), 'application/json');
        // check response
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        $this->isChanged = true;
        // return
        return $_response;
    }
    
    /**
     * Read record
     * @param mixed $id
     * @return mixed 
     */
    public function read($id){
        if( is_null($id) ) throw new ExceptionStorage('READ action requires an ID', 400);
        $response = WebHttp::request($this->url.'/'.$id, 'GET', '', 'application/json');
        // check response
        if( !$response ){
            $error = error_get_last();
            throw new ExceptionStorage($error['message'], 404);
        }
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        // return
        return $_response;
    }
    
    /**
     * Update record
     * @param mixed $changes
     * @param mixed $id 
     */
    public function update($changes, $id){
        if( is_null($id) ) throw new ExceptionStorage('UPDATE action requires an ID', 400);
        // get record
        $record = $this->read($id);
        // change each field
        foreach($changes as $key => $value){
            $record->$key = $value;
            $this->isChanged = true;
        }
        // put
        $response = WebHttp::request($this->url.'/'.$id, 'PUT', json_encode($record), 'application/json');
        // check response
        if( !$response ){
            $error = error_get_last();
            throw new ExceptionStorage($error['message'], 404);
        }
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        $this->isChanged = true;
        // update revision
        $record->_rev = $_response->_rev;
        // return
        return $record;
    }
    
    /**
     * Delete record
     * @param mixed $id 
     */
    public function delete($id){
        if( is_null($id) ) throw new ExceptionStorage('DELETE action requires an ID', 400);
        // get record
        $record = $this->read($id);
        // delete request
        $response = WebHttp::request($this->url.'/'.$id, 'DELETE', '', 'application/json', array("If-Match: {$record->_rev}"));
        // check response
        if( !$response ){
            $error = error_get_last();
            throw new ExceptionStorage($error['message'], 404);
        }
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        $this->isChanged = true;
        // return
        return $record;
    }
    
    /**
     * Deletes all records
     * @return boolean
     */
    public function deleteAll(){
        // delete entire database
        $response = WebHttp::request($this->url, 'DELETE', '', 'application/json');
        // check response
        if( !$response ){
            $error = error_get_last();
            throw new ExceptionStorage($error['message'], 404);
        }
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        $this->isChanged = true;
        // recreate database
        $response = WebHttp::request($this->url, 'PUT', '', 'application/json');
        // check response
        if( !$response ){
            $error = error_get_last();
            throw new ExceptionStorage($error['message'], 404);
        }
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        // return
        return true;
    }
    
    /**
     * Returns all records
     * @return array
     */
    public function all(){
        // get all docs
        $response = WebHttp::request($this->url.'/_all_docs?include_docs=true', 'POST', '{}', 'application/json');
        // check response
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        // add IDs
        $records = array();
        foreach($_response->rows as $row){
            $id = $row->id;
            $records[$id] = $row->doc;
        }
        // return
        return $records;     
    }
    
    /**
     * Search for records
     * @param string $key
     * @param mixed $value 
     */
    public function search($key, $value){
        // get all docs
        $search = new stdClass();
        $search->key = 'author'; // 'cme@ellisun.sw.stratus.com (Carl Ellison)';
        $response = WebHttp::request($this->url.'/_all_docs?include_docs=true', 'POST', json_encode($search), 'application/json');
        // check response
        $_response = json_decode($response);
        if( @$_response->error ) throw new ExceptionStorage($_response->reason, 400);
        // add IDs
        $records = array();
        foreach($_response->rows as $row){
            $id = $row->id;
            $records[$id] = $row->doc;
        }
        // return
        return $records;   
    }
    
    /**
     * Returns last element
     * @return mixed
     */
    public function last(){
        // TODO: 
    }
}