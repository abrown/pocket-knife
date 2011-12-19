<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Site
 * @uses Settings, WebRouting, WebTemplate, ExceptionFile, ExceptionAccess
 */
class Site{
    
    /**
     * Path to site files
     * @var string 
     */
    private $location = '.';
    
    /**
     * Defines access to each page; set to true to allow all, false to deny all
     * @example $this->acl = array('user/29 can access index.html');
     * @var mixed
     * */
    public $acl = true;

    /**
     * Defines storage method for storing the site map; see classes in Storage for specific parameters required
     * @example $this->storage = array('type'=>'mysql', 'username'=>'test', 'password'=>'password', 'location'=>'localhost', 'database'=>'db');
     * @var array
     * */
    public $storage = array('type'=>'json', 'location'=>'site-map.json');

    /**
     * Defines the output content-type of the response; setting (bool) false allows the web server to determine this
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
            'template' => Settings::OPTIONAL | Settings::PATH
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
     * Executes the site server
     * TODO: add 'admin' handling
     */
    public function execute(){
        // find file
        $filename = $this->location . DS . WebRouting::getAnchoredUrl();
        $path = $this->find($filename);
        // send header (if necessary)
        if( $this->output ) header( 'Content-Type: '.$this->output );
        // get contents
        $content = file_get_contents($path);
        // do templating
        if ($this->template) {
            $this->getTemplate()->replace('content', $content);
            $content = $this->getTemplate()->toString();
        }
        // output
        echo $content;
    }
    
    /**
     * Returns an absolute path when given a filename relative to the site, e.g. folder/index.html
     * @param string $filename
     * @return string path to file
     */
    public function find( $filename ){
        $path = $this->location . DS. $filename;
        // readability check
        if( !is_readable($path) ) throw new ExceptionFile("File {$path} is not readable.", 404);
        // access check
        // TODO
        // return
        return $path;
    }
    
    /**
     * Searches for a text string within the site
     */
    public function search( $query ){
        // TODO: implement index
    }
    
    /**
     * Returns site map
     * @return array
     */
    public function getSiteMap(){
        // run spider?
        $empty = $this->getStorage()->count() < 1;
        $stale = $empty ? true : time() > $this->getStorage()->read('_updated') + 86400; // more than a day old
        if( $empty || $stale  ){
            // begin
            $this->getStorage()->begin();
            $this->getStorage()->deleteAll();
            // change directory
            $directory = getcwd();
            $result = chdir($this->location);
            if( !$result ) throw new ExceptionFile('Site cannot crawl in '.$this->location, 404);
            // crawl
            foreach( glob('*') as $filename ){
                if( $filename[0] == '.' ) continue;
                $this->getStorage()->create($filename);
            }
            // change directory back
            chdir($directory);
            // commit
            $this->getStorage()->create(time(), '_updated');
            $this->getStorage()->commit();
        }
        // return
        return $this->getStorage()->all();
    }
    
    /**
     * Return the applicable template for this request
     * @return object
     * */
    protected function getTemplate() {
        static $object = null;
        if (!$object) {
            $template_file = $this->template;
            $object = new WebTemplate($template_file, WebTemplate::PHP_FILE);
            $object->setVariable('site', $this); // TODO: does this violate simplicity?
        }
        return $object;
    }
    
    /**
     * Returns the storage object for this request
     * @var array
     * */
    protected function getStorage() {
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