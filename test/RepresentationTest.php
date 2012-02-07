<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Replace file_get_contents for classes that use it to get
 * request bodies; set static variable in ServiceTest::$REQUEST_BODY
 * to simulate the request
 */
namespace Testing;
function file_get_contents($file){
    if( $file == 'php://input' ) return ServiceTest::$REQUEST_BODY;
    else return \file_get_contents($file);
}


class ServiceTest extends PHPUnit_Framework_TestCase{

    public static $REQUEST_BODY = "";
    
    public static function setUpBeforeClass(){
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path.'/start.php';
        // get code
        autoload('Representation');
        autoload('RepresentationFile');
        autoload('RepresentationForm');
        autoload('RepresentationHtml');
        autoload('RepresentationJson');
        autoload('RepresentationText');
        autoload('RepresentationXml');
    }

    public function setUp(){
        $this->expected = new stdClass();
        $this->expected->a = 1;
        $this->expected->b = array(2=>'some text');
        $this->expected->b[3]->c = false;
        $this->expected->b[3]->d = true;
    }
    
    public function testFile(){
        self::$REQUEST_BODY = "c29tZSB0ZXh0";
        $expected = "some text";
        $f = new RepresentationForm();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($expected, $actual);
    }
    
    public function testForm(){
        self::$REQUEST_BODY = "a=1&b[2]=some+text&b[3][c]=false&b[3][d]=true";
        $f = new RepresentationForm();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }
    
    public function testHtml(){
        self::$REQUEST_BODY = "sample text";
        $expected = "sample text";
        $f = new RepresentationHtml();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals("sample text", $actual);
    }
    
    public function testJson(){
        self::$REQUEST_BODY = '{"a": 1, b: [null, null, "some text", {"c":false, "d":true}]}';
        $f = new RepresentationJson();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }
    
    public function testText(){
        self::$REQUEST_BODY = "sample text";
        $expected = "sample text";
        $f = new RepresentationText();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($expected, $actual);
    }
    
    public function testXml(){
        self::$REQUEST_BODY = '<?xml version="1.0"><root><a>1</a><b><2>some text</2><3><c>false</c><d>true</d></3></b></root>';
        $f = new RepresentationXml();
        $f->receive();
        $actual = $f->getData();
        $this->assertEquals($this->expected, $actual);
    }
}