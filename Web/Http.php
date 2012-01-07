<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides static methods to handle common HTTP-related tasks.
 */
class WebHttp {

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
        if ($type = array_intersect(array_keys($_GET), $types)) {
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
     * Cleans inputs according to type
     * TODO: test
     * @param mixed $input
     * @param string $type one of [url, string, date, html, integer, float]  
     * @return mixed
     */
    static function clean($input, $type = 'text') {
        // recurse
        if (is_array($input) || is_object($input)) {
            foreach ($input as &$item) {
                $item = self::clean($item, $type);
            }
            return $input;
        }
        // clean
        switch ($type) {
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
                if ($time === false)
                    return null;
                else
                    return date('Y-m-d H:i:s', $time);
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
     * Sends HTTP code to client
     */
    static function setCode($code) {
        header('HTTP/1.1 ' . intval($code));
    }
    
    /**
     * Sends HTTP content type to client
     * @param string $type 
     */
    static function setContentType($type){
        header('Content-Type: '.$type);
    }

	/**
	 * Redirects client to the given URL
	 * @param string $url
	 */
    static function redirect($url) {
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
        // return 
        return $response;
    }
    
    /**
     * Returns HTTP code from last HTTP request made using WebHttp::request()
     * TODO: test
     * @return int 
     */
    static function getCode(){
        if( !$http_response_header ) throw new ExceptionWeb('No HTTP request was made', 400);
        $lines = preg_grep('#HTTP/#i', $http_response_header);
        foreach($lines as $line){
            if( preg_match('#HTTP/\d.\d (\d\d\d)#i', $line, $matches) ) return intval($matches[1]);
        }
        return 0;
    }
}