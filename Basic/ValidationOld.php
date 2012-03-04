<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for validating any given input
 * @uses ExceptionForbidden, WebUrl
 * @example
 * $a = 'google.com';
 * $errors = BasicValidation::validate($a, BasicValidation::NOT_EMPTY|BasicValidation::STRING|BasicValidation::URL);
 * pr($errors); // prints an array of rule failures
 * 
 * $_a = BasicValidation::sanitize($a, BasicValidation::STRING|BasicValidation::URL);
 * pr($_a); // $_a should now pass the validation tests it failed before 
 */
class BasicValidation {
    
    /**
     * TYPES
     */
    const IS_NULL = 1;
    const BOOLEAN = 2;
    const INTEGER = 4;
    const FLOAT = 8;
    const STRING = 16;
    const OBJECT = 32;
    const IS_ARRAY = 64;

    /**
     * META-TYPES
     */
    const EITHER_OR = 0;
    const SCALAR = 256;
    const NUMERIC = 512;
    const IS_EMPTY = 1024;
    const NOT_EMPTY = 2048;

    /**
     * STRING-TYPES
     */
    const ALPHANUMERIC = 16384;
    const EMAIL = 32768;
    const URL = 65536;
    const DATE = 131072;
    const HTML = 262144;
    const SQL = 524288;

    /**
     * Checks whether a value conforms to a set of rules. Rules
     * are created by ORing the BasicValidation constants into a bitmask
     * @example
     * $bitmask = BasicValidation::STRING | BasicValidation::EMAIL;
     * BasicValidation::is('address@site.com', $bitmask); // returns true
     * BasicValidation::is(42, $bitmask); // returns false
     * @param mixed $value
     * @param int $bitmask 
     */
    public static function is($value, $bitmask){
        // complex validation: cycle through options
        if( $bitmask & self::EITHER_OR ){
            $max = 524288;
            for($i=1; $i<=$max; $i*=2){
                if( self::_is($value, $i) ){
                    return true;
                }
            }
            return false;
        }
        // simple validation
        else{
            return self::_is($value, $bitmask);
        }
    }
    
    /**
     * Work method for is()
     * @param mixed $value
     * @param int $bitmask
     * @return boolean 
     */
    protected static function _is($value, $bitmask) {
        // TYPES
        if ($bitmask & self::IS_NULL) {
            if (!is_null($value))
                return false;
        }
        elseif ($bitmask & self::BOOLEAN) {
            if (!is_bool($value))
                return false;
        }
        elseif ($bitmask & self::INTEGER) {
            if (!is_int($value))
                return false;
        }
        elseif ($bitmask & self::FLOAT) {
            if (!is_float($value))
                return false;
        }
        elseif ($bitmask & self::STRING) {
            if (!is_string($value))
                return false;
        }
        elseif ($bitmask & self::OBJECT) {
            if (!is_object($value))
                return false;
        }
        elseif ($bitmask & self::IS_ARRAY) {
            if (!is_array($value))
                return false;
        }
        // META-TYPES
        if ($bitmask & self::SCALAR) {
            if (!is_scalar($value))
                return false;
        }
        if ($bitmask & self::NUMERIC) {
            if (!is_numeric($value))
                return false;
        }
        if ($bitmask & self::IS_EMPTY) {
            if (!empty($value))
                return false;
        }
        elseif ($bitmask & self::NOT_EMPTY) {
            if (empty($value))
                return false;
        }
        // STRING
        if ($bitmask & self::ALPHANUMERIC) {
            $regex = '~^[a-z0-9 _-]+$~i';
            if (!preg_match($regex, $value))
                return false;
        }
        if ($bitmask & self::EMAIL) {
            $regex = '~^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$~i';
            if (!preg_match($regex, $value))
                return false;
        }
        if ($bitmask & self::URL) {
            // from: http://mathiasbynens.be/demo/url-regex
            $regex = '~(https?|ftp)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?$~iS';
            if (!preg_match($regex, $value))
                return false;
        }
        if ($bitmask & self::DATE) {
            if (strtotime($value) === false)
                return false;
        }
        if ($bitmask & self::HTML) {
            libxml_use_internal_errors(true);
            libxml_clear_errors();
            $xml = simplexml_load_string($value);
            if (count(libxml_get_errors()) > 0)
                return false;
        }
        if ($bitmask & self::SQL) {
            // TODO:
            $regex = '~^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER)\s~i';
            if (!preg_match($regex, $value))
                return false;
        }
        // all else failed,
        return true;
    }

