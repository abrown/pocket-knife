<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * XML representation of a RESTful resource.
 * @uses Representation, BasicXml
 */
class RepresentationXml extends Representation {

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
        $this->data = to_object($data);
    }

    /**
     * @see Representation::receive()
     */
    public function receive() {
        $in = get_http_body();
        $this->data = BasicXml::xml_decode($in);
    }

    /**
     * @see Representation::send()
     */
    public function send() {
        header('Content-Type: application/json');
        echo BasicXml::xml_encode($this->data);
    }

}