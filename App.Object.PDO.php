<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AppObjectPDO extends AppObjectAbstract{

    /**
     * DB table
     * @var <string>
     */
    protected $__table;

    /**
     * Whether to use database DESCRIBE command to setup fields
     * @var <bool>
     */
    protected $__describe = true;

    /**
     * Whether to cache the query results
     * @var <bool>
     */
    protected $__cache = false;

    /**
     * Constructor
     * @param <mixed> $id Object ID
     */
    public function __construct( $id = null ){
        // setup fields, especially if using describe
        $this->getFields();
        // set ID
        if( !is_null($id) ) $this->__setID($id);
    }

    /**
     * Prepare for action
     */
    public function __prepare(){
        $this->getDatabase()->beginTransaction();
    }

    /**
     * Commit changes
     */
    public function __commit(){
        $this->getDatabase()->commit();
    }
    
    /**
     * Rollback action
     */
    public function __rollback(){
        $this->getDatabase()->rollback();
    }

    /**
     * Checks whether the object exists
     * @return <bool>
     */
    public function exists(){
        $query = array(
            'select' => "SELECT COUNT(`{$this->__table}`.`{$this->__primary}`)",
            'from' => "FROM `{$this->__table}`",
            'where' => "WHERE `{$this->__table}`.`{$this->__primary}` = ?"
        );
        // callback (modify query)
        $this->beforeExists($query);
        // check
        $result = $this->__exists($query);
        // callback (modify result)
        $this->afterExists($result);
        // return
        return $result;
    }

    /**
     * Get object existence from database
     * @return <bool>
     */
    private function __exists($query){
        $query = $this->toSql($query);
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->bindParam(1, $this->__getID());
        $sql->execute();
        // get result
        $result = $sql->fetch();
        $result = intval($result[0]) ? true : false;
        // return
        return $result;
    }

    /**
     * List All
     * @return <array>
     */
    public function enumerate(){
        $key = $this->getEnumerateCacheKey();
        $query = array(
            'select' => "SELECT `{$this->__table}`.*",
            'from' => "FROM `{$this->__table}`",
        );
        // callback (modify query)
        $this->beforeEnumerate($query);
        // check cache
        if( !$this->__cache || !Cache::valid($key) ){
            $results = $this->__enumerate($query);
            if($this->__cache) Cache::write($key, $results);
        }
        else{
            $results = Cache::read($key);
        }
        // callback (modify results)
        $this->afterEnumerate($results);
        // return
        return $results;
    }

    /**
     * List All (from DB)
     * @param <array> $query
     * @return <array>
     */
    private function __enumerate($query){
        $query = $this->toSql($query);
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->execute();
        // get result
        $results = array();
        while( $row = $sql->fetch(PDO::FETCH_ASSOC) ){
            $results[] = $this->__bind($row)->toClone();
        }
        // return
        return $results;
    }

    /**
     * Create
     * @return <mixed> ID
     */
    public function create(){
        $this->beforeCreate();
        // create
        $id = $this->__create();
        $this->__setID($id);
        // delete cache (for enumerate)
        if( $this->__cache ) Cache::delete($this->getEnumerateCacheKey());
        // callback
        $this->afterCreate();
        // return
        return $id;
    }

    /**
     * Create (in DB)
     * @return <mixed> ID
     */
    private function __create(){
        // make field sql
        $fields = array();
        foreach($this->getFields() as $field){
            $_token = ':'.$field;
            if( $this->isEmpty($field) && !$this->autoFill($field) ) continue;
            else $fields[] = "`$field` = $_token";
        }
        $fields = implode(', ', $fields);
        // prepare statement
        $sql = $this->getDatabase()->prepare( "INSERT INTO `{$this->_table}` SET {$fields}" );
        // bind values
        foreach($this->getFields() as $field){
            $_token = ':'.$field;
            if( $this->isEmpty($field) && !$this->autoFill($field, true) ) continue;
            $sql->bindParam( $_token, $this->$field );
        }
        // execute
        $sql->execute();
        if( !$sql->rowCount() ){ throw new Exception('Failed to create record', 400); }
        // object changed
        $this->__changed = true;
        // return
        return $this->getDatabase()->lastInsertID();
    }

    /**
     * Read
     * @return <Object>
     */
    public function read(){
        $key = $this->getCacheKey();
        $query = array(
            'select' => "SELECT `{$this->__table}`.*",
            'from' => "FROM `{$this->__table}`",
            'where' => "WHERE `{$this->__table}`.`{$this->__primary}` = ?"
        );
        // callback (modify query)
        $this->beforeRead($query);
        // check cache
        if( !$this->__cache || !Cache::valid($key) ){
            $result = $this->__read($query);
            if( $this->__cache ) Cache::write($key, $result);
        }
        else{
            $result = Cache::read($key);
        }
        // check result
        if( $result === false ){ throw new Exception('Could not find record to read', 404); }
        // bind
        $this->__bind($result);
        // callback
        $this->afterRead();
        // return
        return $this;
    }

    /**
     * Read (from DB)
     * @param <array> $query
     * @return <array>
     */
    private function __read($query){
        $query = $this->toSql($query);
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->bindParam(1, $this->__getID());
        $sql->execute();
        // get result
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        // return
        return $result;
    }

    /**
     * Update
     * @return <bool>
     */
    public function update(){
        $this->beforeUpdate();
        // check if exists
        if( !$this->exists() ){ throw new Exception('Could not find record to update', 404); }
        // delete cache
        if( $this->__cache ) Cache::delete($this->getCacheKey());
        // update
        $result = $this->__update();
        // callback
        $this->afterUpdate();
        // return
        return $this;
    }

    /**
     * Update (in DB)
     * @return <bool>
     */
    private function __update(){
        // make field sql
        $fields = array();
        foreach($this->getFields() as $field){
            $_token = ':'.$field;
            if( $this->isEmpty($field) && !$this->autoFill($field) ) continue;
            else $fields[] = "`$field` = $_token";
        }
        $fields = implode(', ', $fields);
        // prepare statement
        $sql = $this->getDatabase()->prepare( "UPDATE `{$this->__table}` SET {$fields} WHERE `{$this->__primary}` = :_object_identifier" );
        // bind values
        foreach($this->getFields() as $field){
            $_token = ':'.$field;
            if( $this->isEmpty($field) && !$this->autoFill($field) ) continue;
            $sql->bindParam( $_token, $this->$field );
        }
        // bind identifier
        $sql->bindParam( ':_object_identifier', $this->__getID() );
        // execute
        $sql->execute();
        if( !$sql->rowCount() ){ throw new Exception('Failed to update record', 400); }
        // object changed
        $this->__changed = true;
        // return
        return $this;
    }

    /**
     * Delete
     * @return <bool>
     */
    public function delete(){
        $this->beforeDelete();
        // check if exists
        if( !$this->exists() ){ throw new Exception('Could not find record to delete', 404); }
        // delete cache
        if( $this->__cache ){
            Cache::delete($this->getCacheKey());
            Cache::delete($this->getEnumerateCacheKey());
        }
        // update
        $result = $this->__delete();
        // callback
        $this->afterDelete();
        // return
        return $result;
    }

    /**
     * Delete (in DB)
     * @return <bool>
     */
    private function __delete(){
        // prepare statement
        $sql = $this->getDatabase()->prepare( "DELETE FROM `{$this->__table}` WHERE `{$this->__primary}` = ?" );
        $sql->bindParam(1, $this->__getID());
        $sql->execute();
        // assume changes
        $this->__changed = true;
        // return
        return true;
    }

    /**
     * Callbacks
     */
    protected function beforeExists(&$sql){ /* overload */ }
    protected function afterExists(&$result){ /* overload */ }
    protected function beforeEnumerate(&$sql){ /* overload */ }
    protected function afterEnumerate(&$results){ /* overload */ }
    protected function beforeCreate(){ /* overload */ }
    protected function afterCreate(){ /* overload */ }
    protected function beforeRead(&$sql){ /* overload */ }
    protected function afterRead(){ /* overload */ }
    protected function beforeUpdate(){ /* overload */ }
    protected function afterUpdate(){ /* overload */ }
    protected function beforeDelete(){ /* overload */ }
    protected function afterDelete(){ /* overload */ }

    /**
     * Gets field names from DB
     * @return <array>
     */
    protected function describe(){
        // prepare statement
        $sql = $this->getDatabase()->query( "DESCRIBE `{$this->__table}`" );
        if( !$sql ){ throw new Exception('Could not find table to describe', 404); }
        foreach( $sql as $row ){
            $property = $row['Field'];
            $this->$property = null;
            $this->__fields[] = $property;
        }
        // check if any extra fields have been defined in class
        $properties = array_keys(get_public_vars($this));
        foreach($properties as $property){
            if( !in_array($property, $this->__fields) ){
                $this->$property = null;
                $this->__fields[] = $property;
            }
        }
        // return
        return $this->__fields;
    }

    /**
     * Overriden getFields to use DB DESCRIBE functionality
     * @return <array>
     */
    protected function getFields(){
        if( !$this->__fields ){
            // get fields using Cache and DB tables
            if( $this->__describe ){
                $key = get_class($this).'-'.'Fields';
                if( Cache::valid($key) ){
                    $this->__fields = Cache::read($key);
                }
                else{
                    $this->describe();
                    Cache::write( $key, $this->__fields );
                }
            }
            // get public fields
            else{
                $this->__fields = array_keys(get_public_vars($this));
            }
        }
        return $this->__fields;
    }

    /**
     * Join SQL array
     * @param <array> $array
     */
    private function toSql($array){
        if( !is_array($array) ) throw new Exception('Incorrect SQL', 500);
        $sql = array();
        // add select
        if( isset($array['select']) ) $sql[] = $array['select'];
        else throw new Exception('Incorrect SQL: select');
        // add from
        if( isset($array['from']) ) $sql[] = $array['from'];
        else throw new Exception('Incorrect SQL: from');
        // add joins
        if( isset($array['join']) ) $sql[] = $array['join'];
        if( isset($array['joins']) && is_array($array['joins']) ) $sql = array_merge($sql, $array['joins']);
        // add where
        if( isset($array['where']) ) $sql[] = $array['where'];
        // add order
        if( isset($array['order']) ) $sql[] = $array['order'];
        // add limit
        if( isset($array['limit']) ) $sql[] = $array['limit'];
        // return
        return implode(" \n", $sql);
    }

    /**
     * Return PDO database instance
     * @staticvar string $instance
     * @return PDO
     */
    private function &getDatabase(){
        static $instance = null;
        if( !$instance ) {
            // get configuration
            $config = Configuration::getInstance();
            // create PDO instance
            try {
                $dsn = "mysql:dbname={$config['db']['name']};host={$config['db']['host']}";
                $instance = new PDO($dsn, $config['db']['username'], $config['db']['password']);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage(), 500);
            }
        }
        return $instance;
    }
}
