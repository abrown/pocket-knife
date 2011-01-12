<?php
/**
 * @copyright Copyright 2009 Gearbox Studios. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Set WSDL Path
 */
define('KNIFE_WSDL_PATH', dirname(__FILE__).DS.'wsdl');

class KWsdl{

    /**
     * Cache Key
     * @var <string> 
     */
    public $key = 'service.wsdl';

    /**
     * Cache interval (in seconds)
     * @var <int>
     */
    public $interval = 3600;

    /**
     * Constructor
     */
    public function __construct(){
        if( !$this->isValid() ){
            $this->install();
        }
    }

    /**
     * Get URL Location of WSDL
     * @return <string>
     */
    public function getUrl(){
        return SERVICE_URL.DS.'service'.DS.'wsdl'.DS.$this->key;
    }
    
    /**
     * Check if WSDL cache is valid
     * @return <bool>
     */
    function isValid(){
        $file = KNIFE_WSDL_PATH.DS.$this->key;
        // check file
        if( !is_file($file) ) return false;
        // check interval
        $valid = (filectime($file) + $this->interval) > time() ? true : false;
        return $valid;
    }

    /**
     * Install WSDL file from .install file
     * Replaces tokens in install file with Documentor
     */
    function install(){
        $install = KNIFE_WSDL_PATH.DS.'install.wsdl';
        if( !file_exists($install) ) trigger_error('Could not find '.$install, E_USER_ERROR);
        // get installation and replace tokens
        $contents = file_get_contents($install);
        $contents = $this->replaceTokens($contents);
        // save
        $file = WSDL_PATH.DS.$this->key;
        file_put_contents($file, $contents);
    }

    /**
     * Output
     * @return <type>
     */
    function display(){
        header('Content-Type: application/xml');
        $file = KNIFE_WSDL_PATH.DS.$this->key;
        echo file_get_contents($file);
    }

    /**
     * Replace TOKENs in WSDL
     */
    function replaceTokens($string){
        if( preg_match_all('#{TOKEN/(\w*)/(\w*)}#', $string, $matches, PREG_SET_ORDER) ){
            foreach( $matches as $match ){
                $type = $match[1];
                $token = $match[2];
                $replacement = '';
                switch( $type ){
                    case 'Class':
                        $replacement = $this->getXsdElements($token); // get public variables from class
                    break;
                    case 'Constant':
                        if( defined($token) ) $replacement = constant($token);
                    break;
                    case 'Function':
                        if( method_exists($this, $token) ) $replacement = $this->$token();
                    break;
                }
                // replace
                $string = preg_replace("#{TOKEN/$type/$token}#", $replacement, $string);
            }
        }
        return $string;
    }

    /**
     * Get XSD elements from public class properties
     * @param <string> $class
     * @return <string>
     */
    function getXsdElements($class){
        $doc = new Documentation;
        $xsd = array();
        foreach( $doc->getClass($class)->getProperties() as $property ){
            // skip non-public properties
            if( !$property->isPublic() ) continue;
            // defaults
            $type = 'anyType';
            $format = '<element name="%s" type="%s" />';
            $map = array(
                'array' => 'anyType',
                'mixed' => 'anyType',
                'int' => 'integer'
            );
            // get comment array
            $comment = $doc->getComment( $property->getDocComment() );
            // find type by @var token
            foreach($comment as $line){
                if( strpos($line, '@var') === 0 ){
                    list( $var, $_type ) = explode(' ', $line, 2);
                    break;
                }
            }
            // map php type to xsd type
            if( $_type ){
                $_type = preg_replace('/<|>/', '', $_type);
                if( isset($map[$_type]) ) $type = $map[$_type];
                else $type = $_type;
            }
            // save 
            $xsd[] = sprintf( $format, $property->getName(), $type );
        }
        return implode('', $xsd);
    }
}
