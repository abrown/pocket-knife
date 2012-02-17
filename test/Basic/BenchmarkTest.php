<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class BasicBenchmarkTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require $path . '/start.php';
        // get Service code
        BasicClass::autoloadAll('BasicBenchmark');
    }

    /**
     * Uses the timing functions available in BasicBenchmark
     */
    public function testTimer(){
        BasicBenchmark::startTimer();
        sleep(1);
        BasicBenchmark::endTimer();
        $expected = 1.0;
        $actual = BasicBenchmark::getTime();
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Uses the memory functions available in BasicBenchmark
     */
    public function testMemory(){
        $expected = BasicBenchmark::getMemory('kb');
        print($expected);
    }
}