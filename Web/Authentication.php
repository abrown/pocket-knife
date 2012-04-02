<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class WebAuthentication{

    /**
     * Message to be displayed to unauthenticated users
     * @example
     * MESSAGE OF THE DAY:
     * ...
     * @endexample
     * @var <string>
     */
    protected $message = 'Access denied.';

    /**
     * Instance of the access control list
     * @var <AuthenticationACLAbstract>
     */
    protected $acl;

    /**
     * A factory constructor for the authentication methods
     * @param <string> $type
     * @return <Authentication>
     */
    public static function factory($type){
        $class = 'AuthenticationMethod'.ucfirst( strtolower( $type ) );
        return new $class($type);
    }

    /**
     * Set message-of-the-day
     * @param <string> $message
     */
    public function setMessage( $message ){
        $this->message = $message;
    }

    /**
     * Get message-of-the-day
     * @return <string>
     */
    public function getMessage(){
        return $this->message;
    }

    /**
     * Set ACL type
     * @param <string> $type
     */
    public function setACL($type){
        $class = 'AuthenticationACL'.ucfirst( strtolower( $type ) );
        $this->acl = new $class;
    }

    /**
     * Get ACL
     * @return <AccessControlList>
     */
    public function getACL(){
        return $this->acl;
    }

    /**
     * Force SSL use by looking at URL
     */
    public function forceSSL(){
        if( substr(Http::getUrl(), 0, 5) !== 'https' ){
            Http::redirect( 'https'.substr(Http::getUrl(), 4) );
        }
    }
}

/**
 * All authentication methods must implement: isAllowed() and challenge()
 */
interface AuthenticationMethodInterface{
    /**
     * Checks if the authentication is valid
     * @return <bool>
     */
    public function isAllowed();

    /**
     * Sends challenge to unauthenticated user
     */
    public function challenge();
}

/**
 * Basic Transport Implementation
 */
class AuthenticationMethodBasic extends Authentication implements AuthenticationMethod{

    /**
     * Checks if the authentication is valid
     * @return <boolean> allowed
     */
    public function isAllowed(){
        list($username, $password) = $this->getRequest();
        if( !$username || !$password ) return false;
        // check if user exists
        if( !$this->getAcl()->exists($username) ) return false;
        // check if transport validates
        return $this->getAcl()->valid($username, $password);
    }

    /**
     * Get basic authorization
     * Uses .htaccess rewrite to add HTTP_AUTHORIZATION as an environment variable
     *      RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
     * @return <array> or null on failure
     */
    public function getRequest(){
        if( isset($_SERVER['HTTP_AUTHORIZATION']) ){
            if( preg_match('/Basic (.*)/i', $_SERVER['HTTP_AUTHORIZATION'], $match) ){
                $string = trim( $match[1] );
                $string = base64_decode( $string );
                list($username, $password) = explode(':', $string, 2);
                return array($username, $password);
            }
        }
        return array(null, null);
    }

    /**
     * Sends header to unauthorized users
     */
    public function challenge(){
        header('WWW-Authenticate: Basic realm="'.$this->message.'"');
    }
}

/**
 * Digest Transport Authentication
 */
class AuthenticationMethodDigest extends Authentication implements AuthenticationMethod{

    /**
     * Checks if the requesting user is allowed
     * @return <bool>
     */
    public function isAllowed(){
        // check if ACL type is valid
        if( !method_exists($this->getAcl(), 'getPassword') )
            throw new Error('Cannot use Digest Authentication with this ACL type.');
        // setup request data
        $auth = $this->getRequest();
        if( !$auth ) return false;
        extract($auth);
        // create valid response
        $A1 = md5( $username.':'.$this->message.':'.$this->getAcl()->getPassword($username) );
        $A2 = md5( $_SERVER['REQUEST_METHOD'].':'.$uri );
        $valid = md5( $A1.':'.$nonce.':'.$nc.':'.$cnonce.':'.$qop.':'.$A2 );
        // check response
        if( $response === $valid ) return true;
        else return false;
    }

    /**
     * Get and parse digest authorization
     * Uses .htaccess rewrite to add HTTP_AUTHORIZATION as an environment variable
     *      RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
     * @return <array> or null on failure
     */
    public function getRequest(){
        if( isset($_SERVER['HTTP_AUTHORIZATION']) ){
            // from http://us3.php.net/manual/en/features.http-auth.php
            $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
            $data = array();
            $keys = implode('|', array_keys($needed_parts));
            preg_match_all('@('.$keys.')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $_SERVER['HTTP_AUTHORIZATION'], $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $data[$m[1]] = $m[3] ? $m[3] : $m[4];
                unset($needed_parts[$m[1]]);
            }
            return $needed_parts ? null : $data;
        }
        return null;
    }

    /**
     * Sends header to unauthorized users
     */
    public function challenge(){
        $options[] = 'realm="'.$this->message.'"';
        $options[] = 'qop="auth"';
        $options[] = 'nonce="'.$this->nonce().'"';
        $options[] = 'opaque="'.md5($this->message).'"';
        header('WWW-Authenticate: Digest '.implode(',', $options));
    }

