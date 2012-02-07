<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides simple methods to deal with XML strings; mimics behavior
 * of json_encode()/json_decode()
 * @uses 
 * @example
 */
class BasicXml{

    /**
     * Returns an XML-encoded string
     * @param any $object
     * @return string
     */
    public static function xml_encode($object){
        // TODO: see blog.joeysmith.com/articles/5.html
    }
    
    /**
     * Returns a stdClass object corresponding to the XML string
     * @param string $string
     * @return stdClass
     */
    public static function xml_decode($string){
        
    }
}