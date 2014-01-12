<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods to identify and interact with clients of the web service.
 * Attempts to bridge the gap between CLI and HTTP requests. 
 * @example
 * @uses WebHttp
 */
class Client {

    public $username;
    public $ip;
    public $browser;

    /**
     * Constructor
     * @param string $username
     * @param string $ip
     * @param string $browser
     */
    public function __construct($username = null, $ip = null, $browser = null) {
        $this->username = ($username !== null) ? $username : 'anonymous';
        $this->ip = ($ip !== null) ? $ip : $this->getIPAddressFromHTTPRequest();
        $this->browser = ($browser !== null) ? $browser : $this->getBrowserFromHTTPRequest();
    }

    /**
     * Determine if the client is requesting from a mobile device; useful for
     * templating 'text/html' differently based on the client.
     * @return boolean
     */
    public function isMobile() {
        // TODO
    }

    /**
     * Determine the HTTP content type for this client; uses the $_GET['accept']
     * property to override, then HTTP headers, then defaults to the request
     * content type; finally, if nothing else, it sends application/json
     * (or text/plain for CLI).
     * @return string
     */
    public function getAcceptableContentType($requestContentType = null) {
        $contentType = WebHttp::getAccept();
        // default to request's content type
        if(!$contentType){
            $contentType = $requestContentType;
        }
        // regardless, ensure a representation is available
        if (!Representation::isValidContentType($contentType)) {
            if ($this->getBrowserFromHTTPRequest() == 'cli') {
                $contentType = 'text/plain'; // command-line request will default to plain-text
            } else {
                $contentType = 'application/json'; // JSON for the true default
            }
        }
        // return
        return $contentType;
    }

    /**
     * If logging requests per user, return the number of requests since the
     * given date.
     * @param DateTime $time
     * @return int
     */
    public function getRequestQuotaSince(DateTime $time) {
        // TODO
    }

    /**
     * Return the IP address for this client 
     * @return string
     */
    protected function getIPAddressFromHTTPRequest() {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'localhost';
    }

    /**
     * Attempt to return the client's browser using browscap.ini; identifies
     * command-line clients with 'CLI'
     * @return string
     */
    protected function getBrowserFromHTTPRequest() {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if (get_cfg_var('browscap')) {
                $browserData = get_browser($_SERVER['HTTP_USER_AGENT']);
                return $browserData->parent;
            }
            return $_SERVER['HTTP_USER_AGENT'];
        }
        return 'cli';
    }

}
