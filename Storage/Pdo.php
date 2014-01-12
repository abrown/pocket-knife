<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * StoragePdo
 * @uses StorageInterface, Error, Settings
 */
class StoragePdo implements StorageInterface {

    /**
     * PDO driver to use: mysql, sqlite...
     * @var string
     */
    public $driver = 'mysql';

    /**
     * Database location
     * @var string
     */
    public $location;

    /**
     * Database name
     * @var string 
     */
    public $database;

    /**
     * Database username
     * @var string 
     */
    public $username;

    /**
     * Database password
     * @var string
     */
    public $password;

    /**
     * Database table
     * @var string
     */
    public $table;

    /**
     * Primary key
     * @var string 
     */
    public $primary;

    /**
     * Whether the request changes the data
     * @var boolean 
     */
    protected $isChanged = false;

    /**
     * List of fields in this table
     * @var array
     */
    protected $fields = array();

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
    public function __construct($settings) {
        // validate
        BasicValidation::with($settings)
                ->isSettings()
                ->withProperty('driver')->oneOf('mysql', 'sqlite')
                ->upAll()
                ->withProperty('location')->isString()
                ->upAll()
                ->withProperty('database')->isString()
                ->upAll()
                ->withProperty('username')->isString()
                ->upAll()
                ->withProperty('password')->isString()
                ->upAll()
                ->withProperty('table')->isString()
                ->upAll()
                ->withProperty('primary')->isString();
        // import settings
        $settings->copyTo($this);
    }

    /**
     * Prepare for action
     */
    public function begin() {
        if (!$this->getDatabase()->inTransaction()) {
            $this->getDatabase()->beginTransaction();
        }
    }

    /**
     * Commit changes
     */
    public function commit() {
        $this->getDatabase()->commit();
    }

    /**
     * Rollback action
     */
    public function rollback() {
        $this->getDatabase()->rollback();
    }

    /**
     * Returns whether the last query changed the database
     * @return boolean
     */
    public function isChanged() {
        return $this->isChanged;
    }

    /**
     * Create in DB
     * @param stdClass $record
     * @param mixed $id
     * @return stdClass 
     */
    public function create($record, $id = null) {
        // force to object
        if (!is_object($record)) {
            $record = $this->forceToSchema($record);
        }
        // build columns and values
        $_columns = array();
        $_placeholders = array();
        if ($id) {
            $record->{$this->primary} = $id;
        }
        foreach ($record as $column => $value) {
            $_columns[] = $column;
            $_placeholders[] = ":__$column";
        }
        $columns = implode(', ', $_columns);
        $placeholders = implode(', ', $_placeholders);
        // prepare statement
        $sql = $this->prepare("INSERT INTO `{$this->table}` ({$columns}) VALUES({$placeholders})");
        // bind values
        foreach ($record as $column => $value) {
            if ($value && !is_scalar($value)) {
                $record->$column = 'JSON:' . json_encode($value);
            }
            $sql->bindParam(':__' . $column, $record->$column);
        }
        // execute
        $sql->execute();
        if (!$sql->rowCount()) {
            $error = $sql->errorInfo();
            throw new Error('Failed to create record: ' . $error[2], 400);
        }
        // object changed
        $this->isChanged = true;
        // return
        return ($id) ? $id : $this->getDatabase()->lastInsertID();
    }

    /**
     * Read from DB
     * @param mixed $id
     * @return StdClass 
     */
    public function read($id) {
        if (is_null($id)) {
            throw new Error('READ action requires an ID', 400);
        }
        // build query
        $query = "SELECT `{$this->table}`.*\n" .
                "FROM `{$this->table}`\n" .
                "WHERE `{$this->table}`.`{$this->primary}` = ?";
        // prepare statement
        $sql = $this->prepare($query);
        $sql->bindParam(1, $id);
        $sql->execute();
        // get result
        $result = $sql->fetch(PDO::FETCH_OBJ);
        // check result
        if ($result === false) {
            throw new Error('Could not find record to read', 404);
        }
        // convert any JSON fields
        foreach ($result as $column => $value) {
            if (strpos($value, 'JSON:') === 0) {
                $result->$column = json_decode(substr($value, 5));
            }
        }
        // return
        return $result;
    }

