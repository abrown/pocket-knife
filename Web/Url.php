<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides static methods to handle common URL-related tasks. 
 * These include all types of URL parsing, building, and 
 * normalizing.
 * @uses Error
 */
class WebUrl {

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
            $url .= '://';
            // get server name
            if (isset($_SERVER['SERVER_NAME'])) {
                $url .= $_SERVER['SERVER_NAME'];
            }
            // check port
            if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
                $url .= ':' . $_SERVER['SERVER_PORT'];
            }
            // add uri
            if (isset($_SERVER['REQUEST_URI'])) {
                $url .= $_SERVER['REQUEST_URI'];
            }
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
     * Returns the URL anchor. This is hardcoded to be '.php' because
     * the intended practice is to use PHP files as web service endpoints.
     * Optionally, the developer can use .htaccess redirects to remove
     * this from the URL.
     * @return string
     */
    protected function getAnchor() {
        return '.php';
    }

    /**
     * Returns the site URL
     * @example 
     * // for a request like 'http://www.example.com/dir/index.php'
     * echo WebUrl::getSiteUrl();
     * // prints 'http://www.example.com'
     * @staticvar string $site_url
     * @return type 
     */
    public static function getSiteUrl(){
        static $site_url = null;
        if( $site_url === null ){
            $url = self::getUrl();
            $end = strpos($url, '/', 8);
            if ($end !== false)
                $site_url = substr($url, 0, $end + 1);
            else
                $site_url = $url;
        }
        return $site_url;
    }
    
    /**
     * Returns the request URL from its beginning through the 
     * directory holding the current script.
     * @example
     * // for a request like 'http://www.example.com/dir/index.php'
     * echo WebRouting::getDirectoryUrl();
     * // should print 'http://www.example.com/dir/'
     * @staticvar string $directory_url
     * @return string 
     */
    public static function getDirectoryUrl() {
        static $directory_url = null;
        if ($directory_url === null) {
            $url = self::getLocationUrl();
            $end = strrpos($url, '/');
            if ($end !== false)
                $directory_url = substr($url, 0, $end + 1);
            else
                $directory_url = $url;
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
                throw new Error('No anchor set', 500);
            $url = self::getUrl();
            $anchor = self::getAnchor();
            $start = 0;
            // find url end
            $anchor_start = strpos($url, $anchor);
            if ($anchor_start !== false) {
                $end = $anchor_start + strlen($anchor);
            } elseif (strpos($url, '?') !== false) {
                $end = strpos($url, '?');
            } else {
                $end = strlen($url);
            }
            // make url
            $location_url = substr($url, $start, $end);
            // test for filename 
            if (strpos($location_url, '.php') === false) {
                $parts = explode('/', $_SERVER['SCRIPT_FILENAME']);
                $filename = end($parts);
                if ($location_url[strlen($location_url) - 1] !== '/')
                    $location_url .= '/';
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
                throw new Error('No anchor set', 500);
            $anchored = strpos(self::getUrl(), self::getAnchor());
            if ($anchored === false)
                throw new Error('Anchor not found in URL', 400);
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
    public static function create($tokens, $pass_get_variables = true) {
        if (@$tokens[0] == '/')
            $tokens = substr($tokens, 1);
        $url = self::getLocationUrl() . '/' . $tokens;
        if( $pass_get_variables ){
            $url .= '?' . http_build_query($_GET);
        }
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
                throw new Error('No URL tokens', 400);
        }
        return $tokens;
    }

    /**
     * Normalizes a URL according to the rules in RFC 3986
     * @link http://en.wikipedia.org/wiki/URL_normalization
     * @param string $url 
     * @return string
     */
    static function normalize($url) {
        $parsed_url = parse_url($url);
        // check host name
        if (!isset($parsed_url['host'])) {
            $regex = '/[a-z0-9-_]+\.[a-z]{2,4}/i';
            if (preg_match($regex, $url)) {
                $parsed_url['path'] = '';
                $parsed_url['host'] = $url;
            } else {
                return null;
            }
        }
        // check scheme
        if (!isset($parsed_url['scheme'])) {
            $parsed_url['scheme'] = 'http';
        }
        // parse query
        if (isset($parsed_url['query']))
            parse_str($parsed_url['query'], $parsed_query);
        else
            $parsed_query = array();
        // convert to lower case
        if (isset($parsed_url['scheme']))
            $parsed_url['scheme'] = strtolower($parsed_url['scheme']);
        if (isset($parsed_url['host']))
            $parsed_url['host'] = strtolower($parsed_url['host']);
        if (isset($parsed_url['path']))
            $parsed_url['path'] = strtolower($parsed_url['path']);
        // convert non-alphanumeric characters (except -_.~) to hex
        if (isset($parsed_url['user']))
            $parsed_url['user'] = rawurlencode($parsed_url['user']);
        if (isset($parsed_url['pass']))
            $parsed_url['pass'] = rawurlencode($parsed_url['pass']);
        if (isset($parsed_url['fragment']))
            $parsed_url['fragment'] = rawurlencode($parsed_url['fragment']);
        if (isset($parsed_url['query']))
            $parsed_url['query'] = http_build_query($parsed_query, '', '&');
        // remove dot segments (see http://tools.ietf.org/html/rfc3986, Remove Dot Segments)
        if (isset($parsed_url['path'])) {
            $input = explode('/', $parsed_url['path']);
            $output = array();
            while (count($input)) {
                $segment = array_shift($input);
                if ($segment == '')
                    continue;
                if ($segment == '.')
                    continue;
                if ($segment == '..' && count($output)) {
                    array_pop($output);
                    continue;
                }
                array_push($output, $segment);
            }
            if (isset($parsed_url['path'][0]) && $parsed_url['path'][0] == '/')
                $parsed_url['path'] = '/';
            else
                $parsed_url['path'] == '';
            $parsed_url['path'] .= implode('/', $output);
        }
        // add trailing / to directories
        if (!isset($parsed_url['query']) && !isset($parsed_url['fragment'])) {
            if (!isset($parsed_url['path'])) {
                $parsed_url['path'] = '/';
            } else {
                $last_slash = strrpos($parsed_url['path'], '/');
                $has_last_slash = ($last_slash !== false);
                $has_file_name = (strpos($parsed_url['path'], '.', $last_slash) !== false);
                if (!$has_last_slash && !$has_file_name) {
                    $parsed_url['path'] .= '/';
                }
            }
        }
        // re-build (from http://us2.php.net/manual/en/function.parse-url.php#106731)
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) && $parsed_url['port'] != '80' ? ':' . $parsed_url['port'] : ''; // remove default port
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        $url = "$user$pass$host$port$path$query$fragment";
        // remove duplicate slashes
        $url = str_replace('//', '/', $url);
        $url = $scheme . $url;
        // return
        return $url;
    }

}