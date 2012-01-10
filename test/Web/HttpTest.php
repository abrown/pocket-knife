<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * 
 */
class WebHttpTest extends PHPUnit_Framework_TestCase{
    
    /**
     * Sets environment for all tests
     */
    public static function setUpBeforeClass() {
        // start pocket-knife
        $path = dirname(dirname(dirname(__FILE__)));
        require $path.'/start.php';
        // manually load classes
        autoload('BasicClass');
        BasicClass::autoloadAll('WebHttp');
    }
    
    /**
     * Sets environment before for each test
     */
    public function setUp(){
        // unnecessary
    }
    
    public function testGetUrl(){
        // setup URL
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/directory/index.php/objects/35/read?param1=a&param2=b';
        // test
        $expected = 'http://www.example.com/directory/index.php/objects/35/read?param1=a&param2=b';
        $actual = WebHttp::getUrl();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetUri(){
        $_SERVER['REQUEST_URI'] = '/directory/index.php/objects/35/read?param1=a&param2=b';
        $expected = '/directory/index.php/objects/35/read?param1=a&param2=b';
        $actual = WebHttp::getUri();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetTokens(){
        // deprecated
    }
    
    public function testGetMethod(){
        // test URL query
        $_GET['PUT'] = 1;
        $expected = 'PUT';
        $actual = WebHttp::getMethod();
        $this->assertEquals($expected, $actual);
        $_GET = array();
        // test actual HTTP METHOD
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $expected = 'HEAD';
        $actual = WebHttp::getMethod();
        $this->assertEquals($expected, $actual);
    }
    
    public function testGetParameter(){
        $_GET['parameter'] = 'b';
        $_POST['parameter2'] = 'a';
        $expected = 'a';
        $actual = WebHttp::getParameter('parameter2');
        $this->assertEquals($expected, $actual);
    }
    
    public function testNormalize(){
        // test 1
        $expected = 'http://www.example.com/dir1/dir2?a=b%2Fs%24';
        $actual = WebHttp::normalize('http://www.EXAMPLE.com/Dir1/A/../Dir2?a=b/s$');
        $this->assertEquals($expected, $actual);
        // test 2
        $expected = 'http://www.example.com/';
        $actual = WebHttp::normalize('http://www.EXAMPLE.com');
        $this->assertEquals($expected, $actual);
        // test 3
        $expected = 'http://www.example.com/dir/page.html';
        $actual = WebHttp::normalize('http://www.EXAMPLE.com//dir/./Page.html?');
        $this->assertEquals($expected, $actual);
    }
    
    public function testClean(){
        // test XSS
        $expected = "&#039;&#039;;!--&quot;&lt;XSS&gt;=&amp;{()}";
        $actual = WebHttp::sanitize("'';!--\"<XSS>=&{()}", 'html');
        $this->assertEquals($expected, $actual);
        // test numeric
        $expected = 25;
        $actual = WebHttp::sanitize('25nd', 'integer');
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @expectedException ExceptionWeb
     */
    public function testSetCode(){
        WebHttp::setCode(404);
        $expected = array('HTTP/1.1 404');
        $actual = preg_grep('/^HTTP/', headers_list());
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @expectedException ExceptionWeb
     */
    public function testSetContentType(){
        WebHttp::setContentType('application/json');
        $expected = array('Content-Type: application/json');
        $actual = preg_grep('/^Content-Type/', headers_list());
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @expectedException ExceptionWeb
     */
    public function testRedirect(){
        WebHttp::redirect('http://www.google.com');
        $expected = array('Location: http://www.google.com');
        $actual = preg_grep('/^Location/', headers_list());
        $this->assertEquals($expected, $actual);
    }
    
    public function testRequest(){
        $actual = WebHttp::request('http://www.google.com');
        $this->assertNotNull($actual);
    }
    
    public function testGetRequestCode(){
        $expected = 200;
        $actual = WebHttp::getRequestCode();
        $this->assertEquals($expected, $actual);
    }
}