    /**
     * Update in DB
     * @param stdClass $record
     * @param mixed $id
     * @return stdClass 
     */
    public function update($record, $id) {
        if (is_null($id)) {
            throw new Error('UPDATE action requires an ID', 400);
        }
        // prepare fields
        $fields = array();
        foreach ($record as $field => $value) {
            $fields[] = "`$field` = :__$field";
        }
        $fields = implode(', ', $fields);
        // prepare statement
        $sql = $this->prepare("UPDATE `{$this->table}` SET {$fields} WHERE `{$this->primary}` = :__id");
        $sql->bindParam(':__id', $id);
        // fill fields
        foreach ($record as $field => $value) {
            if (!is_scalar($value)) {
                $record->$field = 'JSON:' . json_encode($value);
            }
            $sql->bindParam(':__' . $field, $record->$field);
        }
        // execute
        $sql->execute();
        if (!$sql->rowCount()) {
            $error = $sql->errorInfo();
            throw new Error('Failed to update record: ' . $error[2], 400);
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
    public function delete($id) {
        $record = $this->read($id);
        // prepare statement
        $sql = $this->prepare("DELETE FROM `{$this->table}` WHERE `{$this->primary}` = ?");
        $sql->bindParam(1, $id);
        $sql->execute();
        // assume changes
        $this->isChanged = true;
        // return
        return $record;
    }

    /**
     * Checks whether a record exists in the database
     * @param mixed $id
     * @return boolean 
     */
    public function exists($id) {
        $query = "SELECT COUNT(`{$this->table}`.`{$this->primary}`)\n" .
                "FROM `{$this->table}`\n" .
                "WHERE `{$this->table}`.`{$this->primary}` = ?";
        // prepare statement
        $sql = $this->prepare($query);
        $sql->bindParam(1, $id);
        $sql->execute();
        // get result
        $result = $sql->fetch();
        $result = intval($result[0]) ? true : false;
        // return
        return $result;
    }

    /**
     * Lists all records in a table
     * @return array
     */
    public function all($number_of_records = null, $page = null) {
        // build query
        $query = "SELECT `{$this->table}`.*\n" .
                "FROM `{$this->table}`";
        // paging
        if (is_int($number_of_records)) {
            $query .= "\nLIMIT ";
            if (is_int($page)) {
                $offset = (abs($page) - 1) * $number_of_records;
                $query .= "$offset, ";
            }
            $query .= $number_of_records;
        }
        // prepare statement
        $sql = $this->prepare($query);
        $sql->execute();
        // get result
        $results = array();
        while ($row = $sql->fetch(PDO::FETCH_OBJ)) {
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
    public function deleteAll() {
        $sql = $this->prepare("DELETE FROM `{$this->table}`");
        $sql->execute();
        // changes
        $this->isChanged = true;
        // error
        if ($sql->errorCode() != '00000') {
            $error = $sql->errorInfo();
            throw new Error('Failed to delete all records: ' . $error[2], 400);
        }
        // return
        return true;
    }

    /**
     * Counts records in the table
     * @return int
     */
    public function count() {
        // build query
        $query = "SELECT COUNT(*)\n" .
                "FROM `{$this->table}`";
        // prepare statement
        $sql = $this->prepare($query);
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
    public function search($key, $value) {
        if (!in_array($key, $this->getFields())) {
            return array();
        }
        // build query
        $query = "SELECT `{$this->table}`.*\n" .
                "FROM `{$this->table}`\n" .
                "WHERE `{$this->table}`.`$key` = :__query OR `{$this->table}`.`$key` LIKE :__like";
        // prepare statement
        $sql = $this->prepare($query);
        $sql->bindParam(':__query', $value);
        $like = '%' . $value . '%'; // why? see http://us1.php.net/manual/en/pdostatement.bindparam.php#99698
        $sql->bindParam(':__like', $like);
        $sql->execute();
        // get result
        $results = array();
        while ($row = $sql->fetch(PDO::FETCH_OBJ)) {
            $id = $row->{$this->primary};
            $results[$id] = $row;
            foreach ($row as $column => $value) {
                if (strpos($value, 'JSON:') === 0) {
                    $results[$id]->$column = json_decode(substr($value, 5));
                }
            }
        }
        // return
        return $results;
    }

    /**
     * Returns the first record in the table
     * @return stdObject
     */
    public function first() {
        // build query
        $query = "SELECT `{$this->table}`.*\n" .
                "FROM `{$this->table}`\n" .
                "LIMIT 0, 1";
        // prepare statement
        $sql = $this->prepare($query);
        $sql->execute();
        // get result
        $result = $sql->fetch(PDO::FETCH_OBJ);
        // check result
        if ($result === false) {
            throw new Error('Could not find record to read', 404);
        }
        // return
        return $result;
    }

    /**
     * Returns the last record in the table (assumes auto-increment)
     * @return stdObject
     */
    public function last() {
        // build query
        $query = "SELECT `{$this->table}`.*\n" .
                "FROM `{$this->table}`\n" .
                "ORDER BY `{$this->table}`.`{$this->primary}` DESC\n" .
                "LIMIT 0, 1";
        // prepare statement
        $sql = $this->prepare($query);
        $sql->execute();
        // get result
        $result = $sql->fetch(PDO::FETCH_OBJ);
        // check result
        if ($result === false) {
            throw new Error('Could not find record to read', 404);
        }
        // return
        return $result;
    }

    /**
     * Gets field names from DB
     * @return <array>
     */
    public function describe() {
        $fields = array();
        // prepare statement
        if ($this->driver == 'mysql') {
            $sql = $this->getDatabase()->query("DESCRIBE `{$this->table}`");
            foreach ($sql as $row) {
                $fields[] = $row['Field'];
            }
        } elseif ($this->driver == 'sqlite') {
            $sql = $this->getDatabase()->query("PRAGMA table_info(`{$this->table}`)");
            foreach ($sql as $row) {
                $fields[] = $row['name'];
            }
        } else {
            throw new Error('Cannot describe this database. Implement more driver support.', 500);
        }
        // return
        return $fields;
    }

    /**
     * Overriden getFields to use DB DESCRIBE functionality
     * @return <array>
     */
    protected function getFields() {
        if (!$this->fields) {
            // get fields using Cache and DB tables
            $this->fields = $this->describe();
        }
        return $this->fields;
    }

    /**
     * Force the given data to fit the fields from this table
     * @param mixed $thing
     * @return \stdClass
     */
    public function forceToSchema($thing) {
        $object = new stdClass();
        $i = 0;
        foreach ($this->getFields() as $property) {
            $object->$property = null;
            // arrays
            if (is_array($thing)) {
                if (array_key_exists($property, $thing)) {
                    $object->$property = $thing[$property]; // take associative array values and match them to the table fields
                } elseif (isset($thing[$i])) {
                    $object->$property = $thing[$i++]; // fill the table fields sequentially from a non-associative array
                }
            }
            // objects
            elseif (is_object($thing) && property_exists($thing, $property)) {
                $object->$property = $thing->$property; // use an object to fill table fields
            }
            // ... and the rest
            elseif (is_scalar($thing)) {
                if ($property !== $this->primary) {
                    $object->$property = $thing; // fill the first table field (but not an ID) if not an object or array
                    break;
                }
            }
        }
        return $object;
    }

    /**
     * Return PDO database instance
     * @staticvar string $instance
     * @return PDO
     */
    protected function &getDatabase() {
        static $instance = null;
        if (!$instance) {
            // create PDO instance
            try {
                // build DSN
                if ($this->driver === 'sqlite') {
                    $dsn = "{$this->driver}:{$this->location}";
                } elseif ($this->driver === 'mysql') {
                    $dsn = "{$this->driver}:dbname={$this->database};host={$this->location}";
                } else {
                    throw new Error("Could not create a DSN string for driver '{$this->driver}'.", 500);
                }
                // create instance
                $instance = new PDO($dsn, $this->username, $this->password);
            } catch (PDOError $e) {
                throw new Error($e->getMessage(), 500);
            }
        }
        return $instance;
    }

    /**
     * Prepare a PDO statement and check that it works
     * @param string $query
     * @return PDOStatement
     * @throws Error
     */
    protected function prepare($query) {
        $pdoStatement = $this->getDatabase()->prepare($query);
        if (!$pdoStatement) {
            $error = $this->getDatabase()->errorInfo();
            throw new Error("This SQL statement could not be prepared: {$query}. Additionally, the database returned '{$error[2]}'.", 500);
        }
        return $pdoStatement;
    }

    /**
     * Display last error information
     * @param type $sql 
     */
    static protected function debug($sql) {
        pr($sql->errorInfo());
    }

}
