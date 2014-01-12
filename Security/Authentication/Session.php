<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for authenticating users with PHP sessions
 * @uses SecurityAuthentication, Representation
 */
class SecurityAuthenticationSession extends SecurityAuthentication {

    /**
     * Sets the session lifetime, in seconds
     * @var int 
     */
    public $session_lifetime = 3600;
    
    /**
     * Constructor, sets additional session cookie parameters
     * @param type $settings 
     */
    public function __construct($settings){
        parent::__construct($settings);
        // set session cookie parameters; TODO: add secure, HttpOnly
        session_set_cookie_params($this->session_lifetime);
        session_regenerate_id(true); 
    }
    
    /**
     * Returns whether the user is logged in.
     * @return boolean 
     */
    public function isLoggedIn() {
        // check session
        if (WebSession::get('username') && WebSession::get('password')) {
            $credentials = new stdClass();
            $credentials->username = WebSession::get('username');
            $credentials->password = WebSession::get('password');
            if ($this->isValidCredential($credentials)) {
                return true;
            }
        }
        // default
        return false;
    }

    /**
     * Returns the name of the current user
     * @return type 
     */
    public function getCurrentUser() {
        return WebSession::get('username');
    }

    /**
     * Returns response from a PHP session authentication request
     * @param string $content_type
     * @return stdClass 
     */
    public function receive($content_type = null) {
        $out = new stdClass();
        // check if request happened
        if (WebHttp::getMethod() != 'POST') {
            return $out;
        }
        // check request
        switch ($content_type) {
            case 'application/octet-stream':
            case 'text/plain':
            case 'application/json':
                $in = get_http_body();
                $out = json_decode($in);
                if (!property_exists($out, 'username'))
                    throw new Error('No username sent.', 404);
                if (!property_exists($out, 'password'))
                    throw new Error('No password sent.', 404);
                if (!property_exists($out, 'one_time_key'))
                    throw new Error('No one time key sent.', 404);
                if ($out->one_time_key !== WebSession::get('one_time_key'))
                    throw new Error('One time key does not match.', 404);
                break;
            case 'application/xml':
                $in = get_http_body();
                $out = BasicXml::xml_decode($in);
                if (!property_exists($out, 'username'))
                    throw new Error('No username sent.', 404);
                if (!property_exists($out, 'password'))
                    throw new Error('No password sent.', 404);
                if (!property_exists($out, 'one_time_key'))
                    throw new Error('No one time key sent.', 404);
                if ($out->one_time_key !== WebSession::get('one_time_key'))
                    throw new Error('One time key does not match.', 404);
                break;
            case 'application/x-www-form-urlencoded':
            case 'multipart/form-data':
            case 'text/html':
                if (!array_key_exists('username', $_POST))
                    throw new Error('No username sent.', 404);
                if (!array_key_exists('password', $_POST))
                    throw new Error('No password sent.', 404);
                if (!array_key_exists('one_time_key', $_POST))
                    throw new Error('No one time key sent.', 404);
                if ($_POST['one_time_key'] !== WebSession::get('one_time_key'))
                    throw new Error('One time key does not match.', 404);
                $out->username = $_POST['username'];
                $out->password = $_POST['password'];
                break;
            default:
                throw new Error('Unknown content type', 400);
                break;
        }
        // save username
        WebSession::put('username', $out->username);
        WebSession::put('password', $out->password);
        // return
        return $out;
    }

    /**
     * Challenges the user with a PHP session authentication challenge
     * @param string $content_type 
     */
    public function send($content_type = null) {
        // set one time key
        WebSession::put('one_time_key', uniqid());
        // create challenge by content types
        switch ($content_type) {
            case 'application/octet-stream':
            case 'text/plain':
                $data = 'Submit authentication request in the JSON object: ';
                $data .= '{"username":"XXX","password":"XXX","one_time_key":"';
                $data .= WebSession::get('one_time_key');
                $data .= '"}';
            case 'application/json':
            case 'application/xml':
                $data = new stdClass();
                $data->username = "[enter username here]";
                $data->password = "[enter password here]";
                $data->one_time_key = WebSession::get('one_time_key');
            case 'application/x-www-form-urlencoded':
            case 'multipart/form-data':
            case 'text/html':
                $action = WebUrl::getUrl();
                $data = '<form action="' . $action . '" method="POST">';
                $data .= 'Username: <input type="text" name="username" /> ';
                $data .= 'Password: <input type="password" name="password" /> ';
                $data .= '<input type="hidden" name="one_time_key" value="' . WebSession::get('one_time_key') . '" />';
                $data .= '<input type="submit" value="Login" />';
                $data .= '</form>';
                break;
            default:
                throw new Error('Unknown content type', 400);
                break;
        }
        // return representation
        $representation = new Representation($data, $content_type);
        $representation->send(401);
    }

}