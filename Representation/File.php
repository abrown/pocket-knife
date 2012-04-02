<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * File representation of a RESTful resource.
 * @uses Representation, ExceptionSettings, ExceptionWeb
 */
class RepresentationFile extends Representation {

    protected $name;

    /**
     * Returns the file name
     * @throws Error
     * @return string
     */
    public function getName() {
        if ($this->name === null)
            throw new Error("RepresentationFile name is not yet set", 500);
        return $this->name;
    }

    /**
     * Sets the file name
     * @param string $filename
     */
    public function setName($filename) {
        $this->name = $filename;
    }

    /**
     * @see Representation::getData()
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @see Representation::setData()
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * @see Representation::receive()
     */
    public function receive() {
        // get name
        $name = md5(date('r'));
        if( function_exists('apache_ request_ headers')){
            $headers = apache_request_headers();
            foreach($headers as $key => $value){
                if($key == 'Content-Disposition'){
                    $start = strpos($value, 'filename=');
                    if( $start !== false ){
                        $start += strlen('filename=');
                        $name = substr($value, $start);
                    }
                    break;
                }
            }
        }
        $this->setName($name);
        // get data
        $in = base64_decode(get_http_body());
        $this->setData($in);
    }

    /**
     * @see Representation::send()
     */
    public function send() {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $this->getName());
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($this->getData()));
        echo $this->getData();
    }

}