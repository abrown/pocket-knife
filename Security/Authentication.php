<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for authenticating users
 */
class SecurityAuthentication extends ResourceGeneric{
    
    public function login($username, $password){
        
    }
    
    public function fromRepresentation($content_type){
        switch($context_type){
            case 'text/html':
            case 'application/x-www-urlencoded':
                
            break;
            // Basic | Digest
            default:
                
            break;  
        }
    }
    
    public function toRepresentation($content_type, $data){
        
    }
}