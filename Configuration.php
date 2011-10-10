<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

//ini_set('display_errors','2');
//ERROR_REPORTING(E_ALL);
//$x = new stdClass();
//$x->t = 1;
//$x->s = 2;
//echo $x->t;
//echo($x->v);
//echo($x->u->x);

/**
 * Configuration
 * @uses ExceptionFile
 * @example
 * Should work like:
 *  add "Configuration::setPath('path/to/configuration.php');"
 *  use "$config = Configuration::getInstance(); $config['var']; ..."
 */
class Configuration {

    /**
     * Current configuration data
     * @var type 
     */
    private $instance;

    /**
     * Path to load/save configuration file
     * @var type 
     */
    private $path;
    
    /**
     * Records whether the data has been changed
     * @var type 
     */
    private $changed = false;

    /**
     * Testing rules
     */
    const MANDATORY = 1;
    const OPTIONAL = 2;
    const SINGLE = 4;
    const MULTIPLE = 8;
    const STRING = 16;
    const NUMERIC = 32;
    const PATH = 64;

    /**
     * Constructor
     * @param array $array 
     */
    public function __construct($array = null) {
        if ($array !== null && is_array($array)) {
            $object = $this->toObject($array);
            $this->instance = $object;
        }
    }
    
    /**
     * Returns current configuration data
     * @return type 
     */
    public function getInstance(){
        if( !$this->instance && $this->path ){
            $this->instance = $this->read();
        }
        return $this->instance;
    }

    /**
     * Gets inaccessible key from current instance
     * @param string $key
     * @return any 
     * @example when calling $configuration->some_property
     */
    public function __get($key) {
        return $this->instance->$key;
    }
    
    /**
     * Gets a key with dot-notation
     * @param string $key 
     * @return any
     * @example when calling $configuration->get('prop.prop2.prop3')
     */
    public function get($key){
        $keys = explode('.', $key);
        $object = $this->instance;
        foreach($keys as $k){
            if( !property_exists($object, $k) ) return null;
            else $object = &$object->$k;
        }
        return $object;
    }
    
    /**
     * Sets inaccessible key from current instance
     * @param string $key
     * @param any $value
     * @return any 
     * @example when calling $configuration->some_property = 'value';
     */
    public function __set($key, $value) {
        $this->changed = true;
        $this->instance->$key = $value;
    }

    /**
     * Sets a key with dot-notation
     * @param string $key 
     * @param any $value
     * @return boolean
     * @example when calling $configuration->get('prop.prop2.prop3')
     */
    public function set($key, $value){
        $this->changed = true;
        $keys = explode('.', $key);
        $object = &$this->instance;
        foreach($keys as $k){
            if( !property_exists($object, $k) ) $object->$k = new stdClass();
            $object = &$object->$k;
        }
        $object = $value;
    }
    
    /**
     * Tests current configuration against a template
     * @param array $template 
     */
    public function validate($template) {
        if( !is_array($template) ) throw new ExceptionConfiguration('Invalid configuration template.', 500);
        foreach($template as $key => $rule){
            if( !is_numeric($rule) ) throw new ExceptionConfiguration("Invalid configuration template rule: '$key' => '$rule'", 500);
            // rules
            $value = $this->get($key);
            $optional = $rule & self::OPTIONAL;
            $testable = true;
            if( $optional && is_null($value) ) $testable = false;
            if( $rule & self::MANDATORY ){
                if( is_null($value) ) throw new ExceptionConfiguration("Invalid configuration: must have '$key' key", 500);
            }
            if( $rule & self::SINGLE && $testable){
                if( is_array($value) || is_object($value) ) throw new ExceptionConfiguration("Invalid configuration: key '$key' must not contain multiple items", 500);
            }
            if( $rule & self::MULTIPLE && $testable){
                if( !is_array($value) && !is_object($value) ) throw new ExceptionConfiguration("Invalid configuration: key '$key' must contain multiple items", 500);
            }
            if( $rule & self::STRING && $testable){
                if( !is_string($value) ) throw new ExceptionConfiguration("Invalid configuration: key '$key' must be a string", 500);
            }
            if( $rule & self::NUMERIC && $testable){
                if( !is_numeric($value) ) throw new ExceptionConfiguration("Invalid configuration: '$key' must be a number", 500);
            }
            if( $rule & self::PATH && $testable){
                if( !is_file($value) && !is_dir($value) && !is_link($value) ) throw new ExceptionConfiguration("Invalid configuration: '$key' must be a valid path", 500);
            }
        }
        return true;
    }

    /**
     * Sets path to configuration file
     * @param <string> $path
     * @return <boolean>
     */
    public function setPath($path) {
        if( !is_file($path) ) throw new ExceptionConfiguration('Could not find configuration file given: '.$path, 500);
        $this->$path = $path;
        return true;
    }

    /**
     * Reset Configuration instance
     * @return void
     */
    public function reset() {
        $this->changed = true;
        $this->$instance = null;
    }

