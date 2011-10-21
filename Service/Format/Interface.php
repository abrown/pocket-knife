<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ServiceFormatInterface
 * Enter description here ...
 * @uses
 *
 */
interface ServiceFormatInterface{
    /**
     * Accesses data received in the HTTP request
     */
    public function getData();
    /**
     * Sets data to be sent in the HTTP response
     */
    public function setData($data);
    /**
     * Sends the HTTP response
     */
    public function send();
    /**
     * Sends an error HTTP response
     */
    public function sendError();
}