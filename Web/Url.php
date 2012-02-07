<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides static methods to handle common URL-related tasks. 
 * These include all types of URL parsing, building, and 
 * normalizing.
 * @uses ExceptionWeb
 */
class WebUrl{

    /**
     * Returns the HTTP request URL
     * @staticvar string $url
     * @return string
     */
    static function getUrl() {
        static $url = null;
        if (is_null($url)) {
            $url = 'http';
            // check https
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $url .= 's';
            }
            $url .= '://' . $_SERVER['SERVER_NAME'];
            // check port
            if ($_SERVER['SERVER_PORT'] != '80') {
                $url .= ':' . $_SERVER['SERVER_PORT'];
            }
            // add uri
            $url .= $_SERVER['REQUEST_URI'];
        }
        return $url;
    }

    /**
     * Returns the HTTP request URI
     * @example
     * For a URL like "http://www.example.com/index.php?etc", the URI
     * returned will be "/index.php?etc"
     * @return string
     */
    static function getUri() {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Returns HTTP request method. Checks the request URI 
     * for a parameter like "PUT" or "POST" before checking the 
     * real HTTP method. This allows all types of requests from 
     * the browser.
     * @return string one of [GET, PUT, POST, DELETE, HEAD, LIST]
     */
    static function getMethod() {
        $types = array('GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'LIST');
        if ($_GET && $type = array_intersect(array_keys($_GET), $types)) {
            return $type[0];
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get a parameter from the HTTP request; will clean the 
     * value using Http::clean() if necessary
     * @param string $parameter a key in the GET or POST array
     * @param boolean $clean a boolean determining whether to use Http::clean() on the parameter
     * @return mixed
     */
    static function getParameter($parameter = null, $clean = false) {
        $out = null;
        // get parameter
        if ($parameter) {
            if (array_key_exists($parameter, $_GET))
                $out = $_GET[$parameter];
            elseif (array_key_exists($parameter, $_POST))
                $out = $_POST[$parameter];
            elseif (strtoupper($parameter) == 'GET')
                $out = $_GET;
            elseif (strtoupper($parameter) == 'POST')
                $out = $_POST;
        }
        // clean?
        if ($clean) {
            $out = self::clean($out, $clean);
        }
        // return
        return $out;
    }

    /**
     * Normalizes a URL according to the rules in RFC 3986
     * @link http://en.wikipedia.org/wiki/URL_normalization
     * @param string $url 
     * @return string
     */
    static function normalize($url){
        $parsed_url = parse_url($url);
        if( isset($parsed_url['query']) ) parse_str($parsed_url['query'], $parsed_query);
        else $parsed_query = array();
        // convert to lower case
        $parsed_url['scheme'] = strtolower($parsed_url['scheme']);
        $parsed_url['host'] = strtolower($parsed_url['host']);
        if( isset($parsed_url['path']) ) $parsed_url['path'] = strtolower($parsed_url['path']);
        // convert non-alphanumeric characters (except -_.~) to hex
        if( isset($parsed_url['user']) ) $parsed_url['user'] = rawurlencode($parsed_url['user']);
        if( isset($parsed_url['pass']) ) $parsed_url['pass'] = rawurlencode($parsed_url['pass']);
        if( isset($parsed_url['fragment']) ) $parsed_url['fragment'] = rawurlencode($parsed_url['fragment']);
        if( isset($parsed_url['query']) ) $parsed_url['query'] = http_build_query($parsed_query, '', '&');
        // remove dot segments (see http://tools.ietf.org/html/rfc3986, Remove Dot Segments)
        if( isset($parsed_url['path']) ){
            $input = explode('/', $parsed_url['path']);
            $output = array();
            while( count($input) ){
                $segment = array_shift($input);
                if( $segment == '' ) continue;
                if( $segment == '.' ) continue;
                if( $segment == '..' && count($output) ){ array_pop($output); continue; }
                array_push($output, $segment);
            }
            if( $parsed_url['path'][0] == '/' ) $parsed_url['path'] = '/';
            else $parsed_url['path'] == '';
            $parsed_url['path'] .= implode('/', $output);
        }
        // add trailing / to directories
        if( isset($parsed_url['path']) && !isset($parsed_url['query']) && !isset($parsed_url['fragment']) ){
            $last_slash = strrpos($parsed_url['path'], '/');
            $last_char = strlen($parsed_url['path']) - 1;
            if( strpos($parsed_url['path'], '.', $last_slash) === false && $parsed_url['path'][$last_char] !== '/' ){
                $parsed_url['path'] .= '/';
            }
        }
        elseif( !isset($parsed_url['path']) ){
            $parsed_url['path'] = '/';
        }
        // re-build (from http://us2.php.net/manual/en/function.parse-url.php#106731)
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) && $parsed_url['port'] != '80' ? ':' . $parsed_url['port'] : ''; // remove default port
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        $url = "$user$pass$host$port$path$query$fragment";
        // remove duplicate slashes
        $url = str_replace('//', '/', $url);
        $url = $scheme.$url;
        // return
        return $url;
    }
}