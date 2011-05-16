<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Http {

    /**
     * Get request URL
     * @staticvar <string> $url
     * @return <string> Request URL
     */
    static function getUrl() {
        static $url = null;
        if( is_null($url) ) {
            $url = 'http';
            // check https
            if( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ) { $url .= 's'; }
            $url .= '://'.$_SERVER['SERVER_NAME'];
            // check port
            if( $_SERVER['SERVER_PORT'] != '80' ) {
                $url .= ':'.$_SERVER['SERVER_PORT'];
            }
            // add uri
            $url .= $_SERVER['REQUEST_URI'];
        }
        return $url;
    }

    /**
     * Get request URI
     * @return <string> Request URI
     */
    static function getUri() {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Get request tokens
     * E.g.: http://www.example.com/a/1/delete -> [a, 1, delete]
     * @staticvar <array> $tokens
     * @return <array>
     */
    static function getTokens() {
        static $tokens = null;
        $pattern = '#\.php/([^?]+)#i';
        if( is_null($tokens) ){
            if( preg_match($pattern, Http::getUrl(), $match) ){
                $tokens = explode('/', $match[1]);
            }
        }
        return $tokens;
    }

    /**
     * Get request method
     * @return <string> Method
     */
    static function getMethod() {
        $types = array('GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'UPDATE');
        if( $type = array_intersect(array_keys($_GET), $types) ){
            return $type[0];
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * TODO: implement getQuery function to get query string
     */
    static function getQuery(){

    }

    /**
     * Get a cleaned parameter
     * @param <string> $parameter
     * @param <string> $clean type
     * @return <mixed>
     */
    static function getParameter($parameter = null, $clean = false){
        $out = null;
        // get parameter
        if( $parameter ){
            if( array_key_exists($parameter, $_GET) ) $out = $_GET[$parameter];
            elseif( array_key_exists($parameter, $_POST) ) $out = $_POST[$parameter];
            elseif( strtoupper($parameter) == 'GET' ) $out = $_POST;
            elseif( strtoupper($parameter) == 'POST' ) $out = $_POST;
        }
        // clean?
        if( $clean ){
            $out = self::clean($out, $clean);
        }
        // return
        return $out;
    }

    /**
     * Cleans inputs according to type
     * TODO: test
     * @param <mixed> $input
     * @param <string> $type
     * @return <mixed>
     */
    static function clean($input, $type = 'text'){
        // recurse
        if( is_array($input) ){
            foreach($input as &$item){
                $item = self::clean($item, $type);
            }
        }
        // clean
        switch($type){
            // remove all non-url characters
            case 'url':
                return preg_replace('/![a-zA-Z0-9\.\/-_]/', '', $input);
            break;
            // make it a safe string (nothing but normal letters)
            default:
            case 'string':
            case 'text':
                return preg_replace('/![a-zA-Z0-9\.\/-_ ]/', ' ', $input);
            break;
            // date format
            case 'date':
                $time = strtotime($input);
                if( $time === false ) return null;
                else return date('Y-m-d H:i:s', $time);
            break;
            // clean for html
            case 'html':
                $out = preg_replace('#<br ?/>|&nbsp;#i', ' ', $input);
                $out = strip_tags($out);
                $out = htmlentities($out);
                return $out;
            break;
            // clean/prepare for sql
            case 'sql':
                return addslashes($input); // really? tag as todo
            break;
            // to integer
            case 'int':
            case 'integer':
            case 'number':
                return intval($input);
            break;
            // to float
            case 'float':
                return floatval($input);
            break;
        }
    }

    /**
     * Send HTTP code
     * @return <void>
     */
    static function setCode($code){
        header( 'HTTP/1.1 '.intval($code) );
    }

    /**
     * Redirect
     * @return <void>
     */
    static function redirect($url){
        header( 'Location: '.$url );
    }
}