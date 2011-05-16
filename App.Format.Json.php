<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AppFormatJson implements AppFormatInterface{

    /**
     * @var <mixed> holds data to output
     */
    private $out;

    /**
     * Get input data
     * @return <mixed>
     */
    public function getData(){
        $in = file_get_contents('php://input');
        return json_decode($in);
    }

    /**
     * Set output data
     * @param <mixed>
     */
    public function setData($data){
        $this->out = $data;
    }

    /**
     * Send formatted output data
     */
    public function send(){
        $config = Configuration::getInstance();
        if( !$config['debug'] ) header('Content-Type: application/json');
        echo json_encode($this->out);
    }

    /**
     * Send formatted error data
     */
    public function error(){
        $error = $this->out;
        // send HTTP header
        header($_SERVER['SERVER_PROTOCOL'].' '.$error->getCode());
        $config = Configuration::getInstance();
        if( !$config['debug'] ) header('Content-Type: application/json');
        // send JSON error
        $e = array('error'=>$error->getMessage(), 'code'=>$error->getCode());
        echo json_encode($e);
    }
}