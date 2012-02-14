<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class ResourceTest extends PHPUnit_Framework_TestCase{

    public static $REQUEST_BODY = "";
    
    public static function setUpBeforeClass(){
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path.'/start.php';
        // get code
        autoload('ResourceInterface');
        autoload('ResourceGeneric');
        autoload('ResourceItem');
        autoload('ResourceList');
    }

    public function setUp(){
        $this->expected = new stdClass();
        $this->expected->a = 1;
        $this->expected->b = array(2=>'some text');
        $this->expected->b[3]->c = false;
        $this->expected->b[3]->d = true;
    }
}



/**
 * This class demonstrates a web service for a library
 */
class Library extends ResourceList{
    // TODO
}