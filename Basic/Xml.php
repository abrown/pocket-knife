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
    public static function xml_encode($thing){
        $w = new XMLWriter;
        $w->openMemory();
        $w->startDocument('1.0', 'UTF-8');
        // get root name
        if( is_scalar($thing) ){
            $name = 'element';
        }
        elseif( is_object($thing) ){
            $name = 'object';
        }
        elseif( is_array($thing) ){
            $name = 'array';
        }
        else{
            throw new ExceptionSettings("Cannot encode an object of type ".gettype($object));
        }
        // encode recursively
        BasicXml::_xml_encode($object, $name, $w);
        // output
        $w->endDocument();
        return $w->outputMemory(TRUE);
    }

    /**
     * Handles recursive encoding of objects
     * @param mixed $object
     * @param string $name
     * @param XMLWriter $writer
     * @throws ExceptionSettings
     */
    private static function _xml_encode($object, $name, $writer){
        if( is_scalar($object) ){
            $writer->startElement($name);
            $writer->startAttribute('type');
            $writer->text(gettype($object));
            $writer->endAttribute();
            $writer->text($object);
            $writer->endElement();
        }
        elseif( is_object($object) ){
            $writer->startElement($name);
            $writer->startAttribute('type');
            $writer->text(get_class($object));
            $writer->endAttribute();
            if( get_parent_class($object) ){
                $writer->startAttribute('extends');
                $writer->text(get_parent_class($object));
                $writer->endAttribute();
            }
            foreach( $object as $key => $value ){
                BasicXml::_xml_encode($object, $key, $writer);           
            }
            $writer->endElement();
        }
        elseif( is_array($object) ){
            $writer->startElement($name);
            $writer->startAttribute('type');
            $writer->text('array');
            $writer->endAttribute();
            foreach( $object as $key => $value ){
                BasicXml::_xml_encode($object, $key, $writer);           
            }
            $writer->endElement();
        }
        else{
            throw new ExceptionSettings("Cannot encode an object of type ".gettype($object));
        }
    }

    /**
     * Returns a stdClass object corresponding to the XML string
     * @param string $string
     * @return stdClass
     */
    public static function xml_decode($string){
        $xml = XMLReader::XML($xmlstr);
        while($xml->read()) {
            switch($xml->nodeType) {
                case XMLReader::ELEMENT:
                    switch($xml->name) {
                        case 'data': continue;
                        case 'int': case 'string': case 'bool': case 'double':
                            return $xml->getAttribute('value');
                        case 'array':
                            $in_array = true;
                            $data = array();
                            break;
                        case 'object':
                            $in_object = true;
                            $class = $xml->getAttribute('class');
                            $data = new $class;
                            break;
                        case 'entry':
                            if ($in_array) {
                                $data[$xml>getAttribute('key')] = $xml>getAttribute('value');
                            }
                            break;
                        case 'property':
                            if ($in_object) {
                                $prop = $xml->getAttribute('name');
                                $data->{$prop} = $xml>getAttribute('value');
                            }
                            break;
                        default: /* What else is there? */
                            return null;
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    if ($xml->name == 'data') return $data;
                    break;
                default: break;
            }
        }
        return $data;
    }


}