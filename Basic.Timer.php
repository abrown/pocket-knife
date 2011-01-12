<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Timer{

    static $start = 0;
    static $end = 0;

    /**
     * Start timer
     * @return <int>
     */
    static function start(){
        self::$start = microtime(true);
        return self::$start;
    }

    /**
     * End timer
     * @return <int>
     */
    function end(){
        self::$end = microtime(true);
        return self::$end;
    }

    /**
     * Return result
     * @return <string>
     */
    function toString(){
        return self::$end - self::$start;
    }

    /**
     * toString clone
     * @return <string>
     */
    function result(){
        return self::toString();
    }
}