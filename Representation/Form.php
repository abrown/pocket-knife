<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * HTML form data representation of a RESTful resource.
 * @uses Representation, WebHttp
 */
class RepresentationForm extends RepresentationHtml {

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

}