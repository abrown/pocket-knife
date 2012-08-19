<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Override 'get_http_body' to mock HTTP inputs
 */
function get_http_body(){
    return RepresentationTest::$REQUEST_BODY;
}

class RepresentationTest extends PHPUnit_Framework_TestCase {

    public static $REQUEST_BODY;
    
    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        autoload('BasicClass');
        BasicClass::autoloadAll('Representation');
    }

    public function setUp() {
        $this->expected = new stdClass();
        $this->expected->a = 1;
        $this->expected->b = new stdClass();
        $this->expected->b->two = 'some text';
        $this->expected->b->three->c = false;
        $this->expected->b->three->d = true;
    }

    /**
     * Demonstrates use of a file representation
     */
    public function testFile() {
        $this->markTestSkipped('Requires $_FILES to be populated and file_get_contents() overriden');
        /**
        self::$REQUEST_BODY = "c29tZSB0ZXh0";
        $expected = "some text";
        $f = new RepresentationFile();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($expected, $actual);
         */
    }

    /**
     * Demonstrates use of a form representation
     */
    public function testForm() {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        self::$REQUEST_BODY = "a=1&b[two]=some+text&b[three][c]=false&b[three][d]=true";
        $f = new RepresentationForm();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }

    /**
     * Demonstrates use of the HTML representation
     */
    public function testHtml() {
        self::$REQUEST_BODY = "sample text";
        $expected = "sample text";
        $f = new RepresentationHtml();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals("sample text", $actual);
    }

    /**
     * Demonstrates use of the JSON representation
     */
    public function testJson() {
        self::$REQUEST_BODY = '{"a": 1, "b": {"two":"some text", "three":{"c":false, "d":true}}}';
        $f = new RepresentationJson();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }

    /**
     * Demonstrates use of the text representation
     */
    public function testText() {
        self::$REQUEST_BODY = "sample text";
        $expected = "sample text";
        $f = new RepresentationText();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Demonstrates use of the upload representation
     */
    public function testUpload() {
        // setup
        $file = tempnam(null, null);
        file_put_contents($file, 'sample text');
        $_FILES['test_file'] = array();
        $_FILES['test_file']['error'] = false;
        $_FILES['test_file']['name'] = "testfile.txt";
        $_FILES['test_file']['tmp_name'] = $file;
        // test upload
        $expected = 'sample text';
        $f = new RepresentationUpload();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($expected, $actual);
        // test name
        $this->assertEquals('testfile.txt', $f->getName());
        // teardown
        unlink($file);
    }

    public function testXml() {
        self::$REQUEST_BODY = '<?xml version="1.0" ?>'.
                '<object type="stdClass"><a type="integer">1</a>'.
                '<b type="stdClass"><two type="string">some text</two>'.
                '<three type="stdClass"><c type="boolean">false</c>'.
                '<d type="boolean">true</d></three></b></object>';
        $f = new RepresentationXml();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }

}