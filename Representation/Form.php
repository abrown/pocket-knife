<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Plain text representation of a RESTful resource.
 * @uses Representation
 */
class RepresentationJson implements Representation{

    /**
     * @see Representation::getData()
     */
    public function getData(){
        return $this->data;
    }

    /**
     * @see Representation::setData()
     */
    public function setData($data){
        $this->data = to_object($data);
    }

    /**
     * @see Representation::receive()
     */
    public function receive(){
        $in = file_get_contents('php://input');
        $this->data = array();
        parse_url($in, $this->data);
        $this->data = to_object($this->data);       
    }

    /**
     * @see Representation::send()
     */
    public function send(){
        header('Content-Type: application/x-www-form-urlencoded');
        echo http_build_query($this->data);
    }
}