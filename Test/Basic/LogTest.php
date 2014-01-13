<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class BasicLogTest extends PHPUnit_Framework_TestCase {

    /**
     * Test appending
     */
    public function testAppend() {
        $file = get_writable_dir() . DS . 'test.log';
        BasicLog::append('...', $file);
        $this->assertFileExists($file);
        unlink($file);
    }

    /**
     * Test error log
     */
    public function testErrorLog() {
        $file = get_writable_dir() . DS . 'error.log';
        BasicLog::setFile($file, 'error');
        $this->assertEquals($file, BasicLog::getFile('error'));
        BasicLog::error("Could not find file.", 404);
        $this->assertFileExists($file);
        $this->assertGreaterThan(10, filesize($file));
        unlink($file);
    }

}
