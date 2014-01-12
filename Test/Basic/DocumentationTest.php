<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class BasicDocumentationTest extends PHPUnit_Framework_TestCase {

    /**
     * Demonstrates usage of the BasicDocumentation class to create
     * automatic HTML documentation from a javadoc-notated file
     */
    public function testDocumentation() {
        $documentation = new BasicDocumentation('BasicDocumentation');
        $html = $documentation->getHtml();
    }

}
