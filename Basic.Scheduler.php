<?php
class Scheduler{

    /**
     * Add a task to a scheduling queue
     * @param <string> $id (probably name)
     * @param <string> $code
     * @param <string> $when
     * @param <string> $class
     * @return <mixed> Task
     */
    public function add($id, $code, $when, $class = 'SchedulerTask'){
        $task = new $class($id, $code, $when);
        $task->__prepare();
        $task->create();
        $task->__commit();
        return $task;
    }

    /**
     * Runs all tasks in the specified list
     * @return <int> number of tasks run
     */
    public function run(){
        $count = 0;
        $t = new SchedulerTask();
        $tasks = $t->enumerate();
        foreach($tasks as $task){
            $t->__bind($task);
            if( $t->isDue() ){
                $t->run();
                $count++;
            }
        }
        $t->__commit();
        return $count;
    }

    /**
     * Launches a recurring event by adding it and running it
     * @param <string> $id
     * @param <string> $code
     * @param <string> $when
     * @return <boolean> if run
     */
    public function launch($id, $code, $when){
        // check
        if( !$this->isRecurring($when) ) throw new Exception('Launch() only uses recurring events.', 400);
        // create if necessary
        $t = new SchedulerTask();
        $t->id = $id;
        if(!$t->exists()){
            $t = $this->add($id, $code, $when);
        }
        else $t->read();
        // run if necessary
        if( $t->isDue() ) return $t->run();
        else return false;
    }

    /**
     * Determines whether a time-string wants recurrence
     * @param <string> $when
     * @return <boolean>
     */
    public function isRecurring($when){
        $when = strtolower($when);
        // every...
        if( strpos($when, 'every') !== false ) return true;
        // plural weekdays
        if( preg_match('/mondays|tuesdays|wednesdays|thursdays|fridays|saturdays|sundays/i', $when) ) return true;
        // default
        return false;
    }

    /**
     * Attempts to parse a string into a unix time
     * @param <string> $string
     * @return <int> unix time
     */
    public function toTime( $when ){
        return $this->parse($when);
    }

    /**
     * Attempts to parse a string into a unix time
     * @param <string> $when
     * @return <int> unix time
     */
    public function parse($when){
        // check if set and format
        if( !$when ){ throw new Exception('No string to parse', 400); }
        $when = strtolower( trim($when) );
        // try shortcuts first
        switch( $when ){
            case 'today':
            case 'now':
                $time = time(); // now
            break;
            case 'tomorrow':
                $time = self::next('noon'); // tomorrow at noon
            break;
            case 'yesterday':
                $time = self::last('noon'); // yesterday at noon
            break;
            case 'this week':
            case 'next week':
                $time = self::next('week'); // monday at noon
            break;
            case 'last week':
                $time = self::last('week'); // last monday at noon
            break;
            case '':
            case 'later':
            case 'next month':
                $time = self::next('month'); // next first at noon
            break;
            case 'way later':
            case 'next year':
                $time = self::next('year'); // next first at noon
            break;
            default:
                // try php parse by default
                $time = strtotime($when);
                // normalize to noon
                if( $time !== false && strpos($string, 'at') === false && !preg_match('/\d{3,}/i', $string) ){
                    $time = self::at('noon', $time);
                }
            break;
        }
        // try to return
        if( $time !== false ){
            return $time;
        }
        // begin parsing
        // special case: a couple (of) -> couple
        if( strpos($when, 'a couple') !== false ){
            $when = str_replace(' of', '', $when);
            $when = str_replace('a couple', 'couple', $when);
        }
        // special case: in the -> at
        if( strpos($when, 'in the') !== false ){
            $when = str_replace('in the', 'at', $when);
        }
        // tokenize and compute
        $tokens = preg_split('/ |,/i', $when);
        $max = count($tokens);
        for($i = 0; $i < $max; $i++){
            $token = $tokens[$i];
            if( $token == '' ){
                continue;
            }
            elseif( $token == 'next' ){
                $i++; // consume next token
                $time = self::next($tokens[$i]);
            }
            elseif( $token == 'at' ){
                $i++; // consume next token
                $time = self::at($tokens[$i], $time);
            }
            elseif( $token == 'in' || $token == 'every' ){
                $i++; $i++; // consume next two tokens
                // check if first token is numeric
                $number = $tokens[$i-1];
                if( !is_numeric($number) ){
                    $number = self::getNumber($number);
                    if( $number === false ){
                        $erroneous = $tokens[$i-1];
                        throw new Exception("'$erroneous' should be a number");
                    }
                }
                $in = self::in($number, $tokens[$i]);
                $time = time() + $in;
                // normalize to noon if no 'at' reference and time is 'in' more than a day
                if( strpos($when, 'at') === false && $in > 24*3600 ) $time = self::at('noon', $time);
            }
            elseif( is_numeric($token) ){
                // is 'in' case
                if( isset($tokens[$i+1]) && preg_match('/sec|min|hou|day|wee|mon|yea/i', $tokens[$i+1]) ){
                    $i++;
                    $number = (int) $token;
                    $time = time() + self::in($number, $tokens[$i]);
                    // normalize to noon
                    if( strpos($when, 'at') === false ) $time = self::at('noon', $time);
                }
                // is 'at' case
                else{
                    $time = self::at($token);
                    if( $time < time() ) $time += self::in(1, 'day');
                }
            }
            elseif( self::getNumber($token) ){
                $i++;
                $number = self::getNumber($token);
                $time = time() + self::in($number, $tokens[$i]);
                // normalize to noon
                if( strpos($when, 'at') === false ) $time = self::at('noon', $time);
            }
            elseif( preg_match('/mon|tue|wed|thu|fri|sat|sun/i', $token) ){
                $time = self::next($token);
            }
            elseif( preg_match('/dawn|morn|break|noon|lunch|after|night|dinner|midn/i', $token) ){
                $time = self::at($token);
            }
        }
        // return
        return $time;
    }

