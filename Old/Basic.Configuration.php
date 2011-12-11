<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 * 
 * Should work like:
 *  add "Settings::setPath('path/to/Settings.php');"
 *  use "$config = Settings::getInstance(); $config['var']; ..."
 * 
 */
class Settings{

    static private $path;
    static private $instance;
    static private $temp;
    const default_cache_interval = 3600;

    /**
     * Sets path to Settings file
     * @param <string> $path
     * @return <boolean>
     */
    public function setPath($path){
        if( is_file($path) ){
            self::$path = $path;
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Gets Settings path
     * @return <string>
     */
    public function getPath(){
        return self::$path;
    }

    /**
     * Get Settings instance
     * @return <array>
     */
    // TODO: test whether caching Settings is any faster
    static public function getInstance(){
        if( !self::$instance ){
            self::$instance = is_array(self::$instance) ? array_merge(self::read(), self::$instance) : self::read();
        }
        return array_merge(self::$instance, self::$temp);
    }
    
    /**
     * Reset Settings instance
     * @return void
     */
    static public function reset(){
        self::$instance = null;
    }

    /**
     * Read Settings from path
     * @return <array>
     */
    static private function read(){
        if( self::$path && is_file(self::$path)){
            include(self::$path);
            // get vars
            $result = get_defined_vars();
            // remove some
            unset($result['_'], $result['_SERVER'], $result['argv']);
        }
        else{
            throw new Exception('Could not find Settings file: '.self::$path, 500);
        }
        // add base directory and version
        $result['pocket_knife_version'] = 1.0;
        $result['base_dir'] = getcwd();
        // return
        return $result;
    }
    
    /**
     * Write an array to the Settings file
     * @param array $config 
     */
    static public function write($config){
        if( !is_file(self::$path)) throw new Exception('Could not find Settings file: '.self::$path, 500);
        $output = "<?php\n";
        foreach($config as $key => $value){
            $output .= self::write_key($key, $value)."\n";
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
    static private function write_key($key, $value, $_indent = 0){
        $indent = str_repeat("\t", $_indent);
        if( is_object($value) ) throw new Exception('Cannot save objects to Settings file', 500);
        elseif( is_int($value) ) $format = '%s$%s = %d;';
        elseif( is_float($value) ) $format = '%s$%s = %f;';
        elseif( is_string($value) ){ $value = addslashes($value); $format = '%s$%s = \'%s\';'; }
        elseif( is_bool($value) ) $format = ($value) ? '%s$%s = true;' : '%s$%s = false;';
        elseif( is_array($value) ){
            $output = sprintf('%s$%s = array( ', $indent, $key)."\n";
            $_indent++;
            foreach($value as $_key => $_value ){
                $line = self::write_key($_key, $_value, $_indent);
                $line = substr($line, 0, -1).','."\n"; // replace comma for semi-colon
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
     * Retrieves Settings value (convenience method)
     * TODO: add ability to get inside config arrays
     * @param string $key 
     */
    static public function get($key){
        $c = self::getInstance();
        return array_key_exists($key, $c) ? $c[$key] : null;
    }
    
    /**
     * Sets a Settings value (not persistent)
     * TODO: add persistence
     * @param string $key
     * @param any $value 
     */
    static public function set($key, $value){
        self::$temp[$key] = $value;
    }
    
    /**
     * Get Settings array from files
     * @return <array>
     */
    /** OUTDATED

    static public function __install(){
        $settings_pattern = KNIFE_BASE_PATH.DS.'data'.DS.'config'.DS.'*';
        $settings_files = glob($settings_pattern);
        // get Settings files
        foreach($settings_files as $file){
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
     */

}