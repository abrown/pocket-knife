<?php
class Scheduler{

    public function addMultiple($list){
        foreach($list as $item){
            $this->add($item->code, $item->when, $item->callback);
        }
    }
    public function add($code, $when, $callback){

    }
    public function run(){
        
    }

    public function toTime( $when ){
        // check if set and format
        if( !$when ){ throw new Exception('No string to parse', 400); }
        $when = strtolower( trim($when) );
        // act on it: try shortcuts or parse
        switch( $when ){
            case 'today':
            case 'now':
                $time = time(); // now
            break;
            case 'tomorrow':
                $time = $this->next('noon'); // tomorrow at noon
            break;
            case 'yesterday':
                $time = $this->last('noon'); // yesterday at noon
            break;
            case 'this week':
            case 'next week':
                $time = $this->next('week'); // monday at noon
            break;
            case 'last week':
                $time = $this->last('week'); // last monday at noon
            break;
            case '':
            case 'later':
            case 'next month':
                $time = $this->next('month'); // next first at noon
            break;
            case 'way later':
            case 'next year':
                $time = $this->next('year'); // next first at noon
            break;
            default:
                $time = $this->parse($this->due); // parse
            break;
        }
        // convert to sql time
        $this->due = date('Y-m-d H:i:s', $time);
    }

    /**
     * Prints the conversion for a given string
     * @param <string> $string
     */
    public function test($string){
        $this->due = $string;
        $this->beforeCreate();
        $time = $this->getFormattedDate($this->due);
        pr("'$string' => '$this->due' => '$time'");
    }

    /**
     * Attempts to parse a string into a unix time
     * @param <string> $string
     * @return <int> unix time
     */
    public function parse($string){
        $string = strtolower($string);
        $time = null;
        // try php parse
        $time = strtotime($string);
        if( $time !== false ){
            // normalize to noon
            if( strpos($string, 'at') === false && !preg_match('/\d{3,}/i', $string) ) $time = $this->at('noon', $time);
            return $time;
        }
        // special cases
        if( strpos($string, 'a couple') !== false ){
            $string = str_replace('a couple', 'couple', $string);
        }
        if( strpos($string, 'in the') !== false ){
            $string = str_replace('in the', 'at', $string);
        }
        // tokenize and compute
        $tokens = preg_split('/ |,/i', $string);
        $max = count($tokens);
        for($i = 0; $i < $max; $i++){
            $token = $tokens[$i];
            if( $token == '' ){
                continue;
            }
            elseif( $token == 'next' ){
                $i++; // consume next token
                $time = $this->next($tokens[$i]);
            }
            elseif( $token == 'at' ){
                $i++; // consume next token
                $time = $this->at($tokens[$i], $time);
            }
            elseif( $token == 'in' ){
                $i++; $i++; // consume next two tokens
                // check if first token is numeric
                $number = $tokens[$i-1];
                if( !is_numeric($number) ){
                    $number = $this->getNumber($number);
                    if( $number === false ){
                        $erroneous = $tokens[$i-1];
                        throw new Exception("'$erroneous' should be a number");
                    }
                }
                $time = time() + $this->in($number, $tokens[$i]);
                // normalize to noon
                if( strpos($string, 'at') === false  ) $time = $this->at('noon', $time);
            }
            elseif( is_numeric($token) ){
                // is 'in' case
                if( isset($tokens[$i+1]) && preg_match('/sec|min|hou|day|wee|mon|yea/i', $tokens[$i+1]) ){
                    $i++;
                    $number = (int) $token;
                    $time = time() + $this->in($number, $tokens[$i]);
                    // normalize to noon
                    if( strpos($string, 'at') === false ) $time = $this->at('noon', $time);
                }
                // is 'at' case
                else{
                    $time = $this->at($token);
                    if( $time < time() ) $time += $this->in(1, 'day');
                }
            }
            elseif( $this->getNumber($token) ){
                $i++;
                $number = $this->getNumber($token);
                $time = time() + $this->in($number, $tokens[$i]);
                // normalize to noon
                if( strpos($string, 'at') === false ) $time = $this->at('noon', $time);
            }
            elseif( preg_match('/mon|tue|wed|thu|fri|sat|sun/i', $token) ){
                $time = $this->next($token);
            }
            elseif( preg_match('/dawn|morn|break|noon|lunch|after|night|dinner|midn/i', $token) ){
                $time = $this->at($token);
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
        }
        // return
        return $time;
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
     * Returns preferred formatting for a unix time
     * @param <int> $time
     * @return <string>
     */
    public function getFormattedDate($time){
        if( is_string($time) ) $time = strtotime($time);
        return date('H:i:sa \o\n l, j F Y', $time);
    }

}