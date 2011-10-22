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
}