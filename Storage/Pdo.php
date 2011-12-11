<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * StoragePdo
 * @uses StorageInterface, ExceptionStorage, Settings
 */
class StoragePdo implements StorageInterface{

    /**
     * Settings
     * @var Settings
     */
    protected $Settings;
    
    /**
     * Whether the request changes the data
     * @var boolean 
     */
    public $isChanged = false;
    
    /**
     * DB table
     * @var string
     */
    protected $table;
    
    /**
     * Primary key
     * @var string 
     */
    protected $primary;

    /**
     * Whether to use database DESCRIBE command to setup fields
     * @var <bool>
     */
    // TODO: protected $__describe = true;

    /**
     * Whether to cache the query results
     * @var <bool>
     */
    // TODO: protected $__cache = false;

    /**
     * List of foreign keys, [table=foreign_key, table2=foreign_key]
     * @var <array>
     */
    // TODO: protected $__foreign = array();

    /**
     * Constructor
     * @param Settings
     */
    public function __construct( $Settings ){
        if( !$Settings || !is_a($Settings, 'Settings') ) throw new ExceptionSettings('StoragePdo requires a Settings', 500);
        // determines what Settings must be passed
        $Settings_template = array(
            'location' => Settings::MANDATORY,
            'database' => Settings::MANDATORY,
            'username' => Settings::MANDATORY,
            'password' => Settings::MANDATORY,
            'table' => Settings::MANDATORY,
            'primary' => Settings::MANDATORY
        );
        // accepts Settings
        $Settings->validate($Settings_template);
        // copy Settings into this
        $this->Settings = $Settings;
        // oft-used vars
        $this->table = $Settings->table;
        $this->primary = $Settings->primary;
    }
    
    /**
     * Prepare for action
     */
    public function begin(){
        $this->getDatabase()->beginTransaction();
    }

    /**
     * Commit changes
     */
    public function commit(){
        $this->getDatabase()->commit();
    }
    
    /**
     * Rollback action
     */
    public function rollback(){
        $this->getDatabase()->rollback();
    }

    /**
     * Returns whether the last query changed the database
     * @return boolean
     */
    public function isChanged(){
        return $this->isChanged;
    }
    
    /**
     * Create in DB
     * @param stdClass $record
     * @param mixed $id
     * @return stdClass 
     */
    public function create($record, $id = null){
        // prepare fields
        $fields = array();
        if( $id ){ $fields[] = "`$this->primary` = :__id"; } // if ID specified
        foreach($record as $field => $value){
            $fields[] = "`$field` = :__$field";
        }
        $fields = implode(', ', $fields);
        // prepare statement
        $sql = $this->getDatabase()->prepare( "INSERT INTO `{$this->table}` SET {$fields}" );
        // bind values
        if( $id ){ $sql->bindParam( '__id', $id ); } // if ID specified
        foreach($record as $field => $value){
            if ( !is_scalar($value) ) $record->$field = json_encode($value);
            $sql->bindParam( ':__'.$field, $record->$field );
        }
        // execute
        $sql->execute();
        if( !$sql->rowCount() ){
            $error = $sql->errorInfo();
            throw new Exception('Failed to create record: '.$error[2], 400); 
        }
        // object changed
        $this->isChanged = true;
        // TODO: delete cache (for enumerate)
        // if( $this->__cache ) Cache::delete($this->getEnumerateCacheKey());
        // return
        if( $id ) return $id;
        else return $this->getDatabase()->lastInsertID();
    }

    /**
     * Read from DB
     * @param mixed $id
     * @return StdClass 
     */
    public function read($id){
        if( is_null($id) ) throw new ExceptionStorage('READ action requires an ID', 400);
        // TODO: caching
        /*
        $key = $this->getCacheKey($id);
        if( !$this->__cache || !Cache::valid($key) ){
            $result = $this->__read($query);
            if( $this->__cache ) Cache::write($key, $result);
        }
        else{
            $result = Cache::read($key);
        }
         */
        // build query
        $query = "SELECT `{$this->table}`.*\n".
                "FROM `{$this->table}`\n".
                "WHERE `{$this->table}`.`{$this->primary}` = ?";
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->bindParam(1, $id);
        $sql->execute();
        // get result
        $result = $sql->fetch(PDO::FETCH_OBJ);
        // check result
        if( $result === false ){ throw new ExceptionStorage('Could not find record to read', 404); }
        // return
        return $result;
    }

