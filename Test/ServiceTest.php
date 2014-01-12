<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class ServiceTest extends \PHPUnit_Framework_TestCase {

    public $instance;

    public function testBuildingResources() {
        $service = new Service(new Settings());
        $resource = $service->buildResource(new Route('GET', 'dog/23', 'text/plain'));
        $this->assertInstanceOf('Dog', $resource);
        // illustrates how insecure buildResource could be 
        $this->setExpectedException('Error');
        $resource = $service->buildResource(new Route('GET', 'reflection/...', 'text/plain'));
        // expect errors
        $this->setExpectedException('Error');
        $resource = $service->buildResource(new Route('GET', '.../...', 'text/plain'));
    }
    
    public function testTriggers(){
        $service = new Service(new Settings());
        $resource = new Dog('Spot', 'Brown', 5);
        $in = new Representation();
        $out = $service->consumeResource($resource, $in); 
        $this->assertEquals(100, $out->getData()->age); // our Dog output trigger should change the age property
        $this->assertFalse(isset($out->getData()->gender)); // ... and remove the gender property
    }
      
    public function testConsumingResources() {
        $service = new Service(new Settings());
        $resource = new Dog();
        // verify a valid representation is returned
        $in = new Representation(null, 'text/html');
        $out = $service->consumeResource($resource, $in);
        $this->assertInstanceOf('Representation', $out);
        $this->assertEquals('text/html', $out->getContentType());
    }
    
    /**
     * Ensure content type setting is working correctly for output: first
     * $_GET[accept], then $_SERVER['HTTP_ACCEPT'], then incoming request,
     * then application/json
     */
    public function testSettingContentTypes(){
        $service = new Service(new Settings());
        // default
        $out = new Representation();
        $this->assertEquals('application/json', $out->getContentType());
        // incoming request
        $service->route = new Route('GET', 'dog', 'application/octet-stream');
        $this->expectOutputRegex('/.+/');
        $out = $service->execute();
        $this->assertEquals('application/octet-stream', $out->getContentType());
        // SERVER
        $_SERVER['HTTP_ACCEPT'] = 'text/plain';
        $service->route = new Route('GET', 'dog');
        $this->expectOutputRegex('/.+/');
        $out = $service->execute();
        $this->assertEquals('text/plain', $out->getContentType());
        // GET
        $_GET['accept'] = 'application/xml';
        $service->route = new Route('GET', 'dog');
        $this->expectOutputRegex('/.+/');
        $out = $service->execute();
        $this->assertEquals('application/xml', $out->getContentType());
    }
    
    public function testGetRequest(){
        $service = new Service(new Settings());
        $service->route = new Route('GET', 'dog', 'application/json');
        $service->representation = new Representation(null, 'application/json');
        $this->expectOutputString('{"name":"Spike","color":null,"age":100}');
        $service->execute();
    }
    
    public function testPostRequest(){
        $service = new Service(new Settings());
        $service->route = new Route('POST', 'dog', 'application/json');
        $service->representation = new Representation(new Dog('Bill', 'Black', 2), 'application/json');
        $this->expectOutputString('{"gender":"male","name":"Bill","color":"Black","age":2}');
        $service->execute();
    }
    
    public function testBenchmark(){
        BasicBenchmark::startMemoryTest();
        BasicBenchmark::startTimer();
        // start pocket knife and benchmark it
        $service = new Service(new Settings());
        $service->route = new Route('DELETE', 'dog', 'application/json');
        $this->expectOutputString('true');
        $service->execute();
        // display benchmark results
        BasicBenchmark::endTimer();
        BasicBenchmark::endMemoryTest();
        //echo 'Load: ' . BasicBenchmark::getTimeElapsed() . 's and ' . BasicBenchmark::getMemoryUsed();
    }

//    /**
//     * See example class below, UrlShortener
//     */
//    public function testUrlShortenerJson() {
//        // mimic a json request
//        self::$REQUEST_BODY = "http://www.google.com";
//        $expected = "Content-Type: application/json\n\n" +
//                '{"url":"ed646a3334","time:"' . date('r') . '"}';
//        $service = new \Service();
//        $service->class = 'UrlShortener';
//        $service->method = 'shorten';
//        $service->content_type = 'application/json';
//        $actual = $service->execute(true);
//        $this->assertEquals($expected, $actual);
//    }
//
//    /**
//     * See example class below, UrlShortener
//     */
//    public function testUrlShortenerHtml() {
//        // mimic an html request
//        self::$REQUEST_BODY = "http://www.google.com";
//        $expected = "Content-Type: text/html\n\n" +
//                'The URL is: ed646a3334<br/>\n' . date('r');
//        $service = new Service();
//        $service->class = 'UrlShortener';
//        $service->method = 'shorten';
//        $service->content_type = 'text/html';
//        $actual = $service->execute(true);
//        $this->assertEquals($expected, $actual);
//    }
}
