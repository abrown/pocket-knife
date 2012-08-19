<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Stores records in memory within this class; best used for testing
 * @uses StorageInterface, Error, BasicValidation
 */
class StorageMemory implements StorageInterface {

    /**
     * Whether the request changes the data
     * @var boolean 
     */
    public $isChanged = false;

    /**
     * Database data
     * @var mixed 
     */
    protected $data = array();

    /**
     * Constructor
     */
    public function __construct($settings) {
        BasicValidation::with($settings)
                ->withOptionalProperty('data')
                ->isObject();
        // import data
        if (isset($settings->data)) {
            $this->data = (array) $settings->data;
            // as per bug #45346, numeric string keys are not allowed (http://stackoverflow.com/questions/316747)
            $this->data = array_combine(array_keys($this->data), array_values($this->data));
        }
    }

    /**
     * Begins transaction
     */
    public function begin() {
        // nothing to do
    }

    /**
     * Completes transaction
     */
    public function commit() {
        // nothing to do
    }

    /**
     * Rolls back transaction
     */
    public function rollback() {
        // nothing to do
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
        if (!is_null($id)) {
            throw new Error('CREATE ID must be null.', 400);
        } else {
            $id = $this->lastID() + 1;
            $this->data[$id] = $record;
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
        if ($this->exists($id))
            return $this->data[$id];
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
        if (!$this->exists($id))
            throw new Error("UPDATE action could not find ID '$id'", 400);
        // ensure record is an object
        if( !is_object($this->data[$id]) ){
            $this->data[$id] = (object) $this->data[$id];
        }
        // change each field
        foreach ($record as $key => $value) {
            $this->data[$id]->$key = $value;
            $this->isChanged = true;
        }
        return $this->data[$id];
    }

    /**
     * Deletes a record
     * @param mixed $id 
     */
    public function delete($id) {
        if (is_null($id))
            throw new Error('DELETE action requires an ID', 400);
        if (!$this->exists($id))
            throw new Error("DELETE action could not find ID '$id'", 400);
        $record = $this->data[$id];
        unset($this->data[$id]);
        $this->isChanged = true;
        return $record;
    }

    /**
     * Tests whether an object with the given ID exists
     * @param mixed $id
     * @return boolean 
     */
    public function exists($id) {
        return isset($this->data[$id]);
    }

    /**
     * Returns all records
     * @return array
     */
    public function all($number_of_records = null, $page = null) {
        if ($number_of_records > 0 && $page > 0) {
            $offset = $number_of_records * ($page - 1);
            return array_slice((array) $this->data, $offset, $number_of_records, true);
        }
        // else
        return $this->data;
    }

    /**
     * Deletes all records
     * @return boolean
     */
    public function deleteAll() {
        $this->data = array();
        $this->isChanged = true;
        return true;
    }

    /**
     * Returns the number of items
     * @return int 
     */
    public function count() {
        return count($this->data);
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
        $id = $this->lastID();
        return $this->data[$id];
    }

    /*     * *
     * Returns the last element ID
     * @return int
     */

    public function lastID() {
        $last = null;
        foreach ($this->data as $i => $v) {
            if ($i > $last)
                $last = $i;
        }
        return $last;
    }

}