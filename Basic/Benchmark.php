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
     * Starts timer
     * @return float the microseconds-time when the timer was started
     */
    public static function startTimer(){
        self::$start = microtime(true);
        return self::$start;
    }
    
    /**
     * Ends timer
     * @return float the microseconds-time when the timer was stopped
     */
    public static function endTimer(){
        self::$end = microtime(true);
        return self::$end;
    }
    
    /**
     * Returns time elapsed
     * @return float the number of seconds from startTime() to endTime()
     */
    public static function getTime(){
        return round(self::$end - self::$start, 5);
    }
    
    /**
     * Returns peak memory usage
     * @return string 
     */
    public static function getPeakMemory($unit = null){
        $size = memory_get_peak_usage(false);
        // choose units
        $units = array('b','kb','mb','gb','tb','pb');
        if( $unit ){ 
            $i = array_search($unit, $units);
        }
        else{
            $i = floor( log($size, 1024) );
        }
        // format size
        $mem = $size / pow(1024, $i);
        $mem = @round( $mem, 2 );
        // return
        return $mem.' '.$units[$i];
    }
    
    /**
     * Returns current memory usage
     * @return string
     */
    public static function getMemory($unit = null){
        $size = memory_get_usage(false);
        // choose units
        $units = array('b','kb','mb','gb','tb','pb');
        if( $unit ){ 
            $i = array_search($unit, $units);
        }
        else{
            $i = floor( log($size, 1024) );
        }
        // format size
        $mem = $size / pow(1024, $i);
        $mem = @round( $mem, 2 );
        // return
        return $mem.' '.$units[$i];
    }
}