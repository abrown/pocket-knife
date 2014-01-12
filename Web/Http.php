<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides static methods to handle common HTTP-related tasks. These include
 * making both analyzing and sending HTTP requests. URL-related tasks are found
 * in WebUrl.
 * @uses Error
 */
class WebHttp {

    /**
     * Stores HTTP codes for WebHttp::request();
     * @var int 
     */
    private static $responseCode;

    /**
     * Returns HTTP request method. Checks the request URI 
     * for the 'method' parameter before checking the 
     * real HTTP method. This allows all types of requests from 
     * the browser.
     * @return string one of [GET, PUT, POST, DELETE, HEAD, LIST, OPTIONS]
     */
    static function getMethod() {
        // check for parameter
        if (array_key_exists('method', $_GET)) {
            return $_GET['method'];
        }
        // check server 
        if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
            return $_SERVER['REQUEST_METHOD'];
        }
        // else:
        return null;
    }

    /**
     * Determine if the HTTP method exists
     * @param string $method
     * @return boolean
     */
    static function isValidMethod($method) {
        return in_array($method, array('GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'OPTIONS'));
    }

    /**
     * Get a parameter from the HTTP request; searches GET and POST
     * to find the parameter
     * @param string $parameter a key in the GET or POST array
     * @return mixed
     */
    static function getParameter($parameter = null) {
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
        // return
        return $out;
    }

    /**
     * Returns the Content-Type of the incoming HTTP request. Checks the request
     * URI first for a 'content-type' parameter; then uses apache-as-a-module
     * to find a content-type; defaults to null
     * @return string 
     */
    static function getContentType() {
        if (array_key_exists('content-type', $_GET)) {
            return $_GET['content-type'];
        }
        // try apache_request_headers(); only works when PHP is installed as a module on Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (array_key_exists('Content-Type', $headers)) {
                $parts = explode(';', $headers['Content-Type']);
                return trim($parts[0]);
            }
        }
        // try $_SERVER[CONTENT_TYPE]; may need to set a RewriteRule in .htaccess; see stackoverflow.com/questions/5519802
        if (array_key_exists('CONTENT_TYPE', $_SERVER) && $_SERVER['CONTENT_TYPE']) {
            return trim(strtok($_SERVER['CONTENT_TYPE'], ';'));
        }
        // otherwise, pull from the Accept header
        return self::getAccept();
        // else
        return null;
    }

    /**
     * Returns the MIME type the client is requesting; 
     * defaults to null
     * @return string 
     */
    static function getAccept() {
        $accept = null;
        // look first in $_GET; allows users to manually specify Accept header
        if (array_key_exists('accept', $_GET)) {
            $accept = $_GET['accept'];
        }
        // try $_SERVER[HTTP_ACCEPT]
        else if (array_key_exists('HTTP_ACCEPT', $_SERVER) && $_SERVER['HTTP_ACCEPT']) {
            $accept = trim(strtok($_SERVER['HTTP_ACCEPT'], ','));
        }
        // else
        return $accept;
    }

    /**
     * Return Etag (in If-None-Match header) sent by the client 
     * in order to determine whether the client's cached resource 
     * is up-to-date. See RFC2616, Section 13.3.
     * @return string
     */
    static function getIfNoneMatch() {
        if (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER) && $_SERVER['HTTP_IF_NONE_MATCH']) {
            return trim($_SERVER['HTTP_IF_NONE_MATCH'], ' "');
        }
        return null;
    }

    /**
     * Return If-Modified-Since header sent by the client in order 
     * to determine whether the client's cached resource is 
     * up-to-date. See RFC2616, Section 13.3; this recommends
     * using both Etags and Last-Modifie.
     * @return int unix time of last update
     */
    static function getIfModifiedSince() {
        if (array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER) && $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
            return strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        }
        return null;
    }

    /**
     * Sends HTTP code to client
     */
    static function setCode($code) {
        if (headers_sent())
            throw new Error('HTTP headers already sent', 400);
        header('HTTP/1.1 ' . intval($code));
    }

    /**
     * Sends HTTP content type to client
     * @param string $type 
     */
    static function setContentType($type) {
        if (headers_sent())
            throw new Error('HTTP headers already sent', 400);
        header('Content-Type: ' . $type);
    }

    /**
     * Redirects client to the given URL
     * @param string $url
     */
    static function redirect($url) {
        if (headers_sent())
            throw new Error('HTTP headers already sent', 400);
        header('Location: ' . $url);
        exit();
    }

    /**
     * Perform an HTTP request, returning the 
     * @example To grab a page: WebHttp::request('www.google.com')
     * @param string $url
     * @param string $method, one of [GET, POST, PUT, DELETE, HEAD, LIST]
     * @param string $content
     * @param string $content_type see http://www.iana.org/assignments/media-types/index.html
     * @param array $headers additional headers, see http://us2.php.net/manual/en/context.http.php
     */
    static function request($url, $method = 'GET', $content = '', $content_type = 'text/html', $headers = array()) {
        self::$responseCode = null; // reset after any previous requests
        $method = strtoupper($method);
        $_headers = array_merge(array('Content-type: ' . $content_type), $headers);
        $options = array('http' =>
            array(
                'method' => $method,
                'header' => $_headers,
                'content' => $content
            )
        );
        $context = stream_context_create($options);
        // do request
        set_error_handler(array('WebHttp', 'handleHTTPRequestFailure'));
        $response = file_get_contents($url, false, $context);
        restore_error_handler();
        // check for errors
        if ($response === false) {
            throw new Error('Could not open url: ' . $url, 404);
        }
        // attempt to find and save the HTTP response code
        if (!isset($http_response_header)) {
            throw new Error('No HTTP request was made', 400);
        }
        $lines = preg_grep('#HTTP/#i', $http_response_header);
        foreach ($lines as $line) {
            if (preg_match('#HTTP/\d.\d (\d\d\d)#i', $line, $matches)) {
                self::$responseCode = intval($matches[1]);
                break;
            }
        }
        // return 
        return $response;
    }
    
    /**
     * Since the file_get_contents() above in request() may fail, we catch
     * the error here, merely setting the response code to 400 Bad Request 
     * and allowing request() to finish.
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @return boolean
     */
    private static function handleHTTPRequestFailure($code, $message, $file, $line){
        self::$responseCode = 400;
        return true;
    }

    /**
     * Returns HTTP code from last HTTP request made using WebHttp::request().
     * @return int 
     */
    static function getRequestCode() {
        return self::$responseCode;
    }

}