    /**
     * Calculates unix time for the next occurrence of a string
     * @param <string> $date
     * @return <int> unix time
     */
    public function next($date){
        $now = getdate();
        switch($date){
            case 'noon':
                $time = $this->at('noon') + $this->in(1, 'day');
            break;
            case 'day':
                $time = $this->at('midnight') + $this->in(1, 'day');
            break;
            case 'week':
                $days = 7 - $now['wday'];
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
            case 'month':
                $year = ($now['mon'] == 12) ? $now['year'] + 1 : $now['year'];
                $month = ($now['mon'] == 12) ? 1 : $now['mon'] + 1;
                $time = mktime(12, 0, 0, $month, 1, $year);
            break;
            case 'year':
                $year = $now['year'] + 1;
                $time = mktime(12, 0, 0, 1, 1, $year);
            break;
            case 'sunday': case 'sun':
                $days = 7 - $now['wday'];
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
            case 'monday': case 'mon':
                $days = fmod(8 - $now['wday'], 7);
                if( $days == 0 ) $days = 7; // same day, next week
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
            case 'tuesday': case 'tue':
                $days = fmod(9 - $now['wday'], 7);
                if( $days == 0 ) $days = 7; // same day, next week
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
            case 'wednesday': case 'wed':
                $days = fmod(10 - $now['wday'], 7);
                if( $days == 0 ) $days = 7; // same day, next week
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
            case 'thursday': case 'thu':
                $days = fmod(11 - $now['wday'], 7);
                if( $days == 0 ) $days = 7; // same day, next week
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
            case 'friday': case 'fri':
                $days = fmod(12 - $now['wday'], 7);
                if( $days == 0 ) $days = 7; // same day, next week
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
            case 'saturday': case 'sat':
                $days = fmod(13 - $now['wday'], 7);
                if( $days == 0 ) $days = 7; // same day, next week
                $time = $this->at('midnight') + $this->in($days, 'days');
            break;
        }
        return $time;
    }

    /**
     * Calculates unix time for the last occurrence of a string
     * @param <string> $date
     * @return <int> unix time
     */
    public function last($date){
        // TODO
    }
    
