<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides static methods to handle common HTTP-related tasks. These include
 * handling URLs, sanitizing data, and making HTTP requests. Any URL-related
 * tasks that have to do with tokens after the anchor are listed in WebRouting.
 * @uses ExceptionWeb
 */
class WebHttp {

    /**
     * Storest HTTP codes for WebHttp::request();
     * @var int 
     */
    private static $response_code;

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
     * Returns HTTP request tokens from the request URL
     * @example For the URL "http://www.example.com/a/1/delete",
     * this methods returns an array of the form [a, 1, delete]
     * @deprecated fits better in WebRouting
     * @staticvar array $tokens
     * @return array
     */
    static function getTokens() {
        static $tokens = null;
        $pattern = '#\.php/([^?]+)#i';
        if (is_null($tokens)) {
            if (preg_match($pattern, WebHttp::getUrl(), $match)) {
                $tokens = explode('/', $match[1]);
            }
        }
        return $tokens;
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
    
    /**
     * Sanitizes a piece of data for a specific purpose. The function should be
     * read 'sanitize() some $data for $purpose'. sanitize() will recursively 
     * clean objects and arrays. For cleaning URLs, see normalize().
     * @example
     * 
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
                if ($time === false) $data = $default;
                else $data = date('c', $time);
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
        if( empty($data) ) return $default;
        else return $data;
    }

    /**
     * Sends HTTP code to client
     */
    static function setCode($code) {
        if( headers_sent() ) throw new ExceptionWeb('HTTP headers already sent', 400);
        header('HTTP/1.1 ' . intval($code));
    }
    
    /**
     * Sends HTTP content type to client
     * @param string $type 
     */
    static function setContentType($type){
        if( headers_sent() ) throw new ExceptionWeb('HTTP headers already sent', 400);
        header('Content-Type: '.$type);
    }

	/**
	 * Redirects client to the given URL
	 * @param string $url
	 */
    static function redirect($url) {
        if( headers_sent() ) throw new ExceptionWeb('HTTP headers already sent', 400);
        header('Location: ' . $url);
        exit();
    }

    /**
     * Performs HTTP request
     * @example To grab a page: WebHttp::request('www.google.com')
     * @param string $url
     * @param string $method, one of [GET, POST, PUT, DELETE, HEAD]
     * @param string $content, 
     * @param string $content_type, see http://www.iana.org/assignments/media-types/index.html
     * @param array $headers, additional headers, see http://us2.php.net/manual/en/context.http.php
     */
    static function request($url, $method = 'GET', $content = '', $content_type = 'text/html', $headers = array()) {
        $method = strtoupper($method);
        $_headers = array_merge( array('Content-type: '.$content_type), $headers );
        $options = array('http' =>
            array(
                'method' => $method,
                'header' => $_headers,
                'content' => $content
            )
        );
        $context = stream_context_create($options);
        // do request
        $response = file_get_contents($url, false, $context);
        // check errors
        if( $response === false ){
            throw new ExceptionWeb('Could not open url: '.$url, 404);
        }
        // save response code
        if( !isset($http_response_header) ){
            throw new ExceptionWeb('No HTTP request was made', 400);
        }
        $lines = preg_grep('#HTTP/#i', $http_response_header);
        self::$response_code = 0;
        foreach($lines as $line){
            if( preg_match('#HTTP/\d.\d (\d\d\d)#i', $line, $matches) ){
                self::$response_code = intval($matches[1]);
                break;
            }
        }
        // return 
        return $response;
    }
    
    /**
     * Returns HTTP code from last HTTP request made using WebHttp::request()
     * @return int 
     */
    static function getRequestCode(){
        return self::$response_code;
    }
}