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
     * Maps content types to representation types
     * @var array
     */
    public static $MAP = array(
        'application/json' => 'RepresentationJson',
        'application/octet-stream' => 'RepresentationFile',
        //'application/rss+xml' => 'RepresentationRss',
        //'application/soap+xml' => 'RepresentationSoap',
        'application/xml' => 'RepresentationXml',
        'application/x-www-form-urlencoded' => 'RepresentationForm',
        'multipart/form-data' => 'RepresentationUpload',
        'text/html' => 'RepresentationHtml',
        'text/plain' => 'RepresentationText'
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
    protected $content_type = 'application/json';

    /**
     * The HTTP code
     * @var string 
     */
    protected $code = 200;

    /**
     * A template to be used for text/html responses
     * @var WebTemplate 
     */
    protected $template;

    /**
     * Constructor
     * @param any $data 
     */
    public function __construct($data, $content_type) {
        $this->setData($data);
        $this->setContentType($content_type);
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
     * Return HTTP Content-Type
     * @return string 
     */
    public function getContentType() {
        return $this->content_type;
    }

    /**
     * Modify HTTP Content-Type
     * @param string $content_type
     * @throws Error 
     */
    public function setContentType($content_type) {
        if (!array_key_exists($content_type, self::$MAP)) {
            throw new Error("Content-type '{$content_type} does not exist.", 415);
        }
        $this->content_type = $content_type;
    }

    /**
     * Return HTTP code
     * @return int 
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * Set HTTP code
     * @param int $code
     */
    public function setCode($code) {
        $this->code = intval($code);
    }

    /**
     * Set template
     * @param string $input see WebTemplate for description
     * @param int $type one of WebTemplate::[STRING, FILE, PHP_STRING, PHP_FILE]
     */
    public function setTemplate($input, $type = self::FILE) {
        $this->template = new WebTemplate($input, $type);
    }

    /**
     * Return template
     * @return WebTemplate
     * @throws Error 
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * Return string form of this Representation
     * @return string
     */
    public function __toString() {
        switch ($this->getContentType()) {
            case 'application/json':
                return json_encode($this->getData());
                break;
            case 'application/octet-stream':
                return file_get_contents($this->getData());
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
                    $this->getTemplate()->setVariable('representation', $this);
                    $this->getTemplate()->setVariable('resource', $this->getData());
                    $this->getTemplate()->replace('resource', $this->getData());
                    return $this->getTemplate()->toString();
                }
            case 'text/plain':
            default:
                if (is_object($this->getData()) && !method_exists($this->getData(), '__toString')) {
                     throw new Error(get_class($this->getData()) . ' has no __toString() method.', 501);
                }
                return $this->getData();
        }
    }

    /**
     * Receive HTTP request from client
     * @throws Error 
     */
    public function receive() {
        if (!$this->getContentType())
            throw new Error('No content type set for representation.', 500);
        switch ($this->getContentType()) {
            case 'application/json':
                $this->setData(json_decode(get_http_body()));
                break;
            case 'multipart/form-data':
                // grab POST variables
                $data = to_object($_POST);
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
                    if ($data->files->$name->size != $file['size']) {
                        throw new Error("File corrupted: " . $file['name'], 400);
                    }
                }
                $this->setData($data);
                break;
            case 'application/octet-stream':
                // create new property 'filename' on the fly
                $this->filename = md5(date('r'));
                if (function_exists('apache_ request_ headers')) {
                    $headers = apache_request_headers();
                    foreach ($headers as $key => $value) {
                        if ($key == 'Content-Disposition') {
                            $start = strpos($value, 'filename=');
                            if ($start !== false) {
                                $start += strlen('filename=');
                                $this->filename = substr($value, $start);
                            }
                            break;
                        }
                    }
                } else {
                    // @todo avoid this
                    throw new Error('File uploads work best with Apache.', 501);
                }
                $this->setData(base64_decode(get_http_body()));
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
    public function send() {
        WebHttp::setCode($this->getCode());
        WebHttp::setContentType($this->getContentType());
        // extra headers
        if ($this->getContentType() == 'application/octet-stream') {
            $filename = pathinfo($this->getData(), PATHINFO_FILENAME);
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($this->getData()));
        }
        // body
        echo $this->__toString();
    }

}