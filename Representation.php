<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Representation of a RESTful resource. It must be able to be
 * sent or received.
 * @uses Error, BasicXml, WebHttp
 * @author andrew
 */
class Representation {

    /**
     * Describes HTTP content types the framework can currently represent; 
     * may add application/rss+xml and application/soap+xml in the future.
     * @var array
     */
    public static $TYPES = array(
        '*/*', // defaults to application/json
        'application/json',
        'application/octet-stream',
        'application/xml',
        'application/x-www-form-urlencoded',
        'multipart/form-data',
        'text/html',
        'text/plain'
    );

    /**
     * Stores the object 
     * @var stdClass
     */
    protected $data;

    /**
     * The HTTP content-type
     * @var string
     */
    protected $contentType;

    /**
     * A template to be used for text/html responses
     * @var WebTemplate 
     */
    protected $template;

    /**
     * Constructor
     * @param any $data 
     * @param string $contentType
     */
    public function __construct($data = null, $contentType = 'application/json') {
        $this->setData($data);
        $this->setContentType($contentType);
    }

    /**
     * Return string form of this Representation
     * @return string
     */
    public function __toString() {
        switch ($this->getContentType()) {
            case '*/*':
            case 'application/json':
                return json_encode($this->getData());
                break;
            case 'application/octet-stream':
                if (isset($this->getData()->contents)) {
                    return base64_encode($this->getData()->contents);
                } elseif (isset($this->getData()->filename)) {
                    return base64_encode(file_get_contents($this->getData()->filename));
                } else {
                    return $this->getData();
                }
                break;
            case 'application/xml':
                return BasicXml::xml_encode($this->getData());
                break;
            case 'application/x-www-form-urlencoded':
                return http_build_query($this->getData());
                break;
            case 'multipart/form-data':
                return 'Cannot return data as multipart/form-data';
                break;
            case 'text/html':
                if ($this->getTemplate()) {
                    $resource = $this->getData();
                    $this->getTemplate()->setVariable('representation', $this);
                    $this->getTemplate()->setVariable('resource', $resource);
                    $this->getTemplate()->replace('resource', $resource);
                    return $this->getTemplate()->toString();
                }
            // if no template defined, expect a __toString() in Resource
            case 'text/plain':
            default:
                if (is_object($this->getData()) && !method_exists($this->getData(), '__toString')) {
                    throw new Error('To accurately return as plain text, ' . get_class($this->getData()) . ' should declare a __toString() method.', 501);
                }
                return (string) $this->getData();
        }
    }

    /**
     * Receive HTTP request from client
     * @throws Error 
     */
    public function receive() {
        if (!$this->getContentType()) {
            throw new Error('No content type set for this representation.', 500);
        }
        switch ($this->getContentType()) {
            case '*/*':
            case 'application/json':
                $this->setData(json_decode(get_http_body()));
                break;
            case 'multipart/form-data':
                // grab POST variables
                $data = ($_POST) ? to_object($_POST) : new stdClass();
                // grab all POST uploaded files
                $data->files = new stdClass();
                foreach ($_FILES as $name => $file) {
                    $data->files->$name = new stdClass();
                    // check for errors
                    if ($file['error']) {
                        throw new Error("Upload failed: " . $file['error'], 400);
                    }
                    // get data
                    $data->files->$name->name = $file['name'];
                    $data->files->$name->type = $file['type'];
                    $data->files->$name->contents = file_get_contents($file['tmp_name']);
                    $data->files->$name->size = strlen($data->files->$name->contents);
                    // check size
                    if (isset($file['size']) && $data->files->$name->size != $file['size']) {
                        throw new Error("File corrupted: " . $file['name'], 400);
                    }
                }
                $this->setData($data);
                break;
            case 'application/octet-stream':
                // create new property 'filename' on the fly
                $data = new stdClass();
                $data->filename = md5(date('r'));
                $data->contents = base64_decode(get_http_body());
                // attempt to set filename
                if (function_exists('apache_request_headers')) {
                    $headers = apache_request_headers();
                    foreach ($headers as $key => $value) {
                        if ($key == 'Content-Disposition') {
                            $start = strpos($value, 'filename=');
                            if ($start !== false) {
                                $start += strlen('filename=');
                                $data->filename = trim(substr($value, $start), '"' . " \t\n\r\0\x0B");
                            }
                            break;
                        }
                    }
                }
                $this->setData($data);
                break;
            case 'application/xml':
                $this->setData(BasicXml::xml_decode(get_http_body()));
                break;
            case 'application/x-www-form-urlencoded':
                if (WebHttp::getMethod() == 'GET') {
                    $this->setData(to_object($_GET));
                } elseif (WebHttp::getMethod() == 'POST') {
                    $this->setData(to_object($_POST));
                } else {
                    $in = get_http_body();
                    $data = array();
                    parse_str($in, $data);
                    $this->setData(to_object($data));
                }
                break;
            case 'text/html':
            case 'text/plain':
            default:
                $this->setData(get_http_body());
                break;
        }
    }

    /**
     * Send HTTP response to client 
     */
    public function send($code, $contentType = null) {
        // set content type 
        if ($contentType !== null) {
            $this->setContentType($contentType);
        }
        // send headers
        if (!headers_sent()) {
            WebHttp::setCode($code);
            WebHttp::setContentType($this->getContentType());
            // extra headers
            if ($this->getContentType() == 'application/octet-stream') {
                $filename = pathinfo($this->getData(), PATHINFO_FILENAME);
                header('Content-Disposition: attachment; filename=' . $filename);
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($this->getData()));
            }
        }
        // send response body
        echo $this->__toString();
    }

    /**
     * Accesses data object
     * @return any
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Modifies data object
     * @param any $data
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Return the HTTP Content-Type of this representation
     * @return string
     */
    public function getContentType() {
        return $this->contentType;
    }

    /**
     * Modify HTTP Content-Type
     * @param string $contentType
     * @throws Error 
     */
    public function setContentType($contentType) {
        if (!self::isValidContentType($contentType)) {
            throw new Error("The content type '{$contentType}' is not supported.", 415);
        }
        $this->contentType = $contentType;
    }

    /**
     * Determine if the HTTP Content-Type can be processed
     * @param string $contentType
     * @return boolean
     */
    static public function isValidContentType($contentType) {
        return in_array($contentType, self::$TYPES, true);
    }

    /**
     * Return template
     * @return WebTemplate
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * Set template
     * @param string $input see Web/Template.php for description
     * @param int $type one of WebTemplate::[STRING, FILE, PHP_STRING, PHP_FILE]
     */
    public function setTemplate($input, $type = self::FILE) {
        $this->template = new WebTemplate($input, $type);
    }

}
