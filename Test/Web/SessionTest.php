<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class WebSessionTest extends PHPUnit_Framework_TestCase {

    /**
     * Demonstrates WebSession funtionality
     */
    public function testStorage() {
        WebSession::put('a', 42);
        $expected = 42;
        $actual = WebSession::get('a');
        $this->assertEquals($expected, $actual);
        echo session_id();
    }

}
