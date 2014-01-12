<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provide routing functionality to Service
 * @uses WebUrl, WebHttp
 */
class Route {

    public $method;
    public $parts = array();
    public $uri;
    public $contentType;

    /**
     * Build a route; if no URL given, will build the route from the current 
     * HTTP request. A URI (e.g. 'resource/id/...') may also be passed.
     * @param string $url
     */
    public function __construct($method = null, $urlOrUri = null, $contentType = null) {
        // setup method
        $this->method = ($method !== null) ? $method : WebHttp::getMethod();
        if(!WebHttp::isValidMethod($this->method)){
            throw new Error("Method '{$this->method}' is an invalid HTTP method; request OPTIONS for valid methods.", 405);
        }
        // setup content type
        $this->contentType = ($contentType !== null) ? $contentType : WebHttp::getContentType();
        if(!Representation::isValidContentType($this->contentType)){
            throw new Error("The content type '{$this->contentType}' is not supported.", 415);
        }
        // setup URI
        $urlOrUri = ($urlOrUri !== null) ? strtolower($urlOrUri) : strtolower(WebUrl::getUrl());
        $this->uri = (strpos($urlOrUri, 'http') === 0) ? $this->getURIFrom($urlOrUri) : $urlOrUri;
        // setup routing parts
        if (strlen($this->uri) > 0) {
            $this->parts = explode('/', $this->uri);
        }
    }
    
    /**
     * Return route as a string like "GET resource/id (application/json)"
     * @return string
     */
    public function __toString(){
        return "{$this->method} {$this->uri} ({$this->contentType})";
    }
    
    /**
     * Return the resource part of this route; this is typically the first
     * part in the URI
     * @example 
     * $route = new Route('http://example.com/api.php/resource/25/version2?...';
     * $route->getResource(); // returns 'resource'
     * @return string
     */
    public function getResource() {
        return isset($this->parts[0]) ? $this->parts[0] : null;
    }
    
    /**
     * Return the identifier part of this route; this is typically the second
     * part in the URI.
     * @example 
     * $route = new Route('http://example.com/api.php/resource/25/version2?...';
     * $route->getID(); // returns '25'
     * @return string
     */
    public function getIdentifier() {
        return isset($this->parts[1]) ? $this->parts[1] : null;
    }
    
    /**
     * 
     * @param Route $template
     */
    public function extractValuesWith(Route $template){
        $values = array();
        $length = count($template->parts) < count($this->parts) ? count($template->parts) : count($this->parts);
        for($i = 0; $i < $length; $i++){
            $k = $template->parts[$i];
            $v = $this->parts[$i];
            if($k[0] == '['){
                $values[substr($k, 1, -1)] = is_numeric($v) ? intval($v) : $v;
            }
        }
        return $values;
    }

    /**
     * Determine the Uniform Resource Identifier from the given URL; in 
     * pocket-knife, this will appear after a URL anchor (default is '.php')
     * and will uniquely identify a Resource.
     * @example 
     * $route->getURIFrom('http://example.com/api.php/resource/25/version2?...'); // will return 'resource/25/version2'
     * @param string $url
     * @return string
     */
    public function getURIFrom($url) {
        $anchor = $this->getAnchor();
        $start = strpos($url, $anchor); // find the location of '.php'
        if ($start === false || $start === strlen($url)) {
            return '';
        }
        $start += strlen($anchor) + 1; // add a character for the trailing slash
        $end = strpos($url, '?', $start);
        if($end === false){
            $end = strlen($url);
        }
        return substr($url, $start, $end - $start);
    }

    /**
     * Return the string after which URIs will be identified.
     * @return string 
     */
    public function getAnchor() {
        return '.php';
    }

}
