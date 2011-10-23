<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ServiceObjectInterface
 */
interface ServiceObjectInterface{
    /**
     * Gets the storage object
     */
    public function getStorage();
    /**
     * Initializes the storage object
     */
    public function setStorage($configuration);
}
