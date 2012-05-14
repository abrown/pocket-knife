<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Stores records in a CSV file on the local server
 * @uses StorageInterface, Error, Error
 */
class StorageCSV implements StorageInterface {

    /**
     * Whether the request changes the data
     * @var boolean 
     */
    public $isChanged = false;

    /**
     * Stores the path to the CSV database
     * @var string 
     */
    public $location;

    /**
     * Structure model for each record
     * @var object 
     */
    public $schema;
    public $delimiter = ',';
    public $enclosure = '"';
    public $escape = '\\';

    /**
     * Database data
     * @var object 
     */
    protected $data;

    /**
     * Constructor
     * @param Settings $settings 
     */
    public function __construct($settings) {
        // validate
        BasicValidation::with($settings)
                ->withProperty('location')->isPath()
                ->upAll()
                ->withProperty('schema')->isObject();
        // import settings
        foreach ($this as $property => $value) {
            if (isset($settings->$property)) {
                $this->$property = $settings->$property;
            }
        }
        // create database if necessary
        $this->data = new stdClass();
        if (!is_file($this->location)) {
            $this->writeLine($this->schema);
        }
        // ensure location is accessible
        elseif (!is_writable($this->location)) {
            throw new Error("The file '$this->location' is not writable", 500);
        }
        // read database
        else {
            $json = file_get_contents($this->location);
            $this->data = json_decode($json);
        }
    }

    /**
     * Begins transaction
     */
    public function begin() {
        // open file
        $handle = fopen($this->location, 'r');
        if ($handle === false) {
            throw new Error("Could not open the file '{$this->location}'", 500);
        }
        // read file into memory
        $row = 1;
        $number_of_columns = count((array) $this->schema);
        while (($line = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== FALSE) {
            // check
            if (count($line) != $number_of_columns) {
                throw new Error("Incorrect number of columns at row " + $row, 500);
            }
            // to object
            $i = 0;
            $_new_object = new stdClass();
            foreach ($this->schema as $property => $type) {
                $_new_object->$property = $line[0];
                $i++;
            }
            // get id
            $id = $_new_object->id;
            // add to data
            $this->data[$id] = $_new_object;
            // increment row
            $row++;
        }
        // close 
        fclose($handle);
    }

    /**
     * Completes transaction
     */
    public function commit() {
        if ($this->isChanged()) {
            // open file
            $handle = fopen($this->location, 'w');
            if ($handle === false) {
                throw new Error("Could not open the file '{$this->location}'", 500);
            }
            // write data to file
            foreach($this->data as $id => $object){
                fputcsv($handle, (array) $object, $this->delimiter, $this->enclosure, $this->escape);
            }
            // close 
            fclose($handle);
        }
    }

    /**
     * Rolls back transaction
     */
    public function rollback() {
        // @todo
    }

    /**
     * Returns true if data has been modified
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
        if ($id) {
            $this->data->$id = $record;
        } else {
            $last = $this->last();
            if (is_null($last))
                $id = 1;
            elseif (is_numeric($last))
                $id = (int) $last + 1;
            else
                $id = $last . '$1';
            $this->data->$id = $record;
        }
        $this->isChanged = true;
        return $id;
    }

    /**
     * Read record
     * @param mixed $id
     * @return mixed 
     */
    public function read($id) {
        if (is_null($id))
            throw new Error('READ action requires an ID', 400);
        if (property_exists($this->data, $id))
            return $this->data->$id;
        else
            throw new Error("READ action could not find ID '$id'", 404);
    }

    /**
     * Update record
     * @param mixed $record
     * @param mixed $id 
     */
    public function update($record, $id) {
        if (is_null($id))
            throw new Error('UPDATE action requires an ID', 400);
        if (!property_exists($this->data, $id))
            throw new Error("UPDATE action could not find ID '$id'", 400);
        // change each field
        foreach ($record as $key => $value) {
            $this->data->$id->$key = $value;
            $this->isChanged = true;
        }
        return $this->data->$id;
    }

    /**
     * Deletes a record
     * @param mixed $id 
     */
    public function delete($id) {
        if (is_null($id))
            throw new Error('DELETE action requires an ID', 400);
        if (!property_exists($this->data, $id))
            throw new Error("DELETE action could not find ID '$id'", 400);
        $record = $this->data->$id;
        unset($this->data->$id);
        $this->isChanged = true;
        return $record;
    }

    /**
     * Tests whether an object with the given ID exists
     * @param mixed $id
     * @return boolean 
     */
    public function exists($id) {
        return property_exists($this->data, $id);
    }

    /**
     * Returns all records
     * @return array
     */
    public function all($number_of_records = null, $page = null) {
        return $this->data;
    }

    /**
     * Deletes all records
     * @return boolean
     */
    public function deleteAll() {
        $this->data = new stdClass();
        $this->isChanged = true;
        return true;
    }

    /**
     * Returns the number of items
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
            if ($record->$key == $value)
                $found[$id] = $record;
        }
        return $found;
    }

    /**
     * Returns the first element
     * @return mixed
     */
    public function first() {
        foreach ($this->data as $i => $v) {
            return $v;
        }
    }

    /**
     * Returns last element
     * @return mixed
     */
    public function last() {
        $last = null;
        foreach ($this->data as $i => $v) {
            if ($i > $last)
                $last = $i;
        }
        return $last;
    }

    /**
     * Returns last time the database was modified in unix-time
     * @return int
     */
    public function getLastModified() {
        return filectime($this->location);
    }

}