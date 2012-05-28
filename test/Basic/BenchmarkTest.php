<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
if (!class_exists('TestCase'))
    require '../Case.php';

class BasicBenchmarkTest extends TestCase {

    /**
     * Uses the timing functions available in BasicBenchmark
     */
    public function testTimer() {
        BasicBenchmark::startTimer();
        sleep(1);
        BasicBenchmark::endTimer();
        $expected = 1.0;
        $actual = BasicBenchmark::getTime();
        $this->assertLessThan($expected - $actual, 0.0001);
    }

    /**
     * Uses the memory functions available in BasicBenchmark
     */
    public function testMemory() {
        $expected = BasicBenchmark::getMemory('kb');
        $this->assertGreaterThan(0, $expected);
    }

}