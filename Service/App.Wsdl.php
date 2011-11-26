<?php

class AppWsdl{
    
    // WSDL elements
    private $doc;
    private $types;
    private $interface;
    private $binding;
    private $service;
    private $endpoint;
    
    // Documentation
    private $name;
    private $class;
    
    public function __construct(){
        $this->doc = new DOMDocument();
        $this->doc->formatOutput = true;
        // root
        $d = $this->doc->createElementNS('http://www.w3.org/ns/wsdl', 'wsdl:description');
        $this->doc->appendChild($d);
        $this->doc->createAttributeNS('http://www.w3.org/ns/wsdl/http', 'http:attr');
        // types
        $t = $this->doc->createElement('wsdl:types');
        $d->appendChild($t);
        $this->types = $t;
        // interface
        $i = $this->doc->createElement('wsdl:interface');
        $d->appendChild($i);
        $this->interface = $i;
        // binding
        $b = $this->doc->createElement('wsdl:binding');
        $d->appendChild($b);
        $this->binding = $b;
        $this->binding->setAttribute( 'type', 'http://www.w3.org/ns/wsdl/http');
        // service
        $s = $this->doc->createElement('wsdl:service');
        $d->appendChild($s);
        $this->service = $s;
        // endpoint
        $e = $this->doc->createElement('wsdl:endpoint');
        $s->appendChild($e);
        $this->endpoint = $e; 
    }
    
    public function setAppName($name){
        $this->name = $name;
        $this->service->setAttribute('name', $name);
        $this->binding->setAttribute('name', $name.'HTTPBinding');
        $this->endpoint->setAttribute('name', $name.'HTTPEndpoint');
        $this->endpoint->setAttribute('binding', $this->binding->getAttribute('name'));
    }
    
    public function setNamespace($uri){
        $this->doc->documentElement->setAttribute('targetNamespace', $uri);
        $this->doc->createAttributeNS($uri, 'tns:attr');
    }
    
    public function setEndpoint($url){
        $this->endpoint->setAttribute('address', $url);
    }
    
    public function setClass($class){
        if( !class_exists($class) ) __autoload( $class );
        $c = new ReflectionClass($class);
        $this->class = $c;
        // add methods
        foreach($c->getMethods() as $m){
            // filter
            if( !$m->isPublic() || strpos($m->getName(), '__') === 0 ) continue;
            // in/out types
            $input = 'String';
            $output = 'String';
            if( preg_match("/@return <(\w+)>/i", $m->getDocComment(), $match) ){
                $output = $match[1];
            }
            if( stripos($output, 'object') !== false ){
                $output = $c->getName();
            }
            // create interface node
            $o = $this->doc->createElement('wsdl:operation');
            $o->setAttribute('name', $m->getName());
            $o->setAttribute('pattern', 'http://www.w3.org/ns/wsdl/in-out');
            $this->interface->appendChild($o);
            // create types: input
            $in = $this->doc->createElement('wsdl:input');
            $in->setAttribute('element', 'type:'.$input);
            $o->appendChild($in);
            
            // create types: output
            $out = $this->doc->createElement('wsdl:output');
            $out->setAttribute('element', 'type:'.$output);
            $o->appendChild($out);
            
            // create binding node
            $_o = $this->doc->createElement('wsdl:operation');
            $_o->setAttribute('ref', 'tns:'.$o->getAttribute('name'));
            $_o->setAttribute('http:method', 'GET');
            $this->binding->appendChild($_o);
        }
    }
    
    public function toString(){
        return $this->doc->saveXML();
    }
}