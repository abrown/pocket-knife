<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Representation of a RESTful resource. It must be able to be
 * sent or received.
 * @uses RepresentationFile, RepresentationForm, RepresentationHtml, RepresentationJson, RepresentationText, RepresentationUpload, RepresentationXml
 * @author andrew
 */
abstract class Representation {

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
        'multipart/form-data' => 'RepresentationUpload',
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
    public abstract function getData();

    /**
     * Modifies data object
     */
    public abstract function setData($data);

    /**
     * Checks whether data has been initialized
     */
    public function hasData() {
        return ($this->data === null);
    }

    /**
     * Receives and decodes the data from the client
     */
    public abstract function receive();
 
    /**
     * Encodes and sends the data to the client
     */
    public abstract function send();
}