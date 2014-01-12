<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class RepresentationTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        
    }

    public function setUp() {
        $this->expected = new stdClass();
        $this->expected->a = 1;
        $this->expected->b = new stdClass();
        $this->expected->b->two = 'some text';
        $this->expected->b->three = new stdClass();
        $this->expected->b->three->c = false;
        $this->expected->b->three->d = true;
    }

    /**
     * Demonstrates use of a file representation
     */
    public function testOctetStream() {
        $_SERVER['REQUEST_BODY'] = "c29tZSB0ZXh0";
        $_SERVER['REQUEST_HEADERS'][] = 'Content-Disposition:  attachment; filename="transfer.file"';
        $f = new Representation(null, 'application/octet-stream');
        $f->receive();
        $this->assertEquals('transfer.file', $f->getData()->filename);
        $this->assertEquals('some text', $f->getData()->contents);
    }

    /**
     * Demonstrates use of a form representation
     */
    public function testForm() {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_BODY'] = "a=1&b[two]=some+text&b[three][c]=false&b[three][d]=true";
        $f = new Representation(null, 'application/x-www-form-urlencoded');
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }

    /**
     * Demonstrates use of the HTML representation
     */
    public function testHtml() {
        $_SERVER['REQUEST_BODY'] = "sample text";
        $expected = "sample text";
        $f = new Representation(null, 'text/html');
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals("sample text", $actual);
    }

    /**
     * Demonstrates use of the JSON representation
     */
    public function testJson() {
        $_SERVER['REQUEST_BODY'] = '{"a": 1, "b": {"two":"some text", "three":{"c":false, "d":true}}}';
        $f = new Representation(null, 'application/json');
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }

    /**
     * Demonstrates use of the text representation
     */
    public function testText() {
        $_SERVER['REQUEST_BODY'] = "sample text";
        $expected = "sample text";
        $f = new Representation(null, 'text/plain');
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
        $_FILES['test_file']['type'] = "text/plain";
        $_FILES['test_file']['tmp_name'] = $file;
        // test upload
        $expected = 'sample text';
        $f = new Representation(null, 'multipart/form-data');
        $f->receive();
        // test name
        $this->assertEquals('testfile.txt', $f->getData()->files->test_file->name);
        $this->assertEquals('text/plain', $f->getData()->files->test_file->type);
        $this->assertEquals('sample text', $f->getData()->files->test_file->contents);
        $this->assertEquals(strlen('sample text'), $f->getData()->files->test_file->size);
        // teardown
        unlink($file);
    }

    public function testXml() {
        $_SERVER['REQUEST_BODY'] = '<?xml version="1.0" ?>' .
                '<object type="stdClass"><a type="integer">1</a>' .
                '<b type="stdClass"><two type="string">some text</two>' .
                '<three type="stdClass"><c type="boolean">false</c>' .
                '<d type="boolean">true</d></three></b></object>';
        $f = new Representation(null, 'application/xml');
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }

    public function testSendJson() {
        $f = new Representation(new Dog('Barky', 'yellow', 1), 'application/json');
        $this->expectOutputString('{"name":"Barky","color":"yellow","age":1}');
        $f->send(200);
    }

    public function testSendText() {
        $f = new Representation(new Dog('Barky', 'yellow', 1), 'application/json');
        $this->expectOutputString('A yellow animal called Barky is 1.');
        $f->send(200, 'text/plain');
    }

}
