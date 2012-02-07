<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Representation of a RESTful resource. It must be able to be
 * sent or received.
 *
 * @author andrew
 */
interface RepresentationInterface {

    /**
     * Maps content types to representation types
     * @var array
     */
    public static $MAP = array(
        'application/json' => 'RepresentationJson',
        'application/octet-stream' => 'RepresentationFile',
        //'application/rss+xml' => 'RepresentationRss',
        //'application/soap+xml' => 'RepresentationSoap',
		'application/xml' => 'RepresentationXml',
		'application/x-www-form-urlencoded' => 'RepresentationForm',
        'text/html' => 'RepresentationHtml',
        'text/plain' => 'RepresentationText'
    );
    
    /**
     * Stores the object 
     * @var stdClass
     */
    protected $data;
    
    /**
     * Accesses data object
     * @return stdClass
     */
    public function getData();
    
    /**
     * Modifies data object
     */
    public function setData($data);
    
    /**
     * Checks whether data has been initialized
     */
    public function hasData(){
        return ($this->data === null);
    }
    
    /**
     * Receives and parses the data from the client
     */
    public function receive();
    
    /**
     * Sends the data to the client
     */
    public function send();
}