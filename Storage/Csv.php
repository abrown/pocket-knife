<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Stores records in a CSV file on the local server; it is recommended to only
 * use simple objects with this storage method since it does flatten nested
 * arrays and objects.
 * @uses StorageInterface, Error, Settings, BasicValidation
 */
class StorageCsv implements StorageInterface {

    /**
     * Stores the path to the CSV database
     * @var string 
     */
    public $location;

    /**
     * List of the names of each column; the property names should be listed
     * as values, not keys.
     * @example
     * $this->schema = array('id', 'description', ...);
     * @var array 
     */
    public $schema = array();

    /**
     * Character separating columns
     * @var string
     */
    public $delimiter = ',';

    /**
     * Character enclosing columns
     * @var string
     */
    public $enclosure = '"';

    /**
     * Database data
     * @var object 
     */
    protected $data;

    /**
     * Whether the request changes the data
     * @var boolean
     */
    protected $isChanged = false;

    /**
     * Constructor
     * @param Settings $settings 
     */
    public function __construct($settings) {
        // validate
        BasicValidation::with($settings)
                ->isSettings()
                ->withProperty('location')->isString()
                ->upAll()
                ->withProperty('schema')->isArray()
                ->upAll()
                ->withOptionalProperty('enclosure')->isString()
                ->upAll()
                ->withOptionalProperty('delimiter')->isString();
        // import settings
        $settings->copyTo($this);
        // create file if necessary
        if (!is_file($this->location)) {
            $handle = fopen($this->location, 'w');
            fputcsv($handle, $this->schema, $this->delimiter, $this->enclosure);
            fclose($handle);
        }
        // ensure location is accessible
        if (!is_writable($this->location)) {
            throw new Error("The file '$this->location' is not writeable and could not be created.", 500);
        }
        // initialize database
        $this->begin();
    }

    /**
     * Begin transaction
     */
    public function begin() {
        $this->data = new stdClass();
        // open file
        $handle = fopen($this->location, 'r');
        if ($handle === false) {
            throw new Error("Could not open the file '{$this->location}'", 500);
        }
        // read schema
        $line = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
        if ($line === false) {
            throw new Error('Schema not set in CSV file.', 500);
        }
        $this->setSchemaFromArray($line);
        //rewind($handle);
        // read file into memory
        $row = 1;
        $number_of_columns = count((array) $this->schema);
        while (($line = fgetcsv($handle, 0, $this->delimiter, $this->enclosure)) !== FALSE) {
            // check
            if (count($line) != $number_of_columns) {
                throw new Error("Incorrect number of columns at row " + $row, 500);
            }
            // to object
            $_new_object = new stdClass();
            if(!in_array('id',$this->schema)){
                $_new_object->id = $row;
            }
            $i = 0;
            foreach ($this->schema as $property) {
                $_new_object->$property = $line[$i++];
            }
            // add to data
            $id = $_new_object->id;
            $this->data->$id = $_new_object;
            // increment row
            $row++;
        }
        // close 
        fclose($handle);
    }

    /**
     * Complete transaction
     */
    public function commit() {
        if ($this->isChanged()) {
            // open file
            $handle = fopen($this->location, 'w');
            if ($handle === false) {
                throw new Error("Could not open the file '{$this->location}'", 500);
            }
            // write schema
            fputcsv($handle, $this->schema, $this->delimiter, $this->enclosure);
            // write data to file
            foreach ($this->data as $id => $object) {
                $values = $this->flattenToSchema($object);
                fputcsv($handle, $values, $this->delimiter, $this->enclosure);
            }
            // close 
            fclose($handle);
        }
        // clear changed
        $this->isChanged = false;
    }

    /**
     * Roll back transaction
     */
    public function rollback() {
        $this->isChanged = false;
        $this->begin();
    }

    /**
     * Return true if data has been modified
     * @return boolean 
     */
    public function isChanged() {
        return $this->isChanged;
    }

    /**
     * Create record
     * @param mixed $record
     * @param mixed $id 
     */
    public function create($record, $id = null) {
        // check if matches schema
        if (!$this->matchesSchema($record)) {
            $record = $this->forceToSchema($record);
        }
        // determine id
        if (is_null($id)) {
            $id = $this->getNextID();
            // save ID in record if necessary
            if (is_object($record) && in_array('id', $this->schema)) {
                $record->id = $id;
            }
        }
        // save
        $this->data->$id = $record;
        $this->isChanged = true;
        // return
        return $id;
    }

    /**
     * Read record
     * @param mixed $id
     * @return mixed 
     */
    public function read($id) {
        // check id
        if (is_null($id)) {
            throw new Error('READ action requires an ID', 400);
        }
        // return
        if ($this->exists($id)) {
            return $this->data->$id;
        } else {
            throw new Error("READ action could not find ID '$id'", 404);
        }
    }

