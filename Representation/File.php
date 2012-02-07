<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * File representation of a RESTful resource.
 * @uses Representation
 */
class RepresentationFile implements Representation{
    
    protected $name;
    
    /**
     * Returns the file name
     * @throws ExceptionSettings
     * @return string
     */
    public function getName(){
        if( $name === null ) throw new ExceptionSettings("RepresentationFile name is not yet set", 500);
        return $this->name;
    }
    
    /**
     * Sets the file name
     * @param string $filename
     */
    public function setName($filename){
        $this->name = $filename;   
    }
    
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
        $this->data = $data;
    }
    
    /**
     * @see Representation::receive()
     */
    public function receive(){
        // grab first POST uploaded file
        $upload = reset($_FILES);
        if( !$upload ) throw new ExceptionWeb("No uploaded file could be found", 404);
        if( $upload['error'] ) throw new ExceptionWeb("Upload failed: ".$upload['error'], 400);
        // get name
        $this->setName($upload['name']);
        // get data
        $this->setData(file_get_contents($upload['tmp_name']));
    }
    
    /**
     * @see Representation::send()
     */
    public function send(){
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.$this->getName());
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.strlen($this->getData()));
        echo $this->getData();
    }
}