    /**
     * Update in DB
     * @param stdClass $record
     * @param mixed $id
     * @return stdClass 
     */
    public function update($record, $id){
        if( is_null($id) ) throw new ExceptionStorage('UPDATE action requires an ID', 400);
        // check if exists
        // if( !$this->exists() ){ throw new Exception('Could not find record to update', 404); }
        // TODO: delete cache
        // if( $this->__cache ) Cache::delete($this->getCacheKey($id));
        // prepare fields
        $fields = array();
        foreach($record as $field => $value){
            $fields[] = "`$field` = :__$field";
        }
        $fields = implode(', ', $fields);
        // prepare statement
        $sql = $this->getDatabase()->prepare( "UPDATE `{$this->table}` SET {$fields} WHERE `{$this->primary}` = :__id" );
        $sql->bindParam( ':__id', $id );        
        // fill fields
        foreach($record as $field => $value){
            if ( !is_scalar($value) ) $record->$field = json_encode($value);
            $sql->bindParam( ':__'.$field, $record->$field );
        }
        // execute
        $sql->execute();
        if( !$sql->rowCount() ){ 
            $error = $sql->errorInfo();
            throw new Exception('Failed to update record: '.$error[2], 400); 
        }
        // object changed
        $this->isChanged = true;
        // return changed object
        return $this->read($id);
    }

    /**
     * Delete from DB
     * @return <bool>
     */
    public function delete($id){
        $record = $this->read($id);
        // prepare statement
        $sql = $this->getDatabase()->prepare( "DELETE FROM `{$this->table}` WHERE `{$this->primary}` = ?" );
        $sql->bindParam(1, $id);
        $sql->execute();
        // assume changes
        $this->isChanged = true;
        // TODO: delete cache
        /**
        if( $this->__cache ){
            Cache::delete($this->getCacheKey());
            Cache::delete($this->getEnumerateCacheKey());
        }
         */
        // return
        return $record;
    }
    
    /**
     * Checks whether a record exists in the database
     * @param mixed $id
     * @return boolean 
     */
    public function exists($id){
        $query = "SELECT COUNT(`{$this->table}`.`{$this->primary}`)\n".
                "FROM `{$this->table}`\n".
                "WHERE `{$this->table}`.`{$this->primary}` = ?";
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->bindParam(1, $this->$id());
        $sql->execute();
        // get result
        $result = $sql->fetch();
        $result = intval($result[0]) ? true : false;
        // return
        return $result;
    }

    /**
     * Lists all records in a table
     * @return <array>
     */
    public function all($number_of_records = null, $page = null){
        // TODO: cache
        /**
        $key = $this->getEnumerateCacheKey();
        if( !$this->__cache || !Cache::valid($key) ){
            $results = $this->__enumerate($query);
            if($this->__cache) Cache::write($key, $results);
        }
        else{
            $results = Cache::read($key);
        }
         */
        // build query
        $query = "SELECT `{$this->table}`.*\n".
                "FROM `{$this->table}`";
        // paging
        if( is_int($number_of_records) ){
            $query .= "\nLIMIT ";
            if( is_int($page) ){
                $offset = (abs($page) - 1) * $number_of_records;
                $query .= "$offset, ";
            }
            $query .= $number_of_records;
        }
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->execute();
        // get result
        $results = array();
        while( $row = $sql->fetch(PDO::FETCH_OBJ) ){
            $id = $row->{$this->primary};
            $results[$id] = $row;
        }
        // return
        return $results;
    }
    
    /**
     * Deletes all records in a table
     * @return boolean 
     */  
    public function deleteAll(){
        $sql = $this->getDatabase()->prepare( "DELETE FROM `{$this->table}`" );
        $sql->execute();
        // changes
        $this->isChanged = true;
        // error
        if( !$sql->rowCount() ){ 
            $error = $sql->errorInfo();
            throw new Exception('Failed to delete all records: '.$error[2], 400); 
        }
        // return
        return true;
    }
    
