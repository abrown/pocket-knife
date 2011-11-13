<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * BasicBenchmark
 * @uses
 */
class BasicBenchmark{
    
    /**
     * Timer storage
     * @var int 
     */
    static $start = 0;
    static $end = 0;
    
    /**
     * Start timer
     * @return <int>
     */
    public static function startTimer(){
        self::$start = microtime(true);
        return self::$start;
    }
    
    /**
     * End timer
     * @return <int>
     */
    public static function endTimer(){
        self::$end = microtime(true);
        return self::$end;
    }
    
    /**
     * Returns time elapsed
     * @return <int>
     */
    public static function getTime(){
        return round(self::$end - self::$start, 5);
    }
    
    /**
     * Returns peak memory usage
     * @return string 
     */
    public static function getPeakMemory(){
        $size = memory_get_peak_usage(false);
        $units = array('b','kb','mb','gb','tb','pb');
        $i = floor( log($size, 1024) );
        $mem = $size / pow(1024, $i);
        $mem = @round( $mem, 2 );
        return $mem.' '.$units[$i];
    }
    
    /**
     * Returns current memory usage
     * @return string
     */
    public static function getMemory(){
        $size = memory_get_usage(false);
        $units = array('b','kb','mb','gb','tb','pb');
        $i = floor( log($size, 1024) );
        $mem = $size / pow(1024, $i);
        $mem = @round( $mem, 2 );
        return $mem.' '.$units[$i];
    }
}