    /**
     * Returns unix time at the specified time for today's (or specified) date
     * @param <string> $time
     * @param <int> $date
     * @return <int> unix time
     */
    public function at($time, $date = null){
        // get date to change
        if( !$date ) $date = time();
        $now = getdate($date);
        // what time to set
        switch($time){
            case 'noon':
                $_time = mktime(12, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            case 'midnight':
                $_time = mktime(0, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            case 'dawn':
                $_time = mktime(6, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            case 'breakfast':
                $_time = mktime(8, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            case 'morning':
                $_time = mktime(9, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            case 'lunch':
                $_time = mktime(12, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            case 'afternoon':
                $_time = mktime(14, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            case 'night': case 'evening';
                $_time = mktime(18, 0, 0, $now['mon'], $now['mday'], $now['year']);
            break;
            default:
                $hour = $minute = $second = $ampm = 0;
                if( preg_match_all('/(\d)|a|p/i', $time, $matches) ){
                    $numbers = count($matches[1]);
                    foreach($matches[0] as $i => $match){
                        if( $match == 'p' ) $ampm += 12; // am/pm
                        elseif( $i < 2 ) $hour .= $match;
                        elseif( $i < 4 ) $minute .= $match;
                        else $second .= $match;
                    }
                }
                $hour = (int) $hour + $ampm;
                $_time = mktime($hour, (int) $minute, (int) $second, $now['mon'], $now['mday'], $now['year']);
            break;
        }
        // return
        return $_time;
    }

    /**
     * Return a time span in seconds for a number of time intervals
     * @param <int> $number
     * @param <string> $interval
     * @return <int> seconds
     */
    public function in($number, $interval){
        // unpluralize
        $interval = strtolower($interval);
        $last = strlen($interval) - 1;
        if( $interval[$last] == 's' ) $interval = substr($interval, 0, -1);
        // find time
        switch($interval){
            case 'second': $time = $number * 1; break;
            case 'minute': $time = $number * 60; break;
            case 'hour': $time = $number * 3600; break;
            case 'day': $time = $number * 86400; break;
            case 'week': $time = $number * 604800; break;
            case 'month': $time = $number * 18144000; break; // calculated for 30-day months
            case 'year': $time = $number * 31556926; break;
        }
        // return
        return $time;
    }

    /**
     * Return a time span in seconds for a number of time intervals
     * @param <int> $number
     * @param <string> $interval
     * @return <int> seconds
     */
    public function every($number, $interval){
        return self::in($number, $interval);
    }

    /**
     * Returns number represented by string, or false if not found
     * @param <string> $string
     * @return <mixed> number or false
     */
    public function getNumber($string){
        $string = strtolower($string);
        // find number
        switch($string){
            case 'one': case 'a': $number = 1; break;
            case 'two': case 'couple': $number = 2; break;
            case 'three': case 'few': $number = 3; break;
            case 'four': $number = 4; break;
            case 'five': $number = 5; break;
            case 'six': $number = 6; break;
            case 'seven': $number = 7; break;
            case 'eight': $number = 8; break;
            case 'nine': $number = 8; break;
            case 'ten': $number = 10; break;
            case 'eleven': $number = 11; break;
            case 'twelve': case 'dozen': $number = 12; break;
            case 'thirteen': $number = 13; break;
            case 'fourteen': $number = 14; break;
            case 'fifteen': $number = 15; break;
            case 'sixteen': $number = 16; break;
            case 'seventeen': $number = 17; break;
            case 'eighteen': $number = 18; break;
            case 'nineteen': $number = 19; break;
            case 'twenty': case 'score': $number = 20; break;
            default: $number = false; break;
        }
        // return
        return $number;
    }

    /**
     * Returns human-readable formatting for a unix time
     * @param <int> $time
     * @return <string>
     */
    public function getFormattedDate($time){
        return date('H:i:sa \o\n l, j F Y', $time);
    }

    /**
     * Returns sql-readable formatting for a unix time
     * @param <int> $time
     * @return <string>
     */
    public function getSqlDate($time){
        return date('Y-m-d H:i:s', $time);
    }
}

class SchedulerTask extends AppObjectJSON{

    /**
     * Database path
     * @var <string>
     */
    protected $__path = 'scheduler.json';

    /**
     * Public properties
     * @var <string>
     */
    public $id;
    public $code;
    public $output;
    public $when_run;
    public $last_run;
    public $next_run;

    /**
     * Constructor
     * @param <string> $name
     * @param <string> $code
     * @param <string> $when
     */
    public function __construct($id = null, $code = null, $when = null){
        $this->id = $id;
        $this->code = $code;
        $this->when_run = $when;
        // calculate next run
        if( !is_null($this->when_run) ) $this->next_run = $this->calculateNext();
        // do parent code
        parent::__construct();
    }

    /**
     * Check if this task is due to run
     * @return <true>
     */
    public function isDue(){
        return (time() > $this->next_run);
    }

    /**
     * Run this task
     */
    public function run(){
        $this->last_run = time();
        // run
        ob_start();
        eval($this->code);
        $this->output = ob_end_clean();
        // next run?
        $this->calculateNext();
        // replace in db
        $index = $this->index($this->__getID());
        $this->__db[$index] = $this->toClone();
        $this->__changed = true;
        $this->__commit();
    }

    /**
     * Calculate next task time
     */
    private function calculateNext(){
        $this->next_run = Scheduler::parse( $this->when_run ); // every ... == in ...
    }

}