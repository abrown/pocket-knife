<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
if (!class_exists('TestCase'))
    require '../Case.php';

class BasicXmlTest extends TestCase {

    /**
     * xml_encode should produce an XML-representation of an object
     */
    public function testXmlEncode() {
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<object type="ExampleClass"><a type="array">' .
                '<element type="string">one</element><element type="integer">' .
                '2</element><element type="double">3.2</element><four' .
                ' type="array"><element type="string">a</element><element' .
                ' type="string">b</element></four></a><b type="stdClass">' .
                '<d type="integer">5</d></b></object>';
        $e = new ExampleClass();
        $actual = BasicXml::xml_encode($e);
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    public function testXmlDecodeScalar() {
        // scalar
        $scalar = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<scalar type="double">99.998</scalar>';
        $expected = 99.998;
        $actual = BasicXml::xml_decode($scalar);
        $this->assertEquals($expected, $actual);
    }

    public function testXmlDecodeArray() {
        // array
        $array = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<array type="array"><element type="integer">1</element>' .
                '<element type="integer">2</element>' .
                '<element type="integer">3</element></array>';
        $expected = array(1, 2, 3);
        $actual = BasicXml::xml_decode($array);
        $this->assertEquals($expected, $actual);
        // recursive array
        $array = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<array type="array"><element type="array">' .
                '<element type="integer">1</element>' .
                '<element type="integer">2</element></element></array>';
        $expected = array(array(1, 2));
        $actual = BasicXml::xml_decode($array);
        $this->assertEquals($expected, $actual);
    }

    public function testXmlDecodeObject() {
        // object
        $input = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<object type="ExampleClass"><a type="array">' .
                '<element type="string">two</element><element type="integer">' .
                '2</element><element type="double">3.2</element><four' .
                ' type="array"><element type="string">a</element><element' .
                ' type="string">b</element></four></a><b type="stdClass">' .
                '<d type="integer">6</d></b></object>';
        $expected = new ExampleClass();
        $expected->a[0] = 'two';
        $expected->b->d = 6;
        $actual = BasicXml::xml_decode($input);
        $this->assertEquals($expected, $actual);
    }

}

/**
 * Used in this test to test encoding/decoding
 */
class ExampleClass {

    public $a;
    public $b;
    private $c;

    function ExampleClass() {
        $this->a = array('one', 2, 3.2, 'four' => array('a', 'b'));
        $this->b = new stdClass();
        $this->b->d = 5;
    }

}