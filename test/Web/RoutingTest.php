<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * 
 */
class WebRoutingTest extends PHPUnit_Framework_TestCase{
    
    /**
     * Sets environment for all tests
     */
    public static function setUpBeforeClass() {
        // start pocket-knife
        $path = dirname(dirname(dirname(__FILE__)));
        require $path.'/start.php';
        // manually load classes
        autoload('BasicClass');
        BasicClass::autoloadAll('WebRouting');
        // setup URL
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/directory/index.php/objects/35/read?param1=a&param2=b';
    }
    
    /**
     * Sets environment before for each test
     */
    public function setUp(){
        // unnecessary
    }
 
    public function testGetAnchor(){
        $this->assertNotNull( WebRouting::getAnchor() );
    }
    
    public function testGetUrl(){
        $expected = 'http://www.example.com/directory/index.php/objects/35/read?param1=a&param2=b';
        $actual = WebRouting::getUrl();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetDirectoryUrl(){
        $expected = 'http://www.example.com/directory';
        $actual = WebRouting::getDirectoryUrl();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetLocationUrl(){
        $expected = 'http://www.example.com/directory/index.php';
        $actual = WebRouting::getLocationUrl();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetAnchoredUrl(){
        $expected = 'objects/35/read';
        $actual = WebRouting::getAnchoredUrl();
        $this->assertEquals($expected, $actual);
    }
    
    public function testCreateUrl(){
        $_GET['a'] = 'b';
        $anchored_url = 'some/anchored/url.html';
        $expected = 'http://www.example.com/directory/index.php/some/anchored/url.html?a=b';
        $actual = WebRouting::createUrl($anchored_url);
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetTokens(){
        $expected = array('objects', '35', 'read');
        $actual = WebRouting::getTokens();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetToken(){
        $expected = '35';
        $actual = WebRouting::getToken(1);
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetClassname(){
        $expected = 'Object';
        $actual = WebRouting::getClassname();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetMethod(){
        $_REQUEST['PUT'] = false;
        $expected = 'update';
        $actual = WebRouting::getMethod();
        $this->assertEquals($expected, $actual);
    }
    
    public function testParse(){
        $expected = array('object'=>'objects', 'id'=>'35', 'action'=>'read');
        $actual = WebRouting::parse();
        $this->assertEquals($expected, $actual);
    }
}