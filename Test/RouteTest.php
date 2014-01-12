<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class RouteTest extends PHPUnit_Framework_TestCase {
  
    public function testBuildingRoutes() {
        $route = new Route('GET', 'http://example.com/api.php/resource/id?property=...', 'application/json');
        $this->assertEquals('GET resource/id (application/json)', (string) $route);
        $route = new Route('POST', 'http://example.com/api.php///...', 'text/plain');
        $this->assertEquals(3, count($route->parts));
        $this->setExpectedException('Error');
        $route = new Route('GET', ''); // no URI
        $this->setExpectedException('Error');
        $route = new Route('GET', 'http://example.com/api.php/'); // no URI
    }

    public function testRetrievingURIs() {
        $route = new Route('GET', '', 'text/plain');
        $this->assertEquals('resource/id', $route->getURIFrom('http://example.com/api.php/resource/id?property=...'));
        $this->assertEquals('', $route->getURIFrom(''));
        $this->assertEquals('', $route->getURIFrom('http://example.com/api.php/'));
        $this->assertEquals('/...//', $route->getURIFrom('http://example.com/api.php//...//'));
    }

    public function testRetrievingParts() {
        $route = new Route('GET', 'http://example.com/api.php/resource/id?property=...', 'text/plain');
        $this->assertEquals('resource', $route->getResource());
        $this->assertEquals('id', $route->getIdentifier());
        $route = new Route('GET', '', 'text/plain');
        $this->assertEquals(null, $route->getResource());
        $this->assertEquals(null, $route->getIdentifier());
        $route = new Route('GET', 'http://example.com/api.php/', 'text/plain');
        $this->assertEquals(null, $route->getResource());
        $this->assertEquals(null, $route->getIdentifier());
        $route = new Route('GET', 'http://example.com/api.php/1234!@#$...ASDF', 'text/plain');
        $this->assertEquals('1234!@#$...asdf', $route->getResource());
        $this->assertEquals(null, $route->getIdentifier());
    }
    
    public function testExtractWithTemplate(){
        $route = new Route('GET', 'http://example.com/api.php/resource/27/editable/...?property=...', 'text/plain');
        $template = new Route('GET', '[resource]/[id]/[property]', 'text/plain');
        $actual = $route->extractValuesWith($template);
        $expected = array('resource' => 'resource', 'id' => 27, 'property' => 'editable');
        $this->assertEquals($expected, $actual);
        // and again
        $template = new Route('GET', '[resource]/[id]/[property]/[property_b]/[property_c]', 'text/plain');
        $actual = $route->extractValuesWith($template);
        $expected = array('resource' => 'resource', 'id' => 27, 'property' => 'editable', 'property_b' => '...');
        $this->assertEquals($expected, $actual);
    }

}
