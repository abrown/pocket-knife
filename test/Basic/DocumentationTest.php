<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class BasicDocumentationTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(dirname(__FILE__)));
        require $path . '/start.php';
        // get code
        autoload('BasicClass');
        BasicClass::autoloadAll('BasicDocumentation');
    }

    /**
     * Demonstrates usage of the BasicDocumentation class to create
     * automatic HTML documentation from a javadoc-notated file
     */
    public function testDocumentation(){
        $documentation = new BasicDocumentation('BasicDocumentation');
        $html = $documentation->getHtml();
    }
}