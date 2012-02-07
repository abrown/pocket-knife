<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * HTML representation of a RESTful resource.
 * @uses Representation
 */
class RepresentationHtml implements Representation{

    /**
     * WebTemplate
     * @var WebTemplate
     */
    protected $template;

    /**
     * Returns the WebTemplate used for formatting this representation
     * @return WebTemplate
     */
    public function getTemplate(){
        if( !$this->template ) throw new ExceptionSettings("RepresentationHtml requires a WebTemplate to complete the request", 500);
        return $this->template;
    }

    /**
     * Returns the WebTemplate used for formatting this representation
     */
    public function setTemplate(WebTemplate $template){
        if( !is_a($template, 'WebTemplate') ) throw new ExceptionSettings("RepresentationHtml requires a WebTemplate", 500);
        $this->template = $template;
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
        if( is_string($data) ) $this->data = $data;
        else $this->data = to_object($data);
    }

    /**
     * @see Representation::receive()
     */
    public function receive(){
        $this->data = file_get_contents('php://input');
    }

    /**
     * @see Representation::send()
     */
    public function send(){
        header('Content-Type: text/html');
        if( is_string($data) ){
            echo $this->data;
        }
        else{
            $tokens = $this->getTemplate()->findTokens();
            $object = MathSet::flatten($this->data);
            foreach($tokens as $token){
                $this->getTemplate()->replace($token, $object[$token]);
            }
            echo $this->getTemplate()->toString();
        }
    }
}