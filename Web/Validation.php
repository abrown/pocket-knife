<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for validating any given input
 * @uses 
 * @example
 *  // example 1:
 *  $validation = new WebValidation();
 *  $validation->addRule('example', WebValidation::NUMERIC, 'The input is not numeric. Try again!');
 *  $errors = $validation->validate('example', 'not a number...'); // $errors == array('The input is not numeric. Try again!);
 *  // example 2:
 *  if( WebValidation::is( 'not an url', WebValidation::URL ) ){
 *      ...
 *  }
 */
class WebValidation {

    const BOOLEAN = 'is_bool';
    const INTEGER = 'is_int';
    const FLOAT = 'is_float';
    const NUMERIC = 'is_numeric';
    const STRING = 'is_string';
    const SCALAR = 'is_scalar'; // scalar variables are those containing an integer, float, string or boolean.
    // const ARRAY = 'is_array';
    const OBJECT = 'is_object';
    const EMAIL = '~\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b~i';
    const URL = '~(https?|ftp)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?$~iS'; //http://mathiasbynens.be/demo/url-regex
    const NOT_NULL = '!is_null';
    const NOT_EMPTY = 'strlen';
    const IS_NULL = 'is_null';
    const IS_EMPTY = '!strlen';
    
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
     * Adds rule to set. A rule may be:
     *  a function, given in string format, like 'is_string'
     *  a negation function, given in the format '!is_string'
     *  a regex pattern, delimited by ~
     * @param string $id
     * @param string $rule 
     */
    public function addRule($id, $rule, $message = null){
        // add rule
        if( !array_key_exists($id, $this->rules) ) $this->rules[$id] = array($rule);
        else $this->rules[$id][] = $rule;
        // add message
        if( !is_null($message) ){
            $this->addMessage($id, $rule, $message);
        }
    }
    
    /**
     * Adds custom message
     *  messages can include sprintf formatting for $id and $rule (in that order)
     *  e.g.: '%s is not %s', 'id', 'empty'
     * @param type $id
     * @param type $rule
     * @param type $message 
     */
    public function addMessage($id, $rule, $message){
        // add rule
        if( !array_key_exists($id, $this->messages) ) $this->messages[$id] = array($rule=>$message);
        else $this->messages[$id][$rule] = $message;
    }
    
    /**
     * Returns error list
     * @param array $list 
     */
    public function validateList($list){
        $errors = array();
        foreach($list as $id => $value){
            $e = $this->validate($id, $value);
            if( $e ) $errors[$id] = $e;
        }
        return $errors;
    }
    
    /**
     * Returns errors with a given id/value
     * @param string $id
     * @param any $value 
     */
    public function validate($id, $value){
        if( !array_key_exists($id, $this->rules) ) return null;
        $errors = array();
        foreach($this->rules[$id] as $rule){
            if( !$this->is($value, $rule) ) $errors[] = $this->getMessage($id, $rule); 
        }
        return $errors;
    }
    
    /**
     * Checks whether a value conforms to a rule. A rule may be:
     *  a function, given in string format, like 'is_string'
     *  a negation function, given in the format '!is_string'
     *  a regex pattern, delimited by ~
     * @param any $value
     * @param string $rule 
     */
    static public function is($value, $rule){
        // regex
        if( $rule[0] == '~' ){
            return (preg_match($rule, $value)) ? true : false;
        }
        // negation
        elseif( $rule[0] == '!' ){
            $function = substr($rule, 1);
            pr(get_defined_functions());
            if( !function_exists($function) ) throw new Exception("Attempting to validate with unknown function '$function'", 500);
            return (!$function($value)) ? true : false;
        }
        // function
        else{
            $function = $rule;
            if( !function_exists($function) ) throw new Exception("Attempting to validate with unknown function '$function'", 500);
            return ($function($value)) ? true : false;
        }
    }
    
    /**
     * Returns error message; messages can include sprintf formatting for $id and $rule (in that order)
     * @param string $id
     * @param string $rule
     * @return string 
     */
    public function getMessage($id, $rule){
        // get custom messages
        if( array_key_exists($id, $this->messages) && array_key_exists($rule, $this->messages[$id]) ){
           $message = $this->messages[$id][$rule];          
        }
        // get built-in messages
        elseif( $key = array_search($rule, $this->rules) ){
            $message = $this->messages[$key];
        }
        // default
        else{
             $message = $this->messages['DEFAULT'];
        }
        // return
        return sprintf($message, $id, $rule);
    }
}