    /**
     * Returns error messages based on failed rules
     * @param mixed $value
     * @param int $bitmask 
     * @return array
     */
    public static function validate($value, $bitmask) {
        // set error messages for each rule
        $messages = array(
            self::IS_NULL => '"%s" is not null',
            self::BOOLEAN => '"%s" is not boolean',
            self::INTEGER => '"%s" is not an integer',
            self::FLOAT => '"%s" is not a float',
            self::STRING => '"%s" is not a string',
            self::OBJECT => '"%s" is not a PHP object',
            self::IS_ARRAY => '"%s" is not an array',
            self::SCALAR => '"%s" is not a scalar value (integer, float, string or boolean)',
            self::NUMERIC => '"%s" is not numeric',
            self::IS_EMPTY => '"%s" is not empty according to PHP\'s empty() function',
            self::NOT_EMPTY => '"%s" is empty according to PHP\'s empty() function',
            self::ALPHANUMERIC => '"%s" contains more than letters, numbers, hyphens, underscores, and spaces',
            self::EMAIL => '"%s" is not a valid e-mail address',
            self::URL => '"%s" is not a valid URL',
            self::DATE => '"%s" is not a valid date according to PHP\'s strtotime() function',
            self::HTML => '"%s" is not valid HTML, i.e. cannot be parsed by libxml',
            self::SQL => '"%s" is not a valid SQL statement'
        );
        // loop through applicable rules and compile errors
        $errors = array();
        foreach ($messages as $_bitmask => $message) {
            if( $bitmask & $_bitmask ){
                if( !self::is($value, $_bitmask) ){
                    $errors[] = sprintf($message, $value);
                }
            }  
        }
        // return
        return $errors;
    }
    
    /**
     * Given a list of values, this function will test them against a 
     * list of rules; uses wildcard '*' functionality
     * @example
     * $list = array('a' => 1, 'b' => 2, 'c' => 3);
     * $rules = array('*' => BasicValidation::NUMERIC, 'a' => BasicValidation::FLOAT);
     * $errors = BasicValidation::validateList($list, $rules);
     * // $errors will contain an entry like 'a => "1" is not a float'
     * @param array $list
     * @param array $rules
     * @return array 
     */
    public static function validateList($list, $rules){
        $errors = array();
        foreach($list as $key => $value){
            // test specific rules
            if(array_key_exists($key, $rules)){
                $e = self::validate($value, $rules[$key]);
                if( $e ) $errors[$key] = $e;
            }
            // test wildcard '*' rules
            if(array_key_exists('*', $rules)){
                $e = self::validate($value, $rules['*']);
                if( $e ) $errors[$key] = array_merge($errors[$key], $e);
            }
        }
        // return 
        return $errors;
    }

