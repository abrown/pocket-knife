<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * HTML form data representation of a RESTful resource.
 * @uses Representation, WebHttp
 */
class RepresentationForm extends Representation {

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
        if (WebHttp::getMethod() == 'GET') {
            $this->setData($_GET);
        } elseif (WebHttp::getMethod() == 'POST') {
            $this->setData($_POST);
        } else {
            $in = get_http_body();
            parse_str($in, $this->data);
            $this->setData($this->data);
        }
    }

    /**
     * @see Representation::send()
     */
    public function send() {
        header('Content-Type: application/x-www-form-urlencoded');
        echo http_build_query($this->data);
    }

}