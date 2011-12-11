<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class Site{
    private $path;
    private $url;
    private $output_format;
    
    function setPath($path){
        $this->path = $path;
    }
    
    function getPath(){
        return $this->path;
    }
    
    /**
     * Sets URL
     * @param type $url 
     */
    function setUrl($url){
        $url = strtolower($url);
        $url = preg_replace('/[^a-z]*$/', '', $url); // remove non-characters at end
        if ( substr($url, 0, 4) != 'http' ) $url = 'http://'.$url;
        $this->url = $url;
    }
    
    /**
     * Returns URL
     * @return string
     */
    function getUrl(){
        return $this->url;
    }
    
    function setSettings(){
        
    }
    
    /**
     * Set output Content-Type
     */
    function setOutputContentType($content_type){
        $this->content_type = $content_type;
    }
    
    function getOutputContentType(){
        return $this->content_type;
    }
    
    function getSiteMap(){
        
    }
    
    /**
     * Executes the page server
     */
    function execute(){
        $file = $this->path . DS . BasicRouting::getAnchoredFilename();
        if( is_file($file) ){
            header( 'Content-Type: '.$this->getOutputContentType() );
            echo file_get_contents($file);
        }
        else{
            BasicHttp::setCode(404);
        }
    } 
}