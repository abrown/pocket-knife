<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Settings
 * @uses ExceptionFile
 * @example
 * Should work like:
 *  add "Settings::setPath('path/to/Settings.php');"
 *  use "$config = Settings::getInstance(); $config['var']; ..."
 */
class Settings {

    /**
     * Current Settings data
     * @var object 
     */
    private $instance;

    /**
     * Path to load/save Settings file
     * @var string
     */
    private $path;

    /**
     * Records whether the data has been changed
     * @var boolean
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
    public function __construct($list = null) {
        if ($list === null) {
            
        } elseif (is_array($list)) {
            $object = to_object($list);
            $this->instance = $object;
        } elseif (is_object($list)) {
            $this->instance = $list;
        }
    }

    /**
     * Returns current Settings data
     * @return type 
     */
    public function getInstance() {
        if (!$this->instance && $this->path) {
            $this->instance = $this->read();
        }
        return $this->instance;
    }

    /**
     * Checks inaccessible keys in current instance for existence
     * @param string $key
     * @return booolean
     * @example when calling property_exists( $Settings, $key );
     * */
    public function __isset($key) {
        return isset($this->instance->$key);
    }

    /**
     * Gets inaccessible key from current instance
     * @param string $key
     * @return any 
     * @example when calling $Settings->some_property
     */
    public function __get($key) {
        if (!isset($this->instance->$key))
            return null;
        else
            return $this->instance->$key;
    }

    /**
     * Gets a key with dot-notation
     * @param string $key 
     * @return any
     * @example when calling $Settings->get('prop.prop2.prop3')
     */
    public function get($key) {
        $keys = explode('.', $key);
        $object = $this->instance;
        foreach ($keys as $k) {
            if (!property_exists($object, $k))
                return null;
            else
                $object = &$object->$k;
        }
        return $object;
    }

    /**
     * Sets inaccessible key from current instance
     * @param string $key
     * @param any $value
     * @return any 
     * @example when calling $Settings->some_property = 'value';
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
     * @example when calling $Settings->get('prop.prop2.prop3')
     */
    public function set($key, $value) {
        $this->changed = true;
        $keys = explode('.', $key);
        $object = &$this->instance;
        foreach ($keys as $k) {
            if (!property_exists($object, $k))
                $object->$k = new stdClass();
            $object = &$object->$k;
        }
        $object = $value;
    }

    /**
     * Tests current Settings against a template
     * @param array $template 
     */
    public function validate($template) {
        if (!is_array($template))
            throw new ExceptionSettings('Invalid Settings template.', 500);
        foreach ($template as $key => $rule) {
            if (!is_numeric($rule))
                throw new ExceptionSettings("Invalid Settings template rule: '$key' => '$rule'", 500);
            // rules
            $value = $this->get($key);
            $optional = $rule & self::OPTIONAL;
            $testable = true;
            if ($optional && is_null($value))
                $testable = false;
            if ($rule & self::MANDATORY) {
                if (is_null($value))
                    throw new ExceptionSettings("Invalid Settings: must have '$key' key", 500);
            }
            if ($rule & self::SINGLE && $testable) {
                if (is_array($value) || is_object($value))
                    throw new ExceptionSettings("Invalid Settings: key '$key' must not contain multiple items", 500);
            }
            if ($rule & self::MULTIPLE && $testable) {
                if (!is_array($value) && !is_object($value))
                    throw new ExceptionSettings("Invalid Settings: key '$key' must contain multiple items", 500);
            }
            if ($rule & self::STRING && $testable) {
                if (!is_string($value))
                    throw new ExceptionSettings("Invalid Settings: key '$key' must be a string", 500);
            }
            if ($rule & self::NUMERIC && $testable) {
                if (!is_numeric($value))
                    throw new ExceptionSettings("Invalid Settings: '$key' must be a number", 500);
            }
            if ($rule & self::PATH && $testable) {
                if (!is_file($value) && !is_dir($value) && !is_link($value))
                    throw new ExceptionSettings("Invalid Settings: '$key' must be a valid path", 500);
            }
        }
        return true;
    }

    /**
     * Sets path to Settings file
     * @param <string> $path
     * @return <boolean>
     */
    public function setPath($path) {
        if (!is_file($path))
            throw new ExceptionSettings('Could not find Settings file given: ' . $path, 500);
        $this->$path = $path;
        return true;
    }

    /**
     * Reset Settings instance
     * @return void
     */
    public function reset() {
        $this->changed = true;
        $this->$instance = null;
    }