    /**
     * Counts records in the table
     * @return int
     */
    public function count(){
        // build query
        $query = "SELECT COUNT(`{$this->table}`.*)\n".
                "FROM `{$this->table}`";
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->execute();  
        // get result
        $result = $sql->fetch();
        $count = intval($result[0]);
        // return
        return $count;
    }
    
    /**
     * Searches for a key-value pair in the table
     * @param string $key
     * @param any $value
     * @return array 
     */
    public function search($key, $value){
        // build query
        $query = "SELECT COUNT(`{$this->table}`.*)\n".
                "FROM `{$this->table}`\n".
                "WHERE `{$this->table}`.`$key` LIKE `%$value%' OR `{$this->table}`.`$key` = '$value'";
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->execute();
        // get result
        $results = array();
        while( $row = $sql->fetch(PDO::FETCH_OBJ) ){
            $id = $row->{$this->primary};
            $results[$id] = $row;
        }
        // return
        return $results;
                
    }
    
    /**
     * Returns the first record in the table
     * @return stdObject
     */
    public function first(){
        // build query
        $query = "SELECT `{$this->table}`.*\n".
                "FROM `{$this->table}`\n".
                "LIMIT 0, 1";
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->execute();
        // get result
        $result = $sql->fetch(PDO::FETCH_OBJ);
        // check result
        if( $result === false ){ throw new ExceptionStorage('Could not find record to read', 404); }
        // return
        return $result;
    }
    
    /**
     * Returns the last record in the table (assumes auto-increment)
     * @return stdObject
     */
    public function last(){
        // build query
        $query = "SELECT `{$this->table}`.*\n".
                "FROM `{$this->table}`\n".
                "ORDER BY `{$this->table}`.`{$this->primary}` DESC".
                "LIMIT 0, 1";
        // prepare statement
        $sql = $this->getDatabase()->prepare( $query );
        $sql->execute();
        // get result
        $result = $sql->fetch(PDO::FETCH_OBJ);
        // check result
        if( $result === false ){ throw new ExceptionStorage('Could not find record to read', 404); }
        // return
        return $result;
    }

    /**
     * With: relates an object to other objects based on a foreign key
     * Foreign keys are of the form: protected __foreign = array( 'tablename'=>'foreign_key', ...);
     * @return <Object>
     */
    /**
    public function with(){
        if( !$this->__foreign ) throw new Exception('Class is configured without a foreign key.', 500);
        // get object class
        $inflector = new Inflection( Routing::getToken(4) );
        $pluralname = $inflector->toLowerCase()->toString();
        $classname = $inflector->toSingular()->toCamelCaseStyle()->toString();
        pr($classname);
        // get id
        if( count(Routing::getTokens()) > 4 ) $id = Routing::getToken(5);
        else $id = null;
        // check new object
        $action = new AppAction($classname);
        $object = $action->getObject();
        // do foreign key
        $key = null;
        foreach($this->__foreign as $table => $_key){
            if( $table == $object->__table ) $key = $_key;
        }
        if( $key === null ) throw new Exception('No foreign key defined for '.$classname.' in '.get_class($this), 500);
        // get data
        $this->read();
        $object->{$key} = $this->id;
        $result = null;
        if( $id ){
            $object->__setID($id);
            $result = $object->read();
        }
        else{
            $result = $object->enumerate();
        }
        $this->{$pluralname} = $result;
        // return
        return $this;
    }
     * 
     */

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
     * Return PDO database instance
     * @staticvar string $instance
     * @return PDO
     */
    protected function &getDatabase(){
        static $instance = null;
        if( !$instance ) {
            // create PDO instance
            try {
                $dsn = "mysql:dbname={$this->Settings->database};host={$this->Settings->location}";
                $instance = new PDO($dsn, $this->Settings->username, $this->Settings->password);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage(), 500);
            }
        }
        return $instance;
    }

    /**
     * Displays last error information
     * @param type $sql 
     */
    static protected function debug($sql){
        pr($sql->errorInfo());
    }
}
