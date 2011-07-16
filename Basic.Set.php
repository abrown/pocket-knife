<?php
/**
 * Description of Set
 *
 * @author andrew
 */
class Set {
    
    static private $cache;
    
    /**
     * Flattens a multi-dimensional array into a single-dimensional, keyed list
     * @staticvar array $result
     * @param any $thing
     * @param string $key
     * @return array 
     */
    static function flatten( $thing, $key = null ){
        self::$cache = array();
        return self::_flatten($thing, $key);
    }

    /**
     * Helps flatten
     * @param type $thing
     * @param type $key
     * @return type 
     */
    static function _flatten( $thing, $key = null ){
        // arrays and objects
        if( is_array($thing) || is_object($thing) ){
            // collapse one-element arrays
            if( is_array($thing) && count($thing) == 1 && key($thing) === 0 ){
                if( $key ) self::$cache[$key] = $thing[0];
                else self::$cache[] = $thing[0];
            }
            // iterate through elements
            else{
                foreach($thing as $_key => $_thing){
                    $k = ($key) ? $key.'.'.$_key : $_key;
                    self::_flatten($_thing, $k);
                }
            }
        }
        // regular elements
        else{
            if( $key ) self::$cache[$key] = $thing;
            else self::$cache[] = $thing;
        }
        // done
        return self::$cache;
    }
}

