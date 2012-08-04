<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * The base exception type for the pocket-knife framework. Because PHP does 
 * not (yet) support multiple inheritance, the class simply defines how to
 * pass itself off as a representation.
 */
class Error extends Exception {

    public $http_code;
    public $http_message;
    public $message;
    public $file;
    public $line;
    public $trace;

    /**
     * Returns a representation of the resource given a content type 
     * and data; should be overloaded in descendant classes to 
     * accommodate content type differences and possible 
     * resource-to-data binding
     * @param string $content_type
     */
    public function send($content_type) {
        if (!array_key_exists($content_type, Representation::$MAP))
            trigger_error('415 Unsupported media type in Error code: ' . $content_type, E_USER_ERROR);
        // set variables
        $this->http_code = $this->getCode();
        $this->http_message = $this->getHttpMessage($this->http_code);
        $this->message = $this->getMessage();
        $this->file = $this->getFile();
        $this->line = $this->getLine();
        $this->trace = explode("\n", $this->getTraceAsString());
        // create representation
        $representation = new Representation($this, $content_type);
        $representation->setCode($this->http_code);
        // special cases
        if ($content_type == 'text/html') {
            $representation->setTemplate(get_base_dir() . DS . 'error-template.php', WebTemplate::PHP_FILE);
        }
        // send
        $representation->send();
    }

    /**
     * Return HTTP message for the given HTTP code
     * @param int $code
     * @return string 
     */
    protected static function getHttpMessage($code) {
        if (!isset(self::$HTTP_ERROR_CODES[$code])) {
            return '[UNKNOWN HTTP MESSAGE]';
        } else {
            return self::$HTTP_ERROR_CODES[$code];
        }
    }

