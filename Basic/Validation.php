<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for validating any given input
 * @uses 
 * @example
 * // example 1:
 * $validation = new BasicValidation();
 * $validation->addRule('example', BasicValidation::NUMERIC, 'The input is not numeric. Try again!');
 * $errors = $validation->validate('example', 'not a number...'); // $errors == array('The input is not numeric. Try again!);
 * 
 * // example 2:
 * if( BasicValidation::is( 'not an url', WebValidation::URL ) ){
 *   ...
 * }
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

    /**
     * META-TYPES
     */
    const STRICT = 128;
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
     * Holds rules
     * @var array 
     */
    private $rules = array();
    private $messages = array(
        'DEFAULT' => '"%s" failed rule "%s"',
        'BOOLEAN' => '"%s" is not boolean',
        'INTEGER' => '"%s" is not an integer',
        'FLOAT' => '"%s" is not a float',
        'NUMERIC' => '"%s" is not numeric',
        'STRING' => '"%s" is not a string',
        'SCALAR' => '"%s" is not a scalar value (integer, float, string or boolean)',
        'OBJECT' => '"%s" is not a PHP object',
        'EMAIL' => '"%s" is not a valid e-mail',
        'URL' => '"%s" is not a valid URL',
        'NOT_NULL' => '"%s" is null',
        'NOT_EMPTY' => '"%s" is empty',
        'IS_NULL' => '"%s" must be null',
        'IS_EMPTY' => '"%s" must be empty',
    );

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
    static public function is($value, $bitmask) {
        // TYPES
        if ($bitmask & self::IS_NULL) {
            if (!is_null($value))
                return false;
        }
        elseif ($bitmask & self::BOOLEAN) {
            if (!is_boolean($value))
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
            // STRING
            if ($bitmask & self::ALPHANUMERIC) {
                $regex = '~[a-z0-9 -_]~i';
                if (!preg_match($regex, $value))
                    return false;
            }
            if ($bitmask & self::EMAIL) {
                $regex = '~\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b~i';
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
                if (!strtotime($value))
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
                $regex = '~^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER)\w~i';
                if (!preg_match($regex, $value))
                    return false;
            }
        } elseif ($bitmask & self::OBJECT) {
            if (!is_object($value))
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
            if (!is_empty($value))
                return false;
        }
        if ($bitmask & self::NOT_EMPTY) {
            if (is_empty($value))
                return false;
        }
        // all else failed,
        return true;
    }

    /**
     * Adds rule to set. 
     * @example
     * A rule may be:
     * a function, given in string format, like 'is_string'
     * a negation function, given in the format '!is_string'
     * a regex pattern, delimited by ~
     * @param string $id
     * @param string $rule 
     */
    public function addRule($id, $rule, $message = null) {
        // add rule
        if (!array_key_exists($id, $this->rules))
            $this->rules[$id] = array($rule);
        else
            $this->rules[$id][] = $rule;
        // add message
        if (!is_null($message)) {
            $this->addMessage($id, $rule, $message);
        }
    }

    /**
     * Adds custom message
     * messages can include sprintf formatting for $id and $rule (in that order)
     * e.g.: '%s is not %s', 'id', 'empty'
     * @param type $id
     * @param type $rule
     * @param type $message 
     */
    public function addMessage($id, $rule, $message) {
        // add rule
        if (!array_key_exists($id, $this->messages))
            $this->messages[$id] = array($rule => $message);
        else
            $this->messages[$id][$rule] = $message;
    }

    /**
     * Returns error list
     * @param array $list 
     */
    public function validateList($list) {
        $errors = array();
        foreach ($list as $id => $value) {
            $e = $this->validate($id, $value);
            if ($e)
                $errors[$id] = $e;
        }
        return $errors;
    }

    /**
     * Returns errors with a given id/value
     * @param string $id
     * @param any $value 
     */
    public function validate($id, $value) {
        if (!array_key_exists($id, $this->rules))
            return null;
        $errors = array();
        foreach ($this->rules[$id] as $rule) {
            if (!$this->is($value, $rule))
                $errors[] = $this->getMessage($id, $rule);
        }
        return $errors;
    }

    /**
      static public function is($value, $rule){
      // regex
      if( $rule[0] == '~' ){
      return (preg_match($rule, $value)) return false;
      }
      // negation
      elseif( $rule[0] == '!' ){
      $function = substr($rule, 1);
      pr(get_defined_functions());
      if( !function_exists($function) ) throw new Exception("Attempting to validate with unknown function '$function'", 500);
      return (!$function($value)) return false;
      }
      // function
      else{
      $function = $rule;
      if( !function_exists($function) ) throw new Exception("Attempting to validate with unknown function '$function'", 500);
      return ($function($value)) return false;
      }
      }
     * 
     */

    /**
     * Returns error message; messages can include sprintf formatting for $id and $rule (in that order)
     * @param string $id
     * @param string $rule
     * @return string 
     */
    public function getMessage($id, $rule) {
        // get custom messages
        if (array_key_exists($id, $this->messages) && array_key_exists($rule, $this->messages[$id])) {
            $message = $this->messages[$id][$rule];
        }
        // get built-in messages
        elseif ($key = array_search($rule, $this->rules)) {
            $message = $this->messages[$key];
        }
        // default
        else {
            $message = $this->messages['DEFAULT'];
        }
        // return
        return sprintf($message, $id, $rule);
    }

    /**
     * Sanitizes a piece of data for a specific purpose. The function should be
     * read 'sanitize() some $data for $purpose'. sanitize() will recursively 
     * clean objects and arrays. For cleaning URLs, see normalize().
     * @example
     * // for SQL:
     * $cleaned_vars = WebHttp::sanitize($_GET, 'sql');
     * // for HTML:
     * echo WebHttp::sanitize($unsafe_html, 'html', '<b>No data given</b>');
     * @param mixed $input
     * @param string $type one of [alphanumeric, date, html, sql, integer, float]
     * @param mixed $default the value to return if $data is empty (uses PHP empty() function)
     * @return mixed
     */
    static function sanitize($data, $type = 'alphanumeric', $default = null) {
        // recurse
        if (is_array($data) || is_object($data)) {
            foreach ($data as &$item) {
                $data = self::sanitize($data, $type, $default);
            }
            return $data;
        }
        // clean
        switch ($type) {
            // make it a safe string (nothing but normal letters and numbers, plus ./-_ )
            default:
            case 'alphanumeric':
                $data = preg_replace('/![a-zA-Z0-9\.\/-_ ]/', ' ', $data);
                break;
            // date format, using ISO 8601 (http://www.w3.org/TR/NOTE-datetime)
            case 'date':
                $time = strtotime($data);
                if ($time === false)
                    $data = $default;
                else
                    $data = date('c', $time);
                break;
            // clean for html, prevents XSS
            case 'html':
                $data = htmlspecialchars($data, ENT_QUOTES);
                break;
            // clean/prepare for SQL statement
            case 'sql':
                $data = mysql_real_escape_string($data);
                break;
            // to integer
            case 'integer':
                $data = intval($data);
                break;
            // to float
            case 'float':
                $data = floatval($data);
                break;
        }
        // return
        if (empty($data))
            return $default;
        else
            return $data;
    }

}