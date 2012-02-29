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
        $this->data = $data;
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
        if (is_null( $this->getData())) {
            echo 'NULL';
        } elseif (is_bool( $this->getData())) {
            echo $this->getData() ? 'TRUE' : 'FALSE';
        } elseif (is_object( $this->getData()) || is_array( $this->getData())) {
            echo print_r( $this->getData(), true);
        } else {
            echo $this->getData();
        }
    }
}