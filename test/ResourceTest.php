<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Override 'get_http_body' to mock HTTP inputs
 */
function get_http_body() {
    return ResourceTest::$REQUEST_BODY;
}

/**
 * Get autoload ready for Library example class
 */
$path = dirname(dirname(__FILE__));
require $path . '/start.php';
autoload('BasicClass');
BasicClass::autoloadAll('Resource');

/**
 * ResourceTest
 */
class ResourceTest extends PHPUnit_Framework_TestCase {

    public static $REQUEST_BODY;

    public function setUp() {
        $this->expected = new stdClass();
        $this->expected->a = 1;
        $this->expected->b = array(2 => 'some text');
        $this->expected->b[3]->c = false;
        $this->expected->b[3]->d = true;
    }

    public function testUse() {
        
    }

}

/**
 * This class demonstrates a web service for some books
 */
class Library extends ResourceList {
    // TODO
}