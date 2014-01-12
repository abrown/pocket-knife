<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class WebUrlTest extends PHPUnit_Framework_TestCase {

    /**
     * @var WebUrl
     */
    protected $object;

    /**
     * Sets environment for all tests
     */
    public static function setUpBeforeClass() {
        // setup URL
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/directory/index.php/objects/35/read?param1=a&param2=b';
    }

    /**
     * Demonstrates getUrl() functionality
     */
    public function testGetUrl() {
        $expected = 'http://www.example.com/directory/index.php/objects/35/read?param1=a&param2=b';
        $actual = WebUrl::getUrl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Demonstrates getUri() functionality; in pocket-knife, this refers to the
     * URL fragment after the anchor (e.g. '.php')
     */
    public function testGetUri() {
        $_SERVER['REQUEST_URI'] = '/directory/index.php/objects/35/read?param1=a&param2=b';
        $expected = 'objects/35/read';
        $actual = WebUrl::getUri();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Returns the URL up to the script name
     */
    public function testGetDirectoryUrl() {
        $expected = 'http://www.example.com/directory/';
        $actual = WebUrl::getDirectoryUrl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Returns the URL through the script name
     */
    public function testGetLocationUrl() {
        $expected = 'http://www.example.com/directory/index.php';
        $actual = WebUrl::getLocationUrl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Returns tokens in string form
     */
    public function testGetAnchoredUrl() {
        $expected = 'objects/35/read';
        $actual = WebUrl::getAnchoredUrl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Builds a URL from tokens
     */
    public function testCreateAnchoredUrl() {
        $_GET['param3'] = 'c';
        $expected = 'http://www.example.com/directory/index.php/objects/36/delete?param3=c';
        $actual = WebUrl::createAnchoredUrl('objects/36/delete', true); // 'true' passes get variables
        $this->assertEquals($expected, $actual);
    }

    /**
     * Builds a URL from a relative url
     */
    public function testCreate() {
        $expected = 'http://www.example.com/directory/objects/36/delete';
        $actual = WebUrl::create('objects/36/delete');
        $this->assertEquals($expected, $actual);
        $expected = 'http://www.example.com/objects/36';
        $actual = WebUrl::create('/objects/36');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Returns an array of tokens from an anchored url
     */
    public function testGetTokens() {
        $expected = array('objects', '35', 'read');
        $actual = WebUrl::getTokens();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Normalizes URLs to RFC 3986 standards
     */
    public function testNormalize() {
        // test 1
        $expected = 'http://www.example.com/dir1/dir2?a=b%2Fs%24';
        $actual = WebUrl::normalize('http://www.EXAMPLE.com/Dir1/A/../Dir2?a=b/s$');
        $this->assertEquals($expected, $actual);
        // test 2
        $expected = 'http://www.example.com/';
        $actual = WebUrl::normalize('http://www.EXAMPLE.com');
        $this->assertEquals($expected, $actual);
        // test 3
        $expected = 'http://www.example.com/dir/page.html';
        $actual = WebUrl::normalize('http://www.EXAMPLE.com//dir/./Page.html?');
        $this->assertEquals($expected, $actual);
    }

}
