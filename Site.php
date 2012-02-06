<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides a system for storing, indexing, editing, and serving a
 * group of files. 
 * When using templates, the following variables and tokens are
 * available.
 * @example
 * // the 'content' token is replaced with the file in question
 * &lt;template:content/&gt;
 * // the 'content' variable is also available, and contains the 
 * // same information as above
 * echo $content;
 * // the 'name' variable holds the anchored URL, while 'path'
 * // holds the absolute path to 'name';
 * echo $name; // e.g. 'dir/page.html'
 * echo $path; // e.g. '/var/www/dir/page.html'
 * // the 'site' variable references the Site service (the actual
 * // object created with this class)
 * echo $site->search('...'); 
 * @uses Settings, WebRouting, WebTemplate, ExceptionFile, ExceptionAccess
 */
class Site{
    
    /**
     * Path to site files
     * @var string 
     */
    public $location = '.';
    
    /**
     * Defines access to each page; set to true to allow all, 
     * false to deny all.
     * @example $this->acl = array('user/29 can access index.html');
     * @var mixed
     * */
    public $acl = true;

    /**
     * Defines the storage method for storing the site map; see classes 
     * in Storage for specific parameters required.
     * @example $this->storage = array('type'=>'mysql', 'username'=>'test', 'password'=>'password', 'location'=>'localhost', 'database'=>'db');
     * @var array
     * */
    public $storage = array('type'=>'json', 'location'=>'site-map.json');

    /**
     * Defines the output content-type of the response; setting 
     * (bool) false allows the web server to determine this.
     * @example $this->output = 'application/json';
     * @var string
     * */
    public $output = false;

    /**
     * Template to apply to the output after processing
     * @var string
     * */
    public $template;
    
    /**
     * Template to apply to the admin HTML content. If left false,
     * administration will be unavailable.
     * @var string
     */
    public $admin;
    
    /**
     * Constructor
     * @param Settings $settings 
     */
    function __construct($settings){
        // determines what settings must be passed
        $settings_template = array(
            'location' => Settings::MANDATORY | Settings::PATH,
            'acl' => Settings::MANDATORY,
            'storage' => Settings::MANDATORY | Settings::MULTIPLE,
            'output' => Settings::OPTIONAL,
            'template' => Settings::OPTIONAL | Settings::PATH,
            'admin' => Settings::OPTIONAL | Settings::PATH
        );
        // accepts settings
        if (!$settings || !is_a($settings, 'Settings'))
            throw new ExceptionSettings('Incorrect settings given.', 500);
        $settings->validate($settings_template);
        // copy settings into this object
        foreach ($this as $key => $value) {
            if (isset($settings->$key))
                $this->$key = $settings->$key;
        }
        // absolutize path
        $this->location = realpath($this->location);
    }
    
    /**
     * Returns this object's URI
     * @return string
     */
    public function getURI( $file = null ){
        return WebRouting::getLocationUrl(); // e.g. http://example.com/site.php
    }
    
    /**
     * Executes the site server.
     * @example
     * // 'admin' invokes the administration section of the site
     * http://example.com/dir/site.php/admin
     * // 'clear' deletes all index entries to refresh the site map
     * http://example.com/dir/site.php/clear
     */
    public function execute(){
    	// send header (if necessary)
    	if( $this->output ) header( 'Content-Type: '.$this->output );
        // get name, find file
        $name = WebRouting::getAnchoredUrl();
        // routing
        switch($name){
        	case 'admin':
        		if( !$this->admin ) throw new ExceptionWeb('Site administration is disabled.', 404);
        		// create
        		$admin = new SiteAdministration($this, $this->admin);
        		$content = $admin->execute();
        		return;
        		break;
        	default:
        		// get content
        		$path = $this->find($name);
		       	if( is_file($path) ) $content = file_get_contents($path);
				else $content = null;
				// do templating
				if ($this->template) {
					$this->getTemplate()->replace('content', $content);
					$this->getTemplate()->setVariable('content', $content);
					$this->getTemplate()->setVariable('name', $name);
					$this->getTemplate()->setVariable('path', $path);
					$this->getTemplate()->setVariable('site', $this);
					$content = $this->getTemplate()->toString();
				}
        }
        // output
        echo $content;
    }
    
    /**
     * Invokes administration section
     * @return string
     */
    public function executeAdmin(){
    	return '<h2>TODO: Admin section</h2>';
    }
    
    /**
     * Clears the site map index and returns an HTML message.
     * @return string
     */
    public function executeClearIndex(){
    	$this->getStorage()->begin();
    	$this->getStorage()->deleteAll();
    	$this->getStorage()->commit();
    	return '<h2>Site map index cleared.</h2>';
    }
    
    /**
     * Returns an absolute path when given a filename relative 
     * to the site, e.g. folder/index.html
     * @param string $filename
     * @return string path to file
     */
    public function find( $filename ){
        $path = $this->location . DS. $filename;
        // readability check
        if( !is_readable($path) ) throw new ExceptionFile("File {$path} is not readable.", 404);
        // access check, TODO
        
        // return
        return $path;
    }
    
    /**
     * Searches for a text string within the site
     * TODO:
     */
    public function search( $query ){
        // TODO: implement index
    }
    
    /**
     * Returns a site map: a list of all files located within 
     * $this->location. It will filter out all files beginning 
     * with a period. It also caches its results for 24 hours;
     * use the '.../clear' URL action (see execute()) to refresh
     * the cache.
     * @return array
     */
    public function getSiteMap(){
        // run spider?
        $empty = $this->getStorage()->count() < 1;
        $stale = $empty ? true : time() > $this->getStorage()->getLastModified() + 86400; // more than a day old
        if( $empty || $stale  ){
            // begin
            $this->getStorage()->begin();
            $this->getStorage()->deleteAll();
            // crawl through files
            $stack = array( $this->location );
            // iteratively search for files
            while( $stack ){
            	$current_directory = array_pop($stack);
            	foreach( scandir($current_directory) as $file ){
            		// filter
            		if( $file[0] == '.' ) continue;
            		// make absolute path
            		$absolute_path = $current_directory.DS.$file;
            		// case: directory
            		if( is_dir($absolute_path) ){
            			array_push($stack, $absolute_path);
            		}
            		// case: file
            		if( is_file($absolute_path) ){
				$relative_path = str_ireplace($this->location.DS, '', $absolute_path);
            			$this->getStorage()->create($relative_path);
            		}
            	}
            }
            // commit
            //$this->getStorage()->create(time(), '_updated');
            $this->getStorage()->commit();
        }
        // return
        return $this->getStorage()->all();
    }
    
    /**
     * Creates and returns the applicable template for this request.
     * Must be public because it is accessed by SiteAdministration.
     * @return WebTemplate
     */
    public function getTemplate() {
        static $object = null;
        if (!$object) {
            $template_file = $this->template;
            $object = new WebTemplate($template_file, WebTemplate::PHP_FILE);
        }
        return $object;
    }
    
    /**
     * Returns the storage object for this request.
     * Must be public because it is accessed by SiteAdministration.
     * @return StorageInterface
     * */
    public function getStorage() {
        static $object = null;
        if (!$object) {
            $settings = new Settings($this->storage);
            // check Settings
            if ( !isset($settings->type) )
                throw new ExceptionSettings('Storage type is not defined', 500);
            // get class
            $class = 'Storage'.ucfirst($settings->type);
            // check parents
            if (!in_array('StorageInterface', class_implements($class)))
                throw new ExceptionSettings($class.' must implement StorageInterface.', 500);
            // create object
            $object = new $class($settings);
        }
        return $object;
    }
}