    /**
     * Read Settings from path
     * @return <array>
     */
    static public function read($path) {
        if (!$path || !is_file($path))
            throw new ExceptionSettings('Could not find Settings file: ' . $path, 500);
        $info = pathinfo($path);
        switch ($info['extension']) {
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
                $result = to_object($result);
                break;
            // LINUX-STYLE
            // TODO: could probably speed this up
            case '.config':
            default:
                $result = new stdClass();
                $lines = file($path);
                $heading = false;
                foreach ($lines as $line) {
                    // remove comment
                    $line = preg_replace('/#.*$/', '', $line);
                    // empty line
                    if (preg_match('/^\s*$/m', $lines, $match)) {
                        continue;
                    }
                    // heading change
                    elseif (preg_match('/^\[(.*)\]\s*$/m', $lines, $match)) {
                        $heading = $match[1];
                        $result->$heading = new stdClass();
                        continue;
                    }
                    // new key/pair
                    elseif (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line);
                        // get key
                        $key = trim($key);
                        // get value
                        $value = trim($value);
                        if (strpos($value, ',') !== false) {
                            $value = explode(',', $value);
                            array_walk($value, 'trim');
                        }
                        // set
                        if ($heading)
                            $result->$heading->$key = $value;
                        else
                            $result->$key = $value;
                    }
                }
                break;
        }
        // return
        return $result;
    }

    /**
     * Write an array to the Settings file
     * @param array $config 
     */
    static public function write($config) {
        if (!is_file(self::$path))
            throw new ExceptionFile('Could not find Settings file: ' . self::$path, 500);
        $output = "<?php\n";
        foreach ($config as $key => $value) {
            $output .= self::write_key($key, $value) . "\n";
        }
        $output .= "?>";
        return file_put_contents(self::$path, $output);
    }

    /**
     * Writes a Settings to a JSON file
     * @param object $config
     * @param string $path
     * @return boolean 
     */
    static private function writeJson($config, $path) {
        $json = json_encode($config);
        return file_put_contents($path, $json);
    }

    /**
     * Writes a Settings to a PHP file
     * @param type $config
     * @param type $path 
     * @return boolean
     */
    static private function writePhp($config, $path) {
        $output = "<?php\n";
        foreach ($config as $key => $value) {
            $output .= self::writePhpKey($key, $value) . "\n";
        }
        $output .= "?>";
        return file_put_contents($path, $output);
    }

    /**
     * Creates a PHP string representing the given $key and $value
     * @param any $key
     * @param any $value
     * @param int $_indent
     * @return string 
     */
    static private function writePhpKey($key, $value, $_indent = 0) {
        $indent = str_repeat("\t", $_indent);
        if (is_object($value))
            throw new Exception('Cannot save objects to Settings file', 500);
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

    static public function writeConfig($config, $path) {
        $output = '';
        $depth = MathSet::getDepth($config);
        // case: no headings needed
        if ($depth < 3) {
            foreach ($config as $key => $value) {
                $output .= self::writeConfigKey($key, $value) . "\n";
            }
        }
        // case: headings needed
        else {
            // find top-level keys
            $scalar = array();
            $non_scalar = array();
            foreach ($config as $key => $value) {
                if (is_scalar($value))
                    $scalar[] = $key;
                else
                    $non_scalar[] = $key;
            }
            // output
            foreach ($scalar as $key) {
                $output .= self::writeConfigKey($key, $config->$key);
            }
            foreach ($non_scalar as $key) {
                $output .= "[$key]\n";
                $output .= self::writeConfigKey($key, $config->$key);
                $output .= "\n";
            }
        }
        // write
        return file_put_contents($path, $output);
    }

    /**
     * Writes a config key in linux-style format
     * @param string $key
     * @param any $value
     * @return string 
     */
    static private function writeConfigKey($key, $value) {
        if (is_array($value)) {
            // test if all keys are integers
            $keys = array_keys();
            $all_integer_test = true;
            foreach ($keys as $key) {
                if (!is_int($key)) {
                    $all_integer_test = false;
                    break;
                }
            }
            // case: numeric array
            if ($all_integer_test) {
                $output = sprintf('%s = %s', $key, implode(', ', $value)) . "\n";
            }
            // case: associative array
            else {
                $output = '';
                foreach ($value as $k => $v) {
                    $output .= self::writeConfigKey($key . '.' . $k, $v) . "\n";
                }
            }
        }
        // case: object
        elseif (is_object($value)) {
            $output = '';
            foreach ($value as $k => $v) {
                $output .= self::writeConfigKey($key . '.' . $k, $v) . "\n";
            }
        }
        // case: scalars
        else {
            $output = sprintf('%s = %s', $key, $value) . "\n";
        }
        // return
        return $output;
    }

}