<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for authenticating users
 */
class SecurityAuthentication extends ResourceGeneric {

    public function login($username, $password) {
        // get user
        $user = $this->getUsers()->find($username);
        if (!$user)
            return false;
        // check password
        $user->checkPassword($password);
        //
    }

    public function fromRepresentation($content_type) {
        switch ($context_type) {
            case 'text/html':
            case 'application/x-www-urlencoded':

                break;
            // Basic | Digest
            default:

                break;
        }
    }

    
    /**
     * Types
     *  session
     *  token
     *  basic
     *  digest
     * 
     * 
     * @param type $content_type
     * @param type $data 
     */
    public function toRepresentation($content_type, $data) {
        switch ($context_type) {
            case 'text/html':
                $form = '<form action="">';
                $form .= '<input type="text" name="u" />';
                $form .= '<input type="password" name="p" />';
                $form .= '</form>';
                break;
            case 'application/json':
                
            break;
            // Basic | Digest
            default:
                if( $this->basic ) header('WWW-Authenticate: Basic realm="pocket-knife authentication"');
                if( $this->digest ) 
                break;
        }
    }

}