    /**
     * Update record; allows updates of just part of a record
     * @param mixed $record
     * @param mixed $id 
     */
    public function update($record, $id) {
        // check id
        if (is_null($id)) {
            throw new Error('UPDATE action requires an ID', 400);
        }
        // check existence
        if (!$this->exists($id)) {
            throw new Error("UPDATE action could not find ID '$id'", 400);
        }
        // change each field
        foreach ($record as $key => $value) {
            $this->data->$id->$key = $value;
            $this->isChanged = true;
        }
        return $this->data->$id;
    }

    /**
     * Delete a record
     * @param mixed $id 
     */
    public function delete($id) {
        // check id
        if (is_null($id)) {
            throw new Error('DELETE action requires an ID', 400);
        }
        // check existence
        if (!$this->exists($id)) {
            throw new Error("DELETE action could not find ID '$id'", 400);
        }
        // delete and return record
        $record = $this->data->$id;
        unset($this->data->$id);
        $this->isChanged = true;
        return $record;
    }

    /**
     * Test whether an object with the given ID exists
     * @param mixed $id
     * @return boolean 
     */
    public function exists($id) {
        return property_exists($this->data, $id);
    }

    /**
     * Return all records
     * @return array
     */
    public function all($number_of_records = null, $page = null) {
        return $this->data;
    }

    /**
     * Delete all records
     * @return boolean
     */
    public function deleteAll() {
        $this->data = new stdClass();
        $this->isChanged = true;
        return true;
    }

    /**
     * Return the number of items in this store
     * @return int 
     */
    public function count() {
        return count((array) $this->data);
    }

    /**
     * Search for records
     * @param string $key
     * @param mixed $value 
     */
    public function search($key, $value) {
        $found = array();
        foreach ($this->data as $id => $record) {
            if (isset($record->$key) && $record->$key == $value)
                $found[$id] = $record;
        }
        return $found;
    }

    /**
     * Return the first element
     * @return mixed
     */
    public function first() {
        foreach ($this->data as $i => $v) {
            return $v;
        }
    }

    /**
     * Return last element
     * @return mixed
     */
    public function last() {
        $last = null;
        foreach ($this->data as $i => $v) {
            $last = $v;
        }
        return $last;
    }

    /**
     * Return the last element's ID
     * @return mixed
     */
    public function getLastID() {
        $last = null;
        foreach ($this->data as $i => $v) {
            $last = $i;
        }
        return $last;
    }

    /**
     * Calculate the next ID that will be created
     * @return string
     */
    public function getNextID() {
        $last = $this->getLastID();
        if (is_null($last)) {
            $next = 1;
        } elseif (is_numeric($last)) {
            $next = (int) $last + 1;
        } else {
            $next = $last . '$';
        }
        return $next;
    }

    /**
     * Return last time the database was modified in unix-time
     * @return int
     */
    public function getLastModified() {
        return filectime($this->location);
    }

    /**
     * Test if the given object has all of the properties in the schema
     * @param object $thing
     * @return boolean
     */
    public function matchesSchema($thing) {
        if(!is_object($thing)){
            return false;
        }
        foreach ($this->schema as $property) {
            if (!property_exists($thing, $property)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Force an object to fit the schema; used when things passed to create
     * do not match the schema.
     * @param mixed $thing
     * @return stdClass
     */
    public function forceToSchema($thing) {
        $object = new stdClass();
        $i = 0;
        foreach ($this->schema as $property) {
            $object->$property = null;
            // arrays
            if(is_array($thing)){
                if(array_key_exists($property, $thing)){
                    $object->$property = $thing[$property];
                }
                elseif(isset($thing[$i])){
                    $object->$property = $thing[$i++];
                }
            }
            // objects
            elseif(is_object($thing) && property_exists($thing, $property)){
                $object->$property = $thing->$property;
            }
            // ... and the rest
            elseif(is_scalar($thing)){
                $object->$property = $thing;
                break;
            }
        }
        return $object;
    }

    /**
     * Force an object to fit the schema for writing to CSV
     * @param stdClass $object
     * @return array
     */
    protected function flattenToSchema(stdClass $object) {
        $array = array();
        foreach ($this->schema as $property) {
            $array[$property] = null;
            if (isset($object->$property)) {
                $array[$property] = $this->flatten($object->$property);
            }
        }
        return $array;
    }
    
    /**
     * Helper method for flattenToSchema()
     * @param mixed $thing
     * @return mixed
     */
    protected function flatten($thing){
        if(is_array($thing) || is_object($thing)){
            $out = array();
            foreach($thing as $key => $value){
                $out[$key] = $this->flatten($value);
            }
            return implode(', ', $out);
        }
        else{
            return $thing;
        }
    }

    /**
     * Set the schema from a given object
     * @param object $object
     */
    protected function setSchemaFromObject(stdClass $object) {
        $this->schema = array();
        foreach ($object as $key => $value) {
            $this->schema[] = $key;
        }
    }

    /**
     * Set the schema from a given array
     * @param array $array
     */
    protected function setSchemaFromArray(array $array) {
        $this->schema = $array;
    }

}
