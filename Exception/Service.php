<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ExceptionService
 */
class ExceptionService extends Exception{ 
    public function __toString(){
        header( 'HTTP/1.1 ' . intval($this->getCode()) );
        $out = "<h2>Service Exception ({$this->getCode()})</h2>\n";
        $out .= "<p>{$this->getMessage()}</p>\n";
        $out .= "<pre>{$this->getTraceAsString()}</pre>\n";
        return $out;
    }
}