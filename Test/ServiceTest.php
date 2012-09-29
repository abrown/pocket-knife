<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
* Replaces file_get_contents for classes that use it to get
* request bodies; set static variable in ServiceTest::$REQUEST_BODY
* to simulate the request
*/
namespace Testing;
function file_get_contents($file){
    if( $file == 'php://input' ) return ServiceTest::$REQUEST_BODY;
    else return \file_get_contents($file);
}

/**
 * Get autoload ready for Library example class
 */
$path = dirname(dirname(__FILE__));
require_once $path . '/start.php';
autoload('BasicClass');
\BasicClass::autoloadAll('Service');
\BasicClass::autoloadAll('ResourceGeneric');

class ServiceTest extends \PHPUnit_Framework_TestCase {

    public $instance;
    
    public static function setUpBeforeClass() {

    }

    public function setUp() {
        $this->instance = new \Service();
    }

    public function testExistence() {
        $this->assertNotNull($this->instance);
    }

    /**
     * See example class below, UrlShortener
     */
    public function testUrlShortenerJson(){
        // mimic a json request
        self::$REQUEST_BODY = "http://www.google.com";
        $expected = "Content-Type: application/json\n\n"+
            '{"url":"ed646a3334","time:"'.date('r').'"}';
        $service = new \Service();
        $service->class = 'UrlShortener';
        $service->method = 'shorten';
        $service->content_type = 'application/json';
        $actual = $service->execute(true);
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * See example class below, UrlShortener
     */
    public function testUrlShortenerHtml(){
        // mimic an html request
        self::$REQUEST_BODY = "http://www.google.com";
        $expected = "Content-Type: text/html\n\n"+
                    'The URL is: ed646a3334<br/>\n'.date('r');
        $service = new Service();
        $service->class = 'UrlShortener';
        $service->method = 'shorten';
        $service->content_type = 'text/html';
        $actual = $service->execute(true);
        $this->assertEquals($expected, $actual);
    }

}

/**
 * This class demonstrates a sample web service for 
 * shortening URLs; no claims are made about the efficacy of
 * the shortening algorithm. By using a generic resource, we
 * do not have to worry about storing data; anything that needs
 * storing will be stored in the class instance as a property.
 */
class UrlShortener extends \ResourceGeneric{
    public function shorten($url){
        return substr(md5($url), 0, 10); // real classy...
    }
    public function fromRepresentation($content_type){
        $url = parent::fromRepresentation($content_type);
        return WebUrl::normalize($url);
    }
    public function toRepresentation($content_type, $data){
        $representation = parent::toRepresentation($content_type, $data);
        if( $content_type == 'text/html' ){
            // special HTML templating
            $representation->setTemplate(new WebTemplate("The URL is: <template:url/><br/><?php echo date('r');?>"), WebTemplate::PHP_STRING);
            $representation->getTemplate()->replace('url', $data);
        }
        elseif( $content_type == 'application/octet-stream' ){
            // can download a file by this name
            $representation->setName("url.txt");
        }
        elseif( $content_type == 'application/json' ){
            // special json format
            $o = new stdClass;
            $o->url = $representation->getData();
            $o->time = date('r');
            $representation->setData($o);
        }
        return $representation;
    }
}