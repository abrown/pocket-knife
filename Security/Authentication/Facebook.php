<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for authenticating Facebook users with OAuth2.0; to use
 * Facebook authentication, your application must be registered as a Facebook
 * app. To do this:
 * 1) navigate to https://developers.facebook.com/apps and click
 * 'Create New App', enter a name and namespace, and click 'Continue'.
 * 2) Edit the 'Site URL' parameter under 'Website with Facebook Login' to 
 * reflect your current server URL; for safety, Facebook will only redirect 
 * requests to this URL.
 * 3) once the app is registered, copy your 'App ID' and 'App Secret' to your
 * local settings file/database, using the properties 'app_id' and 'app_secret'.
 * Note: as of the writing of this class (May 2012), device authentication is not
 * available to all Facebook apps--therefore, the app uses HTTP redirects to
 * channel the user's browser through the login process. Note that this limits
 * the client to a browser supporting redirects.
 * @uses SecurityAuthentication, WebSession, WebHttp, Error
 */
class SecurityAuthenticationFacebook extends SecurityAuthentication {

    public $app_id = "YOUR_APP_ID";
    public $app_secret = "YOUR_APP_SECRET";

    /**
     * Constructor, sets additional validation rules
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
     * Return whether the user is logged in; since this form of authentication
     * depends on $_SESSION, avoid clearing it or affecting 'facebook_access_token'
     * during execution.
     * @return boolean 
     */
    public function isLoggedIn() {
        if (WebSession::get('facebook_access_token')) {
            return true;
        }
        return false;
    }

    /**
     * Return the name of the current user
     * @return type 
     */
    public function getCurrentUser() {
        if (!WebSession::get('facebook_username')) {
            if (!WebSession::get('facebook_access_token')) {
                return null;
            }
            $url = "https://graph.facebook.com/me?access_token=" . WebSession::get('facebook_access_token');
            $data = json_decode(WebHttp::request($url));
            WebSession::put('facebook_username', $data->name);
        }
        return WebSession::get('facebook_username');
    }

    /**
     * Return the Facebook credentials stored for this session
     * @param string $content_type
     * @return stdClass 
     */
    public function receive($content_type = null) {
        $user = $this->getCurrentUser();
        $out = new stdClass();
        $out->username = $user;
        $out->password = WebSession::get('facebook_access_token');
        // return
        return $out;
    }

    /**
     * Challenges the user with the Facebook login screen; uses HTTP redirects
     * to support OAuth 2.0 authentication
     * @todo when available, implement device authentication instead
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
        // redirect
        WebHttp::redirect(WebUrl::create(WebUrl::getAnchoredUrl(), false));
        exit();
    }

}