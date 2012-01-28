<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * StorageInterface
 * All Storage classes must implement these functions
 */
interface StorageInterface{
    
    /**
     * Setup-related functions
     */
    public function begin();
//     public function commit();
    public function rollback();
    public function isChanged();

    /**
     * Item-related functions
     */
    public function create($record, $id = null);
    public function read($id);
    public function update($record, $id);
    public function delete($id);
    public function exists($id);

    /**
     * List-related functions
     */
    public function all($number_of_records = null, $page = null);
    public function deleteAll();
    public function count();
    public function search($key, $value);
    public function first();
    public function last();
}