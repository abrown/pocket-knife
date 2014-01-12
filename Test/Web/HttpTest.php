<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class WebHttpTest extends PHPUnit_Framework_TestCase {

    /**
     * getMethod() gets the current HTTP method from the server or URL
     */
    public function testGetMethod() {
        // test URL query
        $_GET['method'] = 'PUT';
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
    public function testGetParameter() {
        $_GET['parameter'] = 'b';
        $_POST['parameter2'] = 'a';
        $expected = 'a';
        $actual = WebHttp::getParameter('parameter2');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Sets the HTTP code
     * @expectedError Error
     */
    public function testSetCode() {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent; cannot set HTTP code.');
        }
        WebHttp::setCode(404);
        $expected = array('HTTP/1.1 404');
        $actual = preg_grep('/^HTTP/', headers_list());
        $this->assertEquals($expected, $actual);
    }

    /**
     * Set the HTTP content type
     * @expectedError Error
     */
    public function testSetContentType() {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent; cannot set content type.');
        }
        WebHttp::setContentType('application/json');
        $expected = array('Content-Type: application/json');
        $actual = preg_grep('/^Content-Type/', headers_list());
        $this->assertEquals($expected, $actual);
    }

    /**
     * Redirects the user to a new location
     * @expectedError Error
     */
    public function testRedirect() {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent; cannot set location to redirect.');
        }
        WebHttp::redirect('http://www.google.com');
        $expected = array('Location: http://www.google.com');
        $actual = preg_grep('/^Location/', headers_list());
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests HTTP request
     */
    public function testRequest() {
        $actual = WebHttp::request('http://www.google.com');
        $this->assertNotNull($actual);
        $this->assertEquals(200, WebHttp::getRequestCode());
        $this->expectOutputRegex('/.{100}/');
        echo (substr($actual, 0, 100) . '...');
    }

    /**
     * Tests a spurious HTTP request; the given URL should be rejected; 
     * we test this to see if request() can handle failure and throw a proper
     * Error.
     */
    public function testSpuriousRequest() {
        $this->setExpectedException('Error');
        WebHttp::request('http://localhost:9999');
        $this->assertEquals(400, WebHttp::getRequestCode());
    }

}
