<?php
/**
 * @copyright Copyright 2009 Gearbox Studios. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class KSoap extends KAbstractService{

    /**
     * Holds SOAPServer instance
     * @var <SOAPServer>
     */
    private $server;

    /**
     * Holds WSDL instance
     * @var <Wsdl>
     */
    private $wsdl;

    /**
     * Handle SOAP Request
     */
    public function handle(){
        try{
            // use UTF8 (to avoid string encoding errors)
            $database = KDatabase::getInstance();
            $database->exec('SET CHARACTER SET utf8');
            // consume
            $classname = $this->getClassName();
            $this->getServer()->setObject( $this->getAction($classname) );
            $this->getServer()->handle();
        }
        catch(Exception $e){
            // save error
            // error_log( print_r($e, true) );
            // send HTTP header
            header($_SERVER['SERVER_PROTOCOL'].' '.$e->getCode());
            // send SOAP error
            $this->getServer()->fault($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Get SoapServer instance
     * @return <SoapServer>
     */
    public function getServer(){
        if( !$this->server ){
            $options = array('cache_wsdl' => WSDL_CACHE_NONE );
            $url = $this->getWsdl()->getUrl();
            $this->server = new SoapServer($url, $options);
        }
        return $this->server;
    }

    /**
     * Get Wsdl instance
     * @return <Wsdl>
     */
    public function getWsdl(){
        if( !$this->wsdl ){
            $this->wsdl = new Wsdl;
        }
        return $this->wsdl;
    }

    /**
     * Get class name for this request
     * @return <string>
     */
    function getClassName(){
        $tokens = $this->getTokens();
        $entity = current($tokens);
        // get from soap action if no token
        if( !$entity && isset($_SERVER['HTTP_SOAPACTION']) ){
            $entity = trim($_SERVER['HTTP_SOAPACTION'], " \r\n\t\"'");
        }
        // check
        if( empty($entity) ) throw new Exception('No SOAP entity in URL or SOAPACTION', 400);
        if( !$this->isAllowed($entity) ) throw new Exception('No entity with this name', 404);
        // inflect
        $_entity = new KInflection($entity);
        return $_entity->toSingular()->toCamelCaseStyle()->toString();
    }
}