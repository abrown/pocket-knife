<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for validating any given input;
 * uses HTTP code 416 Requested Range Not Satisfiable to signify that some
 * validation condition is not met
 * (see http://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
 * @example
 * $x = 42;
 * BasicValidation::with($x)->isInteger(); // returns BasicValidation object
 * BasicValidation::with($x)->isString()->isEmail(); // throws Error
 * @uses Error
 */
class BasicValidation {

    /**
     * value to test against
     * @var mixed 
     */
    protected $value;

    /**
     * Name of the value to test; changes with property/key traversal
     * @var string 
     */
    protected $name = 'input';

    /**
     * For hierarchical values, allows walking using withProperty()/withKey() and up()
     * @var array 
     */
    protected $stack = array();

    /**
     * Used to skip rules for an optional property/key that is not present in the object
     * @var array
     */
    protected $unavailable_optional_properties = array();

    /**
     * Factory constructor for BasicValidation
     */
    static public function with(&$value) {
        return new BasicValidation($value);
    }

    /**
     * Constructor
     * @param mixed $value
     */
    public function __construct(&$value) {
        $this->value = $value;
    }

    /**
     * Moves to a property within an object
     * @param string $property 
     * @return BasicValidation
     */
    public function withProperty($property) {
        if ($this->isOptional()) {
            return $this;
        }
        if ($this->hasProperty($property)) {
            array_push($this->stack, array($this->name, $this->value));
            $this->name = $this->name . '.' . $property;
            $this->value = $this->value->$property;
        }
        return $this;
    }

    /**
     * Moves to an optional property within an object, or skips the following
     * rules
     * @param string $property
     * @return BasicValidation 
     */
    public function withOptionalProperty($property) {
        if (!is_object($this->value)) {
            throw new Error("'{$this->name}' is not an object.", 416);
        }
        // case: unavailable
        if (!isset($this->value->$property)) {
            array_push($this->stack, array($this->name, $this->value));
            $this->name = $this->name . '.' . $property;
            $this->value = null;
            array_push($this->unavailable_optional_properties, $this->name);
        }
        // case: available
        else {
            array_push($this->stack, array($this->name, $this->value));
            $this->name = $this->name . '.' . $property;
            $this->value = $this->value->$property;
        }
        // return
        return $this;
    }

    /**
     * Returns whether the current rule is part of an optional property/key
     * @return boolean 
     */
    protected function isOptional() {
        return end($this->unavailable_optional_properties) === $this->name;
    }

    /**
     * Check for property existence in an object; uses isset() instead of 
     * property_existst() to handle classes with property overloading 
     * (i.e. __isset, __unset).
     * @param string $property 
     * @return BasicValidation
     */
    public function hasProperty($property) {
        if (!is_object($this->value))
            throw new Error("'{$this->name}' is not an object.", 416);
        if (!isset($this->value->$property))
            throw new Error("Property '$property' does not exist in '{$this->name}'.", 416);
        return $this;
    }

    /**
     * Check that a property does not exist in an object; uses property_exists()
     * to ensure the property is not named but has value of null. 
     * @param string $property 
     * @return BasicValidation
     */
    public function hasNoProperty($property) {
        if (!is_object($this->value))
            throw new Error("'{$this->name}' is not an object.", 416);
        if (property_exists($this->value, $property))
            throw new Error("Property '$property' must not exist in '{$this->name}'.", 416);
        return $this;
    }

    /**
     * Moves to a key within an array
     * @param string $key
     * @return BasicValidation 
     */
    public function withKey($key) {
        if($this->isOptional())
            return $this;
        if ($this->hasKey($key)) {
            array_push($this->stack, array($this->name, $this->value));
            $this->name = $this->name . '.' . $key;
            $this->value = $this->value[$key];
        }
        return $this;
    }

    /**
     * Moves to an optional key within an object, or skips the following
     * rules
     * @param string $key
     * @return BasicValidation 
     */
    public function withOptionalKey($key) {
        if (!is_array($this->value)) {
            throw new Error("'{$this->name}' is not an object.", 416);
        }
        // case: unavailable
        if (!isset($this->value[$key])) {
            array_push($this->stack, array($this->name, $this->value));
            $this->name = $this->name . '.' . $key;
            $this->value = null;
            array_push($this->unavailable_optional_properties, $this->name);
        }
        // case: available
        else {
            array_push($this->stack, array($this->name, $this->value));
            $this->name = $this->name . '.' . $key;
            $this->value = $this->value[$key];
        }
        // return
        return $this;
    }

    /**
     * Check for key existence in an array
     * @param string $key 
     * @return BasicValidation
     */
    public function hasKey($key) {
        if (!is_array($this->value))
            throw new Error("'{$this->name}' is not an array.", 416);
        if (!isset($this->value[$key]))
            throw new Error("Key '$key' does not exist in '{$this->name}'.", 416);
        return $this;
    }

    /**
     * Check that a key does not exist in an array; uses array_key_exists()
     * to ensure the key is not named but has value of null. 
     * @param string $key 
     * @return BasicValidation
     */
    public function hasNoKey($key) {
        if (!is_object($this->value))
            throw new Error("'{$this->name}' is not an object.", 416);
        if (array_key_exists($key, $this->value))
            throw new Error("Property '$key' must not exist in '{$this->name}'.", 416);
        return $this;
    }

    /**
     * Returns to a higher-level value; opposite of withKey() and withProperty()
     * @return BasicValidation
     */
    public function upOne() {
        // go to higher level
        list($this->name, $this->value) = array_pop($this->stack);
        // exit optional blanket
        if ($this->isOptional())
            array_pop($this->unavailable_optional_properties);
        // return
        return $this;
    }

    /**
     * Returns to highest-level value
     * @return BasicValidation 
     */
    public function upAll() {
        // go to highest level
        list($this->name, $this->value) = $this->stack[0];
        $this->stack = array();
        // exit optional blanket
        $this->unavailable_optional_properties = array();
        // return
        return $this;
    }

    /**
     * Check whether the value in question is one the passed parameters
     * @example BasicValidation::with($var)->oneOf('a', 'b', 'c');
     * @param mixed
     * @return BasicValidation 
     */
    public function oneOf() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        foreach (func_get_args() as $parameter) {
            if ($this->value === $parameter)
                return $this;
        }
        // else
        throw new Error("'{$this->name}' is not one of [" . implode(', ', func_get_args()) . '].', 416);
    }

    /**
     * Check whether this value is null
     * @return BasicValidation 
     */
    public function isNull() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_null($this->value)) {
            throw new Error("'{$this->name}' is not null.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether this value is boolean
     * @return BasicValidation 
     */
    public function isBoolean() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_bool($this->value)) {
            throw new Error("'{$this->name}' is not boolean.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether this value is an integer
     * @return BasicValidation 
     */
    public function isInteger() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_int($this->value)) {
            throw new Error("'{$this->name}' is not an integer.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the value is greater than a given number
     * @param mixed $number
     * @return BasicValidation
     * @throws Error 
     */
    public function isGreaterThan($number) {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if ($this->value <= $number) {
            throw new Error("'{$this->name}' is not greater than '$number'.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the value is less than a given number
     * @param mixed $number
     * @return BasicValidation
     * @throws Error 
     */
    public function isLessThan($number) {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if ($this->value >= $number) {
            throw new Error("'{$this->name}' is not less than '$number'.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether this value is a float
     * @return BasicValidation 
     */
    public function isFloat() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_float($this->value)) {
            throw new Error("'{$this->name}' is not a float.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is numeric
     * @return BasicValidation 
     */
    public function isNumeric() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_numeric($this->value)) {
            throw new Error("'{$this->name}' is not numeric.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether this value is a string
     * @return BasicValidation 
     */
    public function isString() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_string($this->value)) {
            throw new Error("'{$this->name}' is not a string.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether this value is an array
     * @return BasicValidation 
     */
    public function isArray() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_array($this->value)) {
            throw new Error("'{$this->name}' is not an array.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether this value is an object
     * @return BasicValidation 
     */
    public function isObject() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_object($this->value)) {
            throw new Error("'{$this->name}' is not an object.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether this value is an object or an array
     * @return BasicValidation
     */
    public function isObjectOrArray() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_object($this->value) && !is_array($this->value)) {
            throw new Error("'{$this->name}' is not an object or an array.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is not an object, array, or resoure
     * @return BasicValidation 
     */
    public function isScalar() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_scalar($this->value)) {
            throw new Error("'{$this->name}' is not scalar; it is an object, array, or resource.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is empty
     * @return BasicValidation 
     */
    public function isEmpty() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!empty($this->value)) {
            throw new Error("'{$this->name}' is not empty according to PHP's empty() function.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is not empty
     * @return BasicValidation 
     */
    public function isNotEmpty() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (empty($this->value)) {
            throw new Error("'{$this->name}' is empty according to PHP's empty() function.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given string is alpha-numeric
     * @return BasicValidation 
     */
    public function isAlphanumeric() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        $regex = '~^[a-z0-9 _-]+$~i';
        if (!preg_match($regex, $this->value)) {
            throw new Error("'{$this->name}' is not alpha-numeric.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the string is under a specified length
     * @param int $length
     * @return BasicValidation
     * @throws Error 
     */
    public function hasLengthUnder($length) {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!is_string($this->value)) {
            throw new Error("'{$this->name}' must be a string to have a length under {$length}.", 416);
        }
        if (strlen($this->value) >= $length) {
            throw new Error("'{$this->name}' must have a length under {$length}.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is a valid e-mail address
     * @return BasicValidation 
     */
    public function isEmail() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        $regex = '~^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$~i';
        if (!preg_match($regex, $this->value)) {
            throw new Error("'{$this->name}' is not a valid e-mail address.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is a valid URL
     * @return BasicValidation 
     */
    public function isUrl() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        // from: http://mathiasbynens.be/demo/url-regex
        $regex = '~(https?|ftp)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?$~iS';
        if (!preg_match($regex, $this->value)) {
            throw new Error("'{$this->name}' is not a valid URL.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is a valid path
     * @return BasicValidation 
     */
    public function isPath() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test whether the file or folder is accessible
        if (!file_exists($this->value)) {
            throw new Error("'{$this->name}', with location '{$this->value}', is not a valid path.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is a PHP-readable date
     * @return BasicValidation 
     */
    public function isDate() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (@strtotime($this->value) === false) {
            throw new Error("'{$this->name}' is not a valid date.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given string is valid HTML
     * @return BasicValidation 
     */
    public function isHtml() {// is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string($this->value);
        if (count(libxml_get_errors()) > 0) {
            throw new Error("'{$this->name}' is not valid HTML.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the given value is valid SQL
     * @return BasicValidation 
     */
    public function isSql() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        throw new Error('Not yet implemented.', 500);
        // return
        return $this;
    }

    /**
     * Check whether a string conforms to a regular expression
     * @example
     * BasicValidation::with(...)->isRegex('/[a-z ]+/i'); // only allow letters and spaces
     * @param string $regex_pattern
     * @return BasicValidation
     */
    public function matches($regex_pattern) {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if (!preg_match($regex_pattern, $this->value)) {
            throw new Error("'{$this->name}' does not match the regular expression: {$regex_pattern}.", 416);
        }
        // return
        return $this;
    }

    /**
     * Check whether the current value is a valid Settings object
     * @return \BasicValidation
     * @throws Error
     */
    public function isSettings() {
        // is optional?
        if ($this->isOptional()) {
            return $this;
        }
        // test
        if ($this->value === null || !is_a($this->value, 'Settings')) {
            throw new Error("'{$this->name}' is not a valid Settings object; the value being checked must be an instance of the Settings class.", 500);
        }
        // return
        return $this;
    }

}