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
     * getMethod() gets the current HTTP method from the server or URL
     */
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
    
    /**
     * Retrieve a parameter from the HTTP request
     */
    public function testGetParameter(){
        $_GET['parameter'] = 'b';
        $_POST['parameter2'] = 'a';
        $expected = 'a';
        $actual = WebHttp::getParameter('parameter2');
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Sets the HTTP code
     * @expectedException ExceptionWeb
     */
    public function testSetCode(){
        WebHttp::setCode(404);
        $expected = array('HTTP/1.1 404');
        $actual = preg_grep('/^HTTP/', headers_list());
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Set the HTTP content type
     * @expectedException ExceptionWeb
     */
    public function testSetContentType(){
        WebHttp::setContentType('application/json');
        $expected = array('Content-Type: application/json');
        $actual = preg_grep('/^Content-Type/', headers_list());
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Redirects the user to a new location
     * @expectedException ExceptionWeb
     */
    public function testRedirect(){
        WebHttp::redirect('http://www.google.com');
        $expected = array('Location: http://www.google.com');
        $actual = preg_grep('/^Location/', headers_list());
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Tests HTTP request
     */
    public function testRequest(){
        $actual = WebHttp::request('http://www.google.com');
        $this->assertNotNull($actual);
        echo (substr($actual, 0, 100).'...');
    }
    
    /**
     * Should return HTTP request code
     */
    public function testGetRequestCode(){
        $expected = 200;
        $actual = WebHttp::getRequestCode();
        $this->assertEquals($expected, $actual);
    }
}