    /**
     * Read configuration from path
     * @return <array>
     */
    static public function read( $path ) {
        if( !$path || !is_file($path) ) throw new ExceptionConfiguration('Could not find configuration file: '.$path, 500);
        $info = pathinfo($path);
        switch( $info['extension'] ){
            // JSON
            case 'json':
                $content = file_get_contents($path);
                $result = json_decode($content);
            break;
            // PHP
            case 'php':
                include(self::$path);
                $result = get_defined_vars();
                unset($result['_'], $result['_SERVER'], $result['argv']);
                $result = $this->toObject($result);
            break;
            // LINUX-STYLE
            // TODO: could probably speed this up
            case '.config':
            default:
                $result = new stdClass();
                $lines = file($path);
                $heading = false;
                foreach( $lines as $line ){
                    // remove comment
                    $line = preg_replace('/#.*$/', '', $line);
                    // empty line
                    if( preg_match('/^\s*$/m', $lines, $match) ){
                        continue;
                    }
                    // heading change
                    elseif( preg_match('/^\[(.*)\]\s*$/m', $lines, $match) ){
                        $heading = $match[1];
                        $result->$heading = new stdClass();
                        continue;
                    }
                    // new key/pair
                    elseif( strpos($line, '=') !== false ){
                        list($key, $value) = explode('=', $line);
                        // get key
                        $key = trim($key);
                        // get value
                        $value = trim($value);
                        if( strpos($value, ',') !== false ){
                            $value = explode(',', $value);
                            array_walk($value, 'trim');
                        }
                        // set
                        if( $heading ) $result->$heading->$key = $value;
                        else $result->$key = $value;
                    }
                }
            break;
        }
        // return
        return $result;
    }

    /**
     * Write an array to the configuration file
     * @param array $config 
     */
    static public function write($config) {
        if (!is_file(self::$path))
            throw new ExceptionFile('Could not find configuration file: ' . self::$path, 500);
        $output = "<?php\n";
        foreach ($config as $key => $value) {
            $output .= self::write_key($key, $value) . "\n";
        }
        $output .= "?>";
        return file_put_contents(self::$path, $output);
    }

    /**
     * Creates a PHP string representing the given $key and $value
     * @param any $key
     * @param any $value
     * @param int $_indent
     * @return string 
     */
    static private function write_key($key, $value, $_indent = 0) {
        $indent = str_repeat("\t", $_indent);
        if (is_object($value))
            throw new Exception('Cannot save objects to configuration file', 500);
        elseif (is_int($value))
            $format = '%s$%s = %d;';
        elseif (is_float($value))
            $format = '%s$%s = %f;';
        elseif (is_string($value)) {
            $value = addslashes($value);
            $format = '%s$%s = \'%s\';';
        } elseif (is_bool($value))
            $format = ($value) ? '%s$%s = true;' : '%s$%s = false;';
        elseif (is_array($value)) {
            $output = sprintf('%s$%s = array( ', $indent, $key) . "\n";
            $_indent++;
            foreach ($value as $_key => $_value) {
                $line = self::write_key($_key, $_value, $_indent);
                $line = substr($line, 0, -1) . ',' . "\n"; // replace comma for semi-colon
                $line = preg_replace('/\$([^ ]+) *=/', '\'$1\' =>', $line, 1); // replace = with =>
                $output .= $line;
            }
            $output .= sprintf('%s); ', $indent);
            return $output;
        }
        // return
        return sprintf($format, $indent, $key, $value);
    }

    /**
     * Converts arrays into objects
     * From Richard Castera, http://www.richardcastera.com/blog/php-convert-array-to-object-with-stdclass
     * @param array $array
     * @return stdClass 
     */
    private function toObject($array) {
        // case: all values/objects
        if (!is_array($array)) {
            return $array;
        }
        // create object
        $object = new stdClass();
        // case: is valid array
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name => $value) {
                $name = strtolower(trim($name));
                if ( strlen($name) > 0 ) {
                    $object->$name = $this->toObject($value);
                }
            }
            return $object;
        }
        // case: nothing to return
        else{
            return null;
        }
    }
    

    /**
     * Get configuration array from files
     * @return <array>
     */
    /** OUTDATED

      static public function __install(){
      $configuration_pattern = KNIFE_BASE_PATH.DS.'data'.DS.'config'.DS.'*';
      $configuration_files = glob($configuration_pattern);
      // get configuration files
      foreach($configuration_files as $file){
      if( is_file($file) ){
      include($file);
      }
      }
      unset($file);
      // get vars
      $result = get_defined_vars();
      // remove some
      unset($result['_'], $result['_SERVER'], $result['argv']);
      // return
      return $result;
      }
     * 
     * Retrieves configuration value (convenience method)
     * TODO: add ability to get inside config arrays
     * @param string $key 
    static public function get($key) {
        $c = self::getInstance();
        return array_key_exists($key, $c) ? $c[$key] : null;
    }

     * Sets a configuration value (not persistent)
     * TODO: add persistence
     * @param string $key
     * @param any $value 
    static public function set($key, $value) {
        self::$temp[$key] = $value;
    }
     * 
     */
}