<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * JSON representation of a RESTful resource.
 * @uses Representation
 */
class RepresentationJson implements RepresentationInterface {

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
        $in = file_get_contents('php://input');
        $this->data = json_decode($in);
    }

    /**
     * @see Representation::send()
     */
    public function send() {
        header('Content-Type: application/json');
        echo json_encode($this->data);
    }

}