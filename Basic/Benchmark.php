<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * BasicBenchmark
 * @example 
 * BasicBenchmark::startMemoryTest();
 * BasicBenchmark::startTimer();
 * // code to test...
 * BasicBenchmark::endTimer();
 * BasicBenchmark::endMemoryTest();
 * // display
 * echo BasicBenchmark::getTimeElapsed().'s and '.BasicBenchmark::getMemoryUsed();
 * // should display something like '0.02354s and 2.472mb'
 * @uses
 */
class BasicBenchmark {

    /**
     * Timer storage
     * @var int 
     */
    static $startTime = 0;
    static $endTime = 0;

    /**
     * Start timer
     * @return float the microseconds-time when the timer was started
     */
    public static function startTimer() {
        self::$startTime = microtime(true);
        return self::$startTime;
    }

    /**
     * End timer
     * @return float the microseconds-time when the timer was stopped
     */
    public static function endTimer() {
        self::$endTime = microtime(true);
        return self::$endTime;
    }

    /**
     * Return the time elapsed in the timer
     * @return float the number of seconds from startTime() to endTime()
     */
    public static function getTimeElapsed() {
        return round(self::$endTime - self::$startTime, 5);
    }

    /**
     * Return peak memory usage in a human-readable format
     * @return string 
     */
    public static function getPeakMemoryUsed($unit = null) {
        $size = memory_get_peak_usage(false);
        // choose units
        $units = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        if ($unit) {
            $i = array_search($unit, $units);
        } else {
            $i = floor(log($size, 1024));
        }
        // format size
        $mem = round($size / pow(1024, $i), 3);
        // return
        return $mem . $units[$i];
    }

    /**
     * Return current memory usage
     * @return string
     */
    public static function getMemoryUsed($unit = null) {
        if (self::$startMemory && self::$endMemory) {
            $size = self::$endMemory - self::$startMemory;
        } else {
            $size = memory_get_usage(false);
        }
        // choose units
        $units = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        $i = ($unit) ? array_search($unit, $units) : floor(log($size, 1024));
        // format size
        $mem = round($size / pow(1024, $i), 3);
        // return
        return $mem . $units[$i];
    }

    /**
     * Timer storage
     * @var int 
     */
    static $startMemory = 0;
    static $endMemory = 0;

    /**
     * Start a memory test, 
     * @return int
     */
    public static function startMemoryTest() {
        self::$startMemory = memory_get_usage(false);
        return self::$startMemory;
    }

    /**
     * End a memory test
     * @return int
     */
    public static function endMemoryTest() {
        self::$endMemory = memory_get_usage(false);
        return self::$endMemory;
    }

}
