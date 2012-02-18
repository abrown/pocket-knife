<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Resource defines a standard way for the service to interact with the classes
 * created in an application. Service uses fromRepresentation() and toRepresentation()
 * to handle data input and output and calls a class method to do the processing.
 * @example
 * // step 1: receive
 * $input = $resource->fromRepresentation($content_type);
 * // step 2: process
 * $output = $resource->action($input);
 * // step 3: send
 * $resource->toRepresentation($content_type, $output);
 * @uses ExceptionSettings, ResourceGeneric, ResourceItem, ResourceList
 */
abstract class Resource {

    /**
     * Returns the object URI
     */
    public abstract function getURI();

    /**
     * Returns the request object given a content type;
     * should be overloaded in descendant classes to 
     * accommodate content type differences and possible 
     * resource-to-data binding
     * @param string $content_type
     * @throws ExceptionSettings
     * @return stdClass
     */
    public function fromRepresentation($content_type) {
        if (!array_key_exists($content_type, Representation::MAP))
            throw new ExceptionSettings('Unknown request content-type.', 500);
        $class = Representation::$MAP[$content_type];
        $representation = new $class;
        $class->receive();
        return $class;
    }

    /**
     * Returns a representation of the resource given a content type 
     * and data; should be overloaded in descendant classes to 
     * accommodate content type differences and possible 
     * resource-to-data binding
     * @param string $content_type
     * @throws ExceptionSettings
     * @return Representation
     */
    public function toRepresentation($content_type, $data) {
        if (!array_key_exists($content_type, Representation::MAP))
            throw new ExceptionSettings('Unknown request content-type.', 500);
        $class = Representation::$MAP[$content_type];
        $representation = new $class;
        $class->setData($data);
        return $class;
    }

    /**
     * Initializes the storage object
     * @param Settings $settings
     */
    public abstract function setStorage($settings);

    /**
     * Returns the storage object
     */
    public abstract function getStorage();
}
