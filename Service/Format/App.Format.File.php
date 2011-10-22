<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AppFormatFile implements AppFormatInterface{

    /**
     * @var <mixed> holds data to output
     */
    private $out;

    /**
     * @var <string> name of file to send
     */
    private $name;

    /**
     * @var <string> MIME-type of file to send
     */
    private $type;

    /**
     * Get input data
     * @return <mixed>
     */
    public function getData(){
        if( !$_FILES ) throw new Exception('No files sent', 400);
        return $_FILES;
    }

    /**
     * Set output data
     * @param <mixed>
     */
    public function setData($data, $name = null, $type = 'text/plain'){
        // data
        $this->out = $data;
        // name
        if( $name === null ) throw new Exception('No file name set for download', 501);
        else $this->name = $name;
        // type, default to text/plain
        $this->type = $type;
        
    }

    /**
     * Send formatted output data
     */
    public function send(){
        header("Content-disposition: attachment; filename={$this->name}");
        header("Content-Type: {$this->type}");
        echo $this->data;
    }

    /**
     * Send formatted error data
     */
    public function error(){
        $error = $this->out;
        // send HTTP header
        header($_SERVER['SERVER_PROTOCOL'].' '.$error->getCode());
        header("Content-disposition: attachment; filename=error.txt");
        header("Content-Type: text/plain");
        echo "Error: ".$error->getMessage()."\n";
        echo "Code: ".$error->getCode();
    }
}