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

    public function begin();
    public function commit();
    public function rollback();
    public function isChanged();
    public function create($record, $id);
    public function read($id);
    public function update($record, $id);
    public function delete($id);
    public function deleteAll();
    public function last();
    public function search($key, $value);
    public function all();
}