<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ResourceInterface
 */
interface ResourceInterface{
    
    /**
     * Returns the object URI
     */
    public function getURI();
    
    /**
     * Returns the request object given a content type;
     * should be overloaded in descendant classes to 
     * accommodate content type differences and possible 
     * resource-to-data binding
     * @param string $content_type
     * @throws ExceptionSettings
     * @return stdClass
     */
    public function fromRepresentation($content_type){
        if( !array_key_exists($content_type, RepresentationInterface::MAP) ) throw new ExceptionSettings('Unknown request content-type.', 500);
        $class = RepresentationInterface::$MAP[$content_type];       
        $representation = new $class;
        $class->receive();
        return $class->getData();
    }
    
    /**
     * Returns a representation of the resource given a content type 
     * and data; should be overloaded in descendant classes to 
     * accommodate content type differences and possible 
     * resource-to-data binding
     * @param string $content_type
     * @throws ExceptionSettings
     * @return RepresentationInterface
     */
    public function toRepresentation($content_type, $data){
        if( !array_key_exists($content_type, RepresentationInterface::MAP) ) throw new ExceptionSettings('Unknown request content-type.', 500);
        $class = RepresentationInterface::$MAP[$content_type];
        $representation = new $class;
        $class->setData($data);
        return $class;
    }
    
    /**
     * Initializes the storage object
     * @param Settings $settings
     */
    public function setStorage($settings);
    
    /**
     * Returns the storage object
     */
    public function getStorage();

}
