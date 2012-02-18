<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Plain text representation of a RESTful resource.
 * @uses Representation
 */
class RepresentationText extends Representation {

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
        if (is_null($data)) {
            $this->data = 'NULL';
        } elseif (is_bool($data)) {
            $this->data = $data ? 'TRUE' : 'FALSE';
        } elseif (is_object($data) || is_array($data)) {
            $this->data = print_r($data, true);
        } else {
            $this->data = $data;
        }
    }

    /**
     * @see Representation::receive()
     */
    public function receive() {
        $this->data = get_http_body();
    }

    /**
     * @see Representation::send()
     */
    public function send() {
        header('Content-Type: text/plain');
        echo $this->data;
    }

}