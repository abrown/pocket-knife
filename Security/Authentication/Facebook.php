<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for authenticating Facebook users with OAuth2.0
 */
class SecurityAuthenticationFacebook extends SecurityAuthentication {

    public $app_id = "YOUR_APP_ID";
    public $app_secret = "YOUR_APP_SECRET";
    public $storage;

    /**
     * Message appears in login window
     * @var string 
     */
    public $message = 'facebook authentication';

    /**
     * Constructor, sets additional session cookie parameters
     * @param type $settings 
     */
    public function __construct($settings) {
        // validate
        BasicValidation::with($settings)
                // facebook app ID
                ->withProperty('app_id')->isString()
                ->upOne()
                // facebook app secret
                ->withProperty('app_secret')->isString();
        parent::__construct($settings);
    }

    /**
     * Returns whether the user is logged in; for HTTP authentication, the
     * logs in with every HTTP request
     * @return boolean 
     */
    public function isLoggedIn() {
        if (WebSession::get('facebook_access_token')) {
            $user = new SecurityUser($this->getCurrentUser(), WebSession::get('facebook_access_token'), 'guest');
            $this->getStorage()->begin();
            $this->getStorage()->create($user);
            $this->getStorage()->commit();
            return true;
        }
        return false;
    }

    /**
     * Returns the name of the current user
     * @return type 
     */
    public function getCurrentUser() {
        $url = "https://graph.facebook.com/me?access_token=" . WebSession::get('facebook_access_token');
        $data = json_decode(WebHttp::request($url));
        return @$data->name;
    }

    /**
     * Returns response from an HTTP Basic Authentication request
     * @param string $content_type
     * @return stdClass 
     */
    public function receive($content_type = null) {
        try {
            $user = $this->getCurrentUser();
        } catch (Error $e) {
            $user = 'guest';
        }
        $out = new stdClass();
        $out->username = $user;
        $out->password = WebSession::get('facebook_access_token');
        // return
        return $out;
    }

    /**
     * Challenges the user with a HTTP Basic Authentication challenge
     * @param string $content_type 
     */
    public function send($content_type = null, $data = null) {
        // redirect to facebook login
        if (!@$_REQUEST['code']) {
            WebSession::put('facebook_csrf_protection_key', md5(uniqid(rand(), true)));
            $url = "http://www.facebook.com/dialog/oauth?client_id="
                    . $this->app_id . "&redirect_uri=" . WebUrl::create(WebUrl::getAnchoredUrl(), false) . "&state="
                    . WebSession::get('facebook_csrf_protection_key');
            WebHttp::redirect($url);
            return;
        }
        // test csrf attack
        if (!@$_REQUEST['state'] || @$_REQUEST['state'] != WebSession::get('facebook_csrf_protection_key')) {
            throw new Error('Login attempt has failed.', 407);
            return;
        }
        // get access token
        $url = "https://graph.facebook.com/oauth/access_token?"
                . "client_id=" . $this->app_id . "&redirect_uri=" . WebUrl::create(WebUrl::getAnchoredUrl(), false)
                . "&client_secret=" . $this->app_secret . "&code=" . $_REQUEST['code'];
        $response = WebHttp::request($url);
        // parse access token
        $params = null;
        parse_str($response, $params);
        // save access token
        WebSession::put('facebook_access_token', @$params['access_token']);
    }

}
