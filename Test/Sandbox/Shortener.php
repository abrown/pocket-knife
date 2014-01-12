<?php

/**
 * This class demonstrates a sample web service for 
 * shortening URLs; no claims are made about the efficacy of
 * the shortening algorithm. By using a generic resource, we
 * do not have to worry about storing data; anything that needs
 * storing will be stored in the class instance as a property.
 */
class Example extends ResourceGeneric {

    public function shorten($url) {
        return substr(md5($url), 0, 10); // real classy...
    }

    public function fromRepresentation($content_type) {
        $url = parent::fromRepresentation($content_type);
        return WebUrl::normalize($url);
    }

    public function toRepresentation($content_type, $data) {
        $representation = parent::toRepresentation($content_type, $data);
        if ($content_type == 'text/html') {
            // special HTML templating
            $representation->setTemplate(new WebTemplate("The URL is: <template:url/><br/><?php echo date('r');?>"), WebTemplate::PHP_STRING);
            $representation->getTemplate()->replace('url', $data);
        } elseif ($content_type == 'application/octet-stream') {
            // can download a file by this name
            $representation->setName("url.txt");
        } elseif ($content_type == 'application/json') {
            // special json format
            $o = new stdClass;
            $o->url = $representation->getData();
            $o->time = date('r');
            $representation->setData($o);
        }
        return $representation;
    }

}