    /**
     * Create nonce
     * @return <string>
     */
    private function nonce(){
        return uniqid( 'pocket-knife:', true ); // prefix, more entropy
    }
}

/**
 * Session Authentication
 */
class AuthenticationMethodHtml extends Authentication implements AuthenticationMethod{

    /**
     * Checks if the authentication is valid
     * @return <boolean> allowed
     */
    public function isAllowed(){
        list($username, $password) = $this->getRequest();
        if( !$username || !$password ) return false;
        // check if user exists
        if( !$this->getAcl()->exists($username) ) return false;
        // check if transport validates
        return $this->getAcl()->valid($username, $password);
    }

    /**
     * Get POSTed username/password
     * @return <array> or null on failure
     */
    public function getRequest(){
        if( isset($_POST['username']) ){
            return array($_POST['username'], $_POST['password']);
        }
        else return array(null, null);
    }

    /**
     * Sends HTML login to unauthorized users
     */
    public function challenge(){
        echo "<form method='POST'><label for='username'>Username</label><input type='text' name='username' id='username'/><label for='password'>Password</label><input type='password' name='password' id='password'/><input type='submit' value='login'/></form>";
    }
}

/**
 * ACL
 */
abstract class AuthenticationACLAbstract{
    abstract public function exists($username);
    abstract public function valid($username, $password);
}

/**
 * Array ACL
 */
class AuthenticationACLArray extends AuthenticationACLAbstract{

    /**
     * List of users
     * @var <array>
     */
    protected $users = array();

    /**
     * Constructor
     * @param <array> list of [usernames] => passwords
     */
    public function __construct($list = null){
        if( $list ) $this->users = $users;
    }
    /**
     * Add a user to the ACL
     * @param <string> $username
     * @param <string> $password
     */
    public function add($username, $password){
        $this->users[$username] = $password;
    }

    /**
     * Add users to list
     * @param <array> $users
     */
    public function addUsers($users){
        $this->users = array_merge($this->users, $users);
    }

    /**
     * Check if username exists
     * @param <string> $username
     * @return <boolean>
     */
    public function exists( $username ){
        return isset($this->users[$username]);
    }

    /**
     * Check if password is valid
     * @param <string> $username
     * @param <string> $password
     * @return <boolean>
     */
    public function valid( $username, $password ){
        if( !$this->exists($username) ) return false;
        return $this->users[$username] == $password;
    }

    /**
     * Get user's password (used by digest transport)
     * @param <string> $username
     * @return <string>
     */
    public function getPassword( $username ){
        if( !$this->exists($username) ) return null;
        else return $this->users[$username];
    }

    /**
     * Get user's id
     * @param <string> $username
     * @return <int>
     */
    public function getId( $username ){
        if( !$this->exists($username) ) return null;
        $index = 1;
        foreach($this->users as $u => $p){
            if( $u == $username ){
                return $index;
            }
            $index++;
        }
    }
}

/**
 * Settings ACL (very similar to Array, just acquire array from config)
 */
class AuthenticationACLSettings extends AuthenticationACLArray{

    /**
     * Add users to list
     * @param <array> $list
     */
    public function __construct(){
        $config = Settings::getInstance();
        $this->users = $config['acl'];
    }

}

// TODO: test PDO ACL database
/**
 * Database ACL, assume password is hashed
 */
class AuthenticationACLDatabase extends AuthenticationACLAbstract{

    /**
     * Table and fields
     * @var <type>
     */
    private $table = 'users';
    private $ifield = 'id';
    private $ufield = 'username';
    private $pfield = 'password';

    /**
     * Users list (cache)
     * @var <array>
     */
    private $users;

    /**
     * Retrieve user from database with username field
     * @param <string> $username
     * @return <array>
     */
    private function getUser($username){
        if( !isset($this->users[$username]) ){
            $database = Database::getInstance();
            // prepare and execute statement
            $sql = $database->prepare( "SELECT * FROM `{$this->table}` WHERE `{$this->ufield}`= ?" );
            $sql->bindParam(1, $username);
            $sql->execute();
            // save results
            $this->users[$username] = $sql->fetch(PDO::FETCH_ASSOC);
        }
        return $this->users[$username];
    }

    /**
     * Check if username exists
     * @param <string> $username
     * @return <bool>
     */
    public function exists( $username ){
        if( $this->getUser($username) ) return true;
        else return false;
    }

    /**
     * Check if password is valid
     * @param <string> $username
     * @param <string> $password
     * @return <boolean>
     */
    public function valid( $username, $password ){
        if( !$this->exists($username) ) return false;
        return $this->users[$username] == md5($password);
    }
    
    /**
     * Get user's id
     * @param <string> $username
     * @return <mixed>
     */
    public function getId( $username ){
        $user = $this->getUser($username);
        if( !$user ) return null;
        if( !isset($user[$this->ifield]) ) return null;
        else return $user[$this->ifield];
    }
}