<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides static methods for handling the URLs in a web service request
 * @uses ExceptionWeb, LanguageInflection
 */
class WebRouting {

    /**
     * Returns the URL anchor. This is hardcoded to be '.php' because
     * the intended practice is to use PHP files as web service endpoints.
     * Optionally, the developer can use .htaccess redirects to remove
     * this from the URL.
     * @return string
     */
    public function getAnchor() {
        return '.php';
    }

    /**
     * Returns the current request URL. This method is identical to
     * WebHttp::getUrl() but included here for convenience and faster
     * loading.
     * @return string
     */
    public static function getUrl() {
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
     * Returns the request URL from its beginning through the 
     * directory holding the current script.
     * @example
     * // for a request like 'http://www.example.com/dir/index.php'
     * echo WebRouting::getDirectoryUrl();
     * // should print 'http://www.example.com/dir'
     * @staticvar string $directory_url
     * @return string 
     */
    public static function getDirectoryUrl(){
        static $directory_url = null;
        if( $directory_url === null ){
            $url = self::getLocationUrl();
            $end = strrpos($url, '/');
            $directory_url = substr($url, 0, $end);
        }
        return $directory_url;
    }

    /**
     * Returns the request URL from its beginning through the
     * anchor ('.php' unless getAnchor() is overriden).
     * @return string
     */
    public static function getLocationUrl() {
        static $location_url = null;
        if ($location_url === null) {
            if (!self::getAnchor())
                throw new ExceptionWeb('No anchor set', 500);
            $url = self::getUrl();
            $anchor = self::getAnchor();
            $start = 0;
            // find url end
            $anchor_start = strpos($url, $anchor);
            if( $anchor_start !== false ){
                $end = $anchor_start + strlen($anchor);
            }
            elseif( strpos($url, '?') !== false ){
                $end = strpos($url, '?');
            }
            else{
                $end = strlen($url);
            }
            // make url
            $location_url = substr($url, $start, $end);
            // test for filename 
            if( strpos($location_url, '.php') === false ){
                $parts = explode('/', $_SERVER['SCRIPT_FILENAME']);
                $filename = end($parts);
                if( $location_url[ strlen($location_url) - 1 ] !== '/' ) $location_url .= '/';
                $location_url .= $filename;
            }
        }
        return $location_url;
    }

    /**
     * Returns the filename (a.k.a. tokens, but not yet in array
     * form) after the anchor and up to the '?'.
     * @return string
     */
    public static function getAnchoredUrl() {
        static $anchored_url = null;
        if ($anchored_url === null) {
            if (!self::getAnchor())
                throw new ExceptionWeb('No anchor set', 500);
            $anchored = strpos(self::getUrl(), self::getAnchor());
            if ($anchored === false)
                throw new ExceptionWeb('Anchor not found in URL', 400);
            $start = $anchored + strlen(self::getAnchor()) + 1;
            $end = @strpos(self::getUrl(), '?', $start);
            if ($end === false)
                $end = strlen(self::getUrl());
            $anchored_url = substr(self::getUrl(), $start, $end - $start);
        }
        return $anchored_url;
    }

    /**
     * Builds a URL with current location, given tokens, and
     * GET variables (if $pass_get_variables is set).
     * @example
     * // For an HTTP request like "http://example.com/index.php?search=..."
     * $_GET['page'] = 1;
     * $_GET['page-limit' = 10;
     * $url = WebRouting::createUrl('object/id/action', true);
     * // $url => "http://example.com/index.php/object/id/action?search=...&page=1&page-limit=10"
     * @param string $tokens
     * @param boolean $pass_get_variables
     */
    public static function createUrl($tokens, $pass_get_variables = true) {
        if (@$tokens[0] == '/')
            $tokens = substr($tokens, 1);
        $url = self::getLocationUrl() . '/' . $tokens . '?' . http_build_query($_GET);
        return $url;
    }

    /**
     * Returns unparsed tokens from a RESTful URL by index.
     * @return array an ordered list of tokens like [0 => ..., 1 => ...]
     */
    public static function getTokens() {
        static $tokens = null;
        if ($tokens === null) {
            $token_string = self::getAnchoredUrl();
            // split and remove empty
            $tokens = explode('/', $token_string);
            foreach ($tokens as $index => $token) {
                if (strlen($token) < 1)
                    unset($tokens[$index]);
            }
            if (!$tokens)
                throw new ExceptionWeb('No URL tokens', 400);
        }
        return $tokens;
    }

    /**
     * Returns the specified token. If given an integer, will return
     * the token with that index from an ordered list. If given one
     * of [object, id, action], will return the token from the parsed
     * list.
     * @param mixed $key
     * @return string
     */
    public static function getToken($key) {
        if (is_int($key))
            $tokens = self::getTokens();
        else
            $tokens = self::parse();
        return $tokens[$key];
    }

    /**
     * Returns the class name from the URL's object token. Uses
     * LanguageInflection to move between URL-style names (e.g.
     * 'blog_posts') and pocket-knife class names (e.g. 'BlogPost').
     * @deprecated currently unused in Service, since we go
     * straight from URL name to class name
     * @staticvar string $classname
     * @return string
     */
    public function getClassname() {
        static $classname = null;
        if ($classname === null) {
            $inflector = new LanguageInflection(self::getToken('object'));
            $classname = $inflector->toSingular()->toCamelCaseStyle()->toString();
        }
        return $classname;
    }

    /**
     * Returns most likely class method given an HTTP request method.
     * @deprecated unused in Service due to Service::getRouting()
     * @return string
     */
    public function getMethod() {
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
        foreach ($_REQUEST as $key => $value) {
            $KEY = strtoupper($key);
            if (in_array($KEY, $http_request_methods))
                $method = $KEY;
        }
        // look at server requesr_method
        if (!$method)
            $method = $_SERVER['REQUEST_METHOD'];
        // map
        return $map[$method];
    }

    /**
     * Returns parsed tokens from a RESTful URL. This method will
     * attempt to intelligently decide which tokens fits where in
     * the "[object]/[id]/[action]" scheme used by pocket-knife
     * web services.
     * @deprecated currently unused in Service due to
     * Service::getRouting().
     * @return array a list of tokens like "[object => ..., id => ..., action => ...]"
     */
    public static function parse() {
        static $tokens = null;
        if ($tokens === null) {
            $tokens = self::getTokens();
            // parse
            reset($tokens);
            $object = current($tokens);
            $id = next($tokens);
            $action = next($tokens);
            if (!$action) {
                // case: entities/enumerate
                if ($id && !is_numeric($id))
                    $action = $id;
                // case: entities/23
                else if ($id) {
                    $method = self::getMethod(); // use HTTP method
                    if ($method)
                        $action = $method;
                    else
                        $action = 'exists';
                }
                // case: entities/
                else
                    $action = 'enumerate';
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
}