<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class BasicBenchmarkTest extends PHPUnit_Framework_TestCase {

    /**
     * Uses the timing functions available in BasicBenchmark
     */
    public function testTimer() {
        BasicBenchmark::startTimer();
        sleep(1);
        BasicBenchmark::endTimer();
        $expected = 1.0;
        $actual = BasicBenchmark::getTimeElapsed();
        $this->assertLessThan(0.001, $expected - $actual);
    }

    /**
     * Uses the memory functions available in BasicBenchmark
     */
    public function testMemory() {
        BasicBenchmark::startMemoryTest();
        $string = str_pad('', 1024, 'x');
        BasicBenchmark::endMemoryTest();
        $this->assertEquals('1104b', BasicBenchmark::getMemoryUsed('b')); // extra 80 bytes... from where?
        $this->assertEquals('1.078kb', BasicBenchmark::getMemoryUsed('kb'));
    }

}
