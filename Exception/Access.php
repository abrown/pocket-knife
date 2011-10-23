<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * ExceptionAccess
 */
class ExceptionAccess extends Exception{ 
    public function __toString(){
        header( 'HTTP/1.1 ' . intval($this->getCode()) );
        $out = "<h2>Access Exception ({$this->getCode()})</h2>\n";
        $out .= "<p>{$this->getMessage()}</p>\n";
        $out .= "<pre>{$this->getTraceAsString()}</pre>\n";
        return $out;
    }
}