    /**
     * HTTP status codes according to http://en.wikipedia.org/wiki/List_of_HTTP_status_codes.
     * @var array 
     */
    protected static $HTTP_ERROR_CODES = array(
        400 => 'Bad Request', // The request cannot be fulfilled due to bad syntax.[2]
        401 => 'Unauthorized', // Similar to 403 Forbidden, but specifically for use when authentication is possible but has failed or not yet been provided.[2] The response must include a WWW-Authenticate header field containing a challenge applicable to the requested resource. See Basic access authentication and Digest access authentication.
        402 => 'Payment Required', // Reserved for future use.[2] The original intention was that this code might be used as part of some form of digital cash or micropayment scheme, but that has not happened, and this code is not usually used. As an example of its use, however, Apple's MobileMe service generates a 402 error ("httpStatusCode:402" in the Mac OS X Console log) if the MobileMe account is delinquent.[citation needed]
        403 => 'Forbidden', // The request was a legal request, but the server is refusing to respond to it.[2] Unlike a 401 Unauthorized response, authenticating will make no difference.[2]
        404 => 'Not Found', // The requested resource could not be found but may be available again in the future.[2] Subsequent requests by the client are permissible.
        405 => 'Method Not Allowed', // A request was made of a resource using a request method not supported by that resource;[2] for example, using GET on a form which requires data to be presented via POST, or using PUT on a read-only resource.
        406 => 'Not Acceptable', // The requested resource is only capable of generating content not acceptable according to the Accept headers sent in the request.[2]
        407 => 'Proxy Authentication Required', // The client must first authenticate itself with the proxy.[2]
        408 => 'Request Timeout', // The server timed out waiting for the request.[2] According to W3 HTTP specifications: "The client did not produce a request within the time that the server was prepared to wait. The client MAY repeat the request without modifications at any later time."
        409 => 'Conflict', // Indicates that the request could not be processed because of conflict in the request, such as an edit conflict.[2]
        410 => 'Gone', // Indicates that the resource requested is no longer available and will not be available again.[2] This should be used when a resource has been intentionally removed and the resource should be purged. Upon receiving a 410 status code, the client should not request the resource again in the future. Clients such as search engines should remove the resource from their indices. Most use cases do not require clients and search engines to purge the resource, and a "404 Not Found" may be used instead.
        411 => 'Length Required', // The request did not specify the length of its content, which is required by the requested resource.[2]
        412 => 'Precondition Failed', // The server does not meet one of the preconditions that the requester put on the request.[2]
        413 => 'Request Entity Too Large', // The request is larger than the server is willing or able to process.[2]
        414 => 'Request-URI Too Long', // The URI provided was too long for the server to process.[2]
        415 => 'Unsupported Media Type', // The request entity has a media type which the server or resource does not support.[2] For example, the client uploads an image as image/svg+xml, but the server requires that images use a different format.
        416 => 'Requested Range Not Satisfiable', // The client has asked for a portion of the file, but the server cannot supply that portion.[2] For example, if the client asked for a part of the file that lies beyond the end of the file.
        417 => 'Expectation Failed', // The server cannot meet the requirements of the Expect request-header field.[2]
        418 => 'I\'m a teapot (RFC 2324)', // This code was defined in 1998 as one of the traditional IETF April Fools' jokes, in RFC 2324, Hyper Text Coffee Pot Control Protocol, and is not expected to be implemented by actual HTTP servers. However, known implementations do exist.[11] An Nginx HTTP server uses this code to simulate goto-like behaviour in its configuration.[12]
        420 => 'Enhance Your Calm', // Returned by the Twitter Search and Trends API when the client is being rate limited.[13] Likely a reference to this number's association with marijuana. Other services may wish to implement the 429 Too Many Requests response code instead.
        422 => 'Unprocessable Entity (WebDAV) (RFC 4918)', // The request was well-formed but was unable to be followed due to semantic errors.[4]
        423 => 'Locked (WebDAV) (RFC 4918)', // The resource that is being accessed is locked.[4]
        424 => 'Failed Dependency (WebDAV) (RFC 4918)', // The request failed due to failure of a previous request (e.g. a PROPPATCH).[4]
        425 => 'Unordered Collection (RFC 3648)', // Defined in drafts of "WebDAV Advanced Collections Protocol",[14] but not present in "Web Distributed Authoring and Versioning (WebDAV) Ordered Collections Protocol".[15]
        426 => 'Upgrade Required (RFC 2817)', // The client should switch to a different protocol such as TLS/1.0.[16]
        428 => 'Precondition Required', // The origin server requires the request to be conditional. Intended to prevent "the 'lost update' problem, where a client GETs a resource's state, modifies it, and PUTs it back to the server, when meanwhile a third party has modified the state on the server, leading to a conflict."[17] Specified in an Internet-Draft which is approved for publication as RFC.
        429 => 'Too Many Requests', // The user has sent too many requests in a given amount of time. Intended for use with rate limiting schemes. Specified in an Internet-Draft which is approved for publication as RFC.[17]
        431 => 'Request Header Fields Too Large', // The server is unwilling to process the request because either an individual header field, or all the header fields collectively, are too large. Specified in an Internet-Draft which is approved for publication as RFC.[17]
        444 => 'No Response', // An nginx HTTP server extension. The server returns no information to the client and closes the connection (useful as a deterrent for malware).
        449 => 'Retry With', // A Microsoft extension. The request should be retried after performing the appropriate action.[18]
        450 => 'Blocked by Windows Parental Controls', // A Microsoft extension. This error is given when Windows Parental Controls are turned on and are blocking access to the given webpage.[19]
        499 => 'Client Closed Request', // An Nginx HTTP server extension. This code is introduced to log the case when the connection is closed by client while HTTP server is processing its request, making server unable to send the HTTP header back.[20]
        500 => 'Internal Server Error', // A generic error message, given when no more specific message is suitable.[2]
        501 => 'Not Implemented', // The server either does not recognise the request method, or it lacks the ability to fulfill the request.[2]
        502 => 'Bad Gateway', // The server was acting as a gateway or proxy and received an invalid response from the upstream server.[2]
        503 => 'Service Unavailable', // The server is currently unavailable (because it is overloaded or down for maintenance).[2] Generally, this is a temporary state.
        504 => 'Gateway Timeout', // The server was acting as a gateway or proxy and did not receive a timely response from the upstream server.[2]
        505 => 'HTTP Version Not Supported', // The server does not support the HTTP protocol version used in the request.[2]
        506 => 'Variant Also Negotiates (RFC 2295)', // Transparent content negotiation for the request results in a circular reference.[21]
        507 => 'Insufficient Storage (WebDAV) (RFC 4918)', // The server is unable to store the representation needed to complete the request.[4]
        508 => 'Loop Detected (WebDAV) (RFC 5842)', // The server detected an infinite loop while processing the request (sent in lieu of 208).
        509 => 'Bandwidth Limit Exceeded (Apache bw/limited extension)', // This status code, while used by many servers, is not specified in any RFCs.
        510 => 'Not Extended (RFC 2774)', // Further extensions to the request are required for the server to fulfill it.[22]
        511 => 'Network Authentication Required', // The client needs to authenticate to gain network access. Intended for use by intercepting proxies used to control access to the network (e.g. "captive portals" used to require agreement to Terms of Service before granting full Internet access via a Wi-Fi hotspot). Specified in an Internet-Draft which is approved for publication as RFC.[17]
        598 => 'Network read timeout error', // This status code is not specified in any RFCs, but is used by some[which?] HTTP proxies to signal a network read timeout behind the proxy to a client in front of the proxy.
        599 => 'Network connect timeout error', // This status code is not specified in any RFCs, but is used by some[which?] HTTP proxies to signal a network connect timeout behind the proxy to a client in front of the proxy. 
    );

}