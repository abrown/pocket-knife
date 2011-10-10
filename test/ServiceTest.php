<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class ServiceTest extends PHPUnit_Framework_TestCase{
    
    public static function setUpBeforeClass(){
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path.'/start.php';
        // get Service code
        autoload('Service');
    }
    
    public function setUp(){
        $this->s = new Service();
    }
    
    public function testExistence()
    {    
        $this->assertNotNull($this->s);
    }
    
    public function testRESTful(){

    }
   
}