    /**
     * Sanitizes a value for a specific purpose; uses the same constants as
     * validate() and is().
     * @example
     * // for HTTP input:
     * $cleaned_username = BasicValidation::sanitize($_GET['username'], WebValidation::ALPHANUMERIC);
     * // for HTML (cleans tags , etc.):
     * $cleaned_html = BasicValidation::sanitize($_GET['html'], WebValidation::HTML, '<b>No data given</b>');
     * @param mixed $value
     * @param int $bitmask one or more of the constants defined in BasicValidation
     * @param mixed $default the value to return if all else fails
     * @return mixed
     */
    public static function sanitize($value, $bitmask, $default = null) {
        $out = null;
        // TYPES
        if ($bitmask & self::IS_NULL) {
            return null;
        }
        elseif ($bitmask & self::BOOLEAN) {
            if( self::is($value, self::BOOLEAN) ) $out = $value;
            else $out = $default;
        }
        elseif ($bitmask & self::INTEGER) {
            if( self::is($value, self::INTEGER) ) $out = $value;
            else $out = $default;
        }
        elseif ($bitmask & self::FLOAT) {
            if( self::is($value, self::FLOAT) ) $out = $value;
            else $out = $default;
        }
        elseif ($bitmask & self::STRING) {
            if( self::is($value, self::STRING) ) $out = $value;
            else $out = $default;
        }
        elseif ($bitmask & self::OBJECT) {
            if( self::is($value, self::OBJECT) ) $out = $value;
            else $out = $default;
        }
        elseif ($bitmask & self::IS_ARRAY) {
            if( self::is($value, self::IS_ARRAY) ) $out = $value;
            else $out = $default;
        }
        // META-TYPES
        if ($bitmask & self::SCALAR) {
            if( self::is($value, self::SCALAR) ) $out = $value;
            else $out = $default;
        }
        if ($bitmask & self::NUMERIC) {
            if( self::is($value, self::NUMERIC) ) $out = floatval($value);
            else $out = $default;
        }
        if ($bitmask & self::IS_EMPTY) {
            if( self::is($value, self::IS_EMPTY) ) $out = $value;
            else $out = $default;
        }
        elseif ($bitmask & self::NOT_EMPTY) {
            if( self::is($value, self::NOT_EMTPY) ) $out = $value;
            else $out = $default;
        }
        // STRING
        if ($bitmask & self::ALPHANUMERIC) {
            $regex = '~![a-zA-Z0-9 _-]~i';
            $out = preg_replace($regex, '', $value);
        }
        if ($bitmask & self::EMAIL) {
            if( self::is($value, self::EMAIL) ) $out = $value;
            else $out = $default;
        }
        if ($bitmask & self::URL) {
            $out = WebUrl::normalize($value);
            if( !self::is($out, self::URL) ) $out = $default;
        }
        if ($bitmask & self::DATE) {
            $out = strtotime($value); // returns timestamp
            if( $out === false ) $out = $default;
        }
        if ($bitmask & self::HTML) {
            $out = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        if ($bitmask & self::SQL) {
            $out = mysql_real_escape_string($value);
            if( !$out ) $out = $default;
        }
        // return
        return $out;
    }
    
    /**
     *
     * @param type $list
     * @param type $rules
     * @param type $defaults
     * @return type 
     */
    public static function sanitizeList($list, $rules, $defaults = null){
        if( is_scalar($list) ){
            return self::sanitize($list, $rules, $defaults);
        }
        elseif( is_array($list) || is_object($list) ){
            // setup
            if( is_array($list) ) $out = array();
            else $out = new stdClass();
            // loop through values
            foreach($list as $key => $value){
                // get specific rule
                $rule = 0;
                if( array_key_exists($key, $rules) ) $rule |= $rules[$key];
                if( array_key_exists('*', $rules) ) $rule |= $rules['*'];
                // get specific default
                $default = null;
                if( is_array($defaults) && array_key_exists('*', $defaults) ) $default = $defaults['*'];
                if( is_array($defaults) && array_key_exists($key, $defaults) ) $default = $defaults[$key];
                // sanitize
                if( is_array($list) ) $out[$key] = self::sanitizeList($value, $rule, $default);
                else $out->$key = self::sanitizeList($value, $rule, $default);
            }
            return $out;
        }
        else{
            throw new ExceptionForbidden("Unknown type to sanitize", 500);
        }
    }
}