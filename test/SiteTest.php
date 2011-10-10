<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class SiteTest extends PHPUnit_Extensions_OutputTestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        autoload('Site');
    }

    public function setUp() {
        $this->s = new Site();
    }
    
    public function testUrlHandling(){
        $this->s->setUrl('google.com/');
        $this->assertEquals('http://google.com', $this->s->getUrl());
    }

    public function testExistence() {
        $this->assertNotNull($this->s);
    }
    
    public function testCanAccessPages() {
        
    }
}