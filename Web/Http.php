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
    private static $response_code;

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
        // else:
        return $_SERVER['REQUEST_METHOD'];
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
     * to find a content-type; defaults to 'text/html'
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
            return strtok($_SERVER['CONTENT_TYPE'], ';');
        }
        // else
        return 'text/html';
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
     * Performs HTTP request
     * @example To grab a page: WebHttp::request('www.google.com')
     * @param string $url
     * @param string $method, one of [GET, POST, PUT, DELETE, HEAD, LIST]
     * @param string $content
     * @param string $content_type see http://www.iana.org/assignments/media-types/index.html
     * @param array $headers additional headers, see http://us2.php.net/manual/en/context.http.php
     */
    static function request($url, $method = 'GET', $content = '', $content_type = 'text/html', $headers = array()) {
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
        $response = file_get_contents($url, false, $context);
        // check errors
        if ($response === false) {
            throw new Error('Could not open url: ' . $url, 404);
        }
        // save response code
        if (!isset($http_response_header)) {
            throw new Error('No HTTP request was made', 400);
        }
        $lines = preg_grep('#HTTP/#i', $http_response_header);
        self::$response_code = 0;
        foreach ($lines as $line) {
            if (preg_match('#HTTP/\d.\d (\d\d\d)#i', $line, $matches)) {
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
    static function getRequestCode() {
        return self::$response_code;
    }

}