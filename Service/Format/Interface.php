<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ServiceFormatInterface
 * All content formats must implement this
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
     * Gets the text to send to the client
     */
    public function getResponse();
    /**
     * Sets the text to send to the client
     */
    public function setResponse($data);
    /**
     * Sends the HTTP response
     */
    public function send();
    /**
     * Sends an error HTTP response
     */
    public function sendError();
}