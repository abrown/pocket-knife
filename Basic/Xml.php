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
class BasicXml {

    /**
     * Returns an XML-encoded string
     * @param any $object
     * @return string
     */
    public static function xml_encode($thing) {
        $w = new XMLWriter;
        $w->openMemory();
        $w->setIndent(true);
        $w->startDocument('1.0', 'UTF-8');
        // get root name
        if (is_scalar($thing)) {
            $name = 'scalar';
        } elseif (is_object($thing)) {
            $name = 'object';
        } elseif (is_array($thing)) {
            $name = 'array';
        } else {
            throw new ExceptionForbidden("Cannot encode an object of type " . gettype($object));
        }
        // encode recursively
        BasicXml::_xml_encode($thing, $name, $w);
        // output
        $w->endDocument();
        return trim($w->outputMemory(TRUE));
    }

    /**
     * Handles recursive encoding of objects
     * @param mixed $thing
     * @param string $name
     * @param XMLWriter $writer
     * @throws ExceptionSettings
     */
    private static function _xml_encode($thing, $name, $writer) {
        if (is_scalar($thing)) {
            $writer->startElement($name);
            $writer->startAttribute('type');
            $writer->text(gettype($thing));
            $writer->endAttribute();
            $writer->text($thing);
            $writer->endElement();
        } elseif (is_object($thing)) {
            $writer->startElement($name);
            $writer->startAttribute('type');
            $writer->text(get_class($thing));
            $writer->endAttribute();
            if (get_parent_class($thing)) {
                $writer->startAttribute('extends');
                $writer->text(get_parent_class($thing));
                $writer->endAttribute();
            }
            // encode each property
            $public_properties = get_public_vars($thing);
            foreach ($public_properties as $property => $value) {
                BasicXml::_xml_encode($value, $property, $writer);
            }
            $writer->endElement();
        } elseif (is_array($thing)) {
            $writer->startElement($name);
            $writer->startAttribute('type');
            $writer->text('array');
            $writer->endAttribute();
            // encode each element
            foreach ($thing as $key => $value) {
                if (is_int($key))
                    $key = 'element';
                BasicXml::_xml_encode($value, $key, $writer);
            }
            $writer->endElement();
        }
        else {
            throw new ExceptionForbidden("Cannot encode an object of type " . gettype($thing));
        }
    }

    /**
     * Returns a stdClass object corresponding to the XML string
     * @param string $string
     * @return stdClass
     */
    public static function xml_decode($string) {
        $reader = XMLReader::XML($string);
        $reader->read();
        $variable = BasicXml::_xml_decode($reader);
        return $variable;
    }

    /**
     * Handles the recursive decoding of objects
     * @param XMLReader $reader
     * @return mixed
     */
    private function _xml_decode($reader) {
        //pr($reader->name);
        $variable = null;
        // get scalar
        $scalars = array('boolean', 'integer', 'double', 'string', 'null');
        if ($reader->name == 'scalar' || in_array($reader->getAttribute('type'), $scalars)) {
            $type = $reader->getAttribute('type');
            $variable = $reader->readString();
            if( $type == 'boolean' && $variable == 'false' ){
                $variable = false;
            }
            else{
                settype($variable, $type);
            }            
        }
        // get array
        elseif ($reader->name == 'array' || $reader->getAttribute('type') == 'array') {
            $variable = array();
            // loop through array elements
            $depth = $reader->depth;
            while ($reader->read()) {
                if( $reader->depth <= $depth ) break;
                if ($reader->nodeType == XMLReader::ELEMENT) {
                    if ($reader->name == 'element') {
                        $variable[] = null;
                        end($variable);
                        $cursor = key($variable);
                    } else {
                        $variable[$reader->name] = null;
                        $cursor = $reader->name;
                    }
                    // recursively decode the contents
                    $variable[$cursor] = BasicXml::_xml_decode($reader);
                }
            }
        }
        // get object
        else {
            $type = $reader->getAttribute('type');
            if (!class_exists($type, false))
                autoload($type);
            $variable = new $type;
            // loop through properties
            $depth = $reader->depth;
            while ($reader->read()) {
                if( $reader->depth <= $depth ) break;
                if ($reader->nodeType == XMLReader::ELEMENT) {
                    $property = $reader->name;
                    $variable->$property = BasicXml::_xml_decode($reader);
                }
            }
        }
        // return
        return $variable;
    }
}