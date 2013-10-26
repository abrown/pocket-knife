<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Stores JSON records in a JSON file on the local server; uses indexes
 * @uses StorageInterface, BasicValidation, Settings, Error
 */
class StorageJson implements StorageInterface {

    /**
     * Stores the path to the JSON database
     * @var string 
     */
    public $location;

    /**
     * Database data
     * @var mixed 
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
                ->withProperty('location')->isString();
        // import settings
        $settings->copyTo($this);
        // create database if necessary
        $this->data = new stdClass();
        if (!is_file($this->location)) {
            file_put_contents($this->location, '{}');
        }
        // ensure location is accessible
        elseif (!is_writable($this->location)) {
            throw new Error("The file '$this->location' is not writable", 500);
        }
        // read database
        else {
            $this->begin();
        }
    }

    /**
     * Begins transaction
     */
    public function begin() {
        $json = file_get_contents($this->location);
        $this->data = json_decode($json);
    }

    /**
     * Completes transaction
     */
    public function commit() {
        if ($this->isChanged()) {
            $json = json_encode($this->data);
            file_put_contents($this->location, $json);
        }
        $this->isChanged = false;
    }

    /**
     * Rolls back transaction
     */
    public function rollback() {
        $this->isChanged = false;
        $this->begin();
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
            $last = $this->getLastID();
            if (is_null($last))
                $id = 1;
            elseif (is_numeric($last))
                $id = (int) $last + 1;
            else
                $id = $last . '$1';
            // save ID in record if necessary
            if (is_object($record) && method_exists($record, 'setID')) {
                $record->setID($id);
            }
            // save record
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
        if (is_int($number_of_records)) {
            if (is_int($page)) {
                $offset = (abs($page) - 1) * $number_of_records;
            } else {
                $offset = 0;
            }
            return array_slice((array) $this->data, $offset, $number_of_records, true);
        } else {
            return (array) $this->data;
        }
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
     * Return the first element
     * @return mixed
     */
    public function first() {
        if (is_object($this->data)) {
            foreach ($this->data as $i => $v) {
                return $v;
            }
        }
    }

    /**
     * Return the last element
     * @return mixed
     */
    public function last() {
        $id = $this->getLastID();
        return $this->data->$id;
    }

    /**
     * Return last ID
     * @return int
     */
    public function getLastID() {
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