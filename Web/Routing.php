<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * WebRouting
 * @uses
 */
class WebRouting{

    /**
     * Set URL anchor
     * @param <string> $string
     */
    public function setAnchor($string){
        // TODO: do we need a setAnchor?
    }

    /**
     * Get URL anchor
     * @return <string>
     */
    public function getAnchor(){
        return '.php';
    }

    /**
     * Get current request URL
     * @return <string>
     */
    public static function getUrl() {
        static $url = null;
        if( $url === null ){
            $url = 'http';
            if( array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on') $url .= 's';
            $url .= '://'.$_SERVER['SERVER_NAME'];
            if( $_SERVER['SERVER_PORT'] != '80') $url .= ':'.$_SERVER['SERVER_PORT'];
            $url .= $_SERVER['REQUEST_URI'];
        }
        return $url;
    }

    /**
     * Get URL from start through anchor
     * @return <string>
     */
    public static function getLocationUrl(){
        static $location_url = null;
        if( $location_url === null ){
            if( !self::getAnchor() ) throw new ExceptionWeb('No anchor set', 500);
            $url = self::getUrl();
            $anchor = self::getAnchor();
            $start = 0;
            $end = strpos( $url, $anchor ) + strlen( $anchor );
            $location_url = substr( $url, $start, $end );
        }
        return $location_url;
    }
    
    /**
     * Returns filename after anchor
     * @return string 
     */
    public static function getAnchoredUrl(){
        static $anchored_url = null;
        if( $anchored_url === null ){
            if( !self::getAnchor() ) throw new ExceptionWeb('No anchor set', 500);
            $anchored = strpos(self::getUrl(), self::getAnchor());
            if( $anchored === false  ) throw new ExceptionWeb('Anchor not found in URL', 400);
            $start = $anchored + strlen( self::getAnchor() );
            $end = strpos( self::getUrl(), '?', $start );
            if( $end === false ) $end = strlen( self::getUrl() );
            $anchored_url = substr( self::getUrl(), $start, $end - $start );
        }
        return $anchored_url;
    }

    /**
     * Get tokens from a REST-style URL
     * @return <array> List of tokens
     */
    public static function parse() {
        static $tokens = null;
        if( $tokens === null ){
            $tokens = self::getTokens();
            // parse
            reset($tokens);
            $object = current($tokens);
            $id = next($tokens);
            $action = next($tokens);
            if( !$action ){
                // case: entities/enumerate
                if( $id && !is_numeric($id) ) $action = $id;
                // case: entities/23
                else if( $id ){
                    $method = self::getMethod(); // use HTTP method
                    if( $method ) $action = $method;
                    else $action = 'exists';
                }
                // case: entities/
                else $action = 'enumerate';
            }
            // save
            $tokens = array(
                'object' => $object,
                'id' => $id,
                'action' => $action
            );
        }
        // return
        return $tokens;
    }

    /**
     * Get tokens from a REST-style URL and return by index
     * @return <array> List of tokens
     */
    public static function getTokens() {
        static $tokens = null;
        if( $tokens === null ){
            $token_string = self::getAnchoredUrl();
            // split and remove empty
            $tokens = explode('/', $token_string);
            foreach($tokens as $index => $token){
                if( strlen($token) < 1 ) unset($tokens[$index]);
            }
            if( !$tokens ) throw new ExceptionWeb('No URL tokens', 400);
        }
        return $tokens;
    }


    /**
     * Get specified token
     * @return <string>
     */
    public static function getToken($key){
        if( is_int($key) ) $tokens = self::getTokens();
        else $tokens = self::parse();
        return $tokens[$key];
    }

    /**
     * Get classname from entity token
     * @staticvar <string> $classname
     * @return <string>
     */
    public function getClassname(){
        static $classname = null;
        if( $classname === null ){
            $inflector = new LanguageInflection( self::getToken('object') );
            $classname = $inflector->toSingular()->toCamelCaseStyle()->toString();
        }
        return $classname;
    }

    /**
     * Get entity name in readable format (right now, just as classname)
     * @return <string> name
     */
    public function getName(){
        return self::getClassname();
    }
    
    /**
     * Get request method
     * Checks REQUEST for a method-like key such as 'PUT', then checks the
     * REQUEST_METHOD
     * @return <string>
     */
    public function getMethod(){
        $map = array(
            'HEAD' => 'exists',
            'POST' => 'create',
            'GET' => 'read',
            'PUT' => 'update',
            'DELETE' => 'delete'
        );
        $http_request_methods = array_keys($map);
        $method = null;
        // look through request
        foreach($_REQUEST as $key => $value){
            $KEY = strtoupper($key);
            if( in_array($key, $http_request_methods) ) $method = $KEY;
            if( in_array($KEY, $http_request_methods) ) $method = $KEY;
        }
        // look at server requesr_method
        if(!$method) $method = $_SERVER['REQUEST_METHOD'];
        // map
        return $map[$method];
    }
}