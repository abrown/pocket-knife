<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class PageTest extends PHPUnit_Extensions_OutputTestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get code
        autoload('BasicDocumentation');
        BasicDocumentation::autoloadAll('Page');
    }

    public function setUp() {
        $this->s = new Page();
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