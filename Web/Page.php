<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Page
 * @uses Settings, WebRouting, WebHttp, WebTemplate, ExceptionFile, ExceptionSettings 
 */
class Page{

	/**
	 * Path to the file to serve as a page
	 * @var string
	**/
	public $file;
	
	/**
	 * Content-type of the page 
	 * @var string
	**/
	public $content_type = 'text/html';
	
	/**
	 * Path to the file to use as a Template
	 * @see WebTemplate
	 * @var string
	**/
	public $template;
	
	/**
	 * List of key/values mapping a snippet name (key) to a snippet PHP file (value)
	 * var object
	**/
	public $ajax = array();
    
    /**
     * Constructor
     * @param Settings $Settings 
     */
    public function __construct($Settings){
        // determines what Settings must be passed
        $Settings_template = array(
            'file' => Settings::MANDATORY,
            'content_type' => Settings::OPTIONAL,
            'template' => Settings::OPTIONAL,
            'ajax' => Settings::OPTIONAL | Settings::MULTIPLE
        );
        // accepts Settings
        if( !$Settings || !is_a($Settings, 'Settings') ) throw new ExceptionSettings('Incorrect Settings given.', 500);
        $Settings->validate($Settings_template);
        // copy Settings into this
        foreach($this as $key=>$value){
			if( isset($Settings->$key) ) $this->$key = $Settings->$key;
		}
    }
    
    /**
     * Returns HTML from file
     * @param string $file
     * @return string 
     */
    public function getHtml($file){
        if( !is_file($file) ) throw new ExceptionFile('File not found: '.$file, 404);
        return file_get_contents($file);
    }
    
    /**
     * Returns templated HTML from file
     * @param string $file
     * @param string $template_file
     * @return string 
     */
    public function getTemplatedHtml($file, $template_file){
        $template = new Template($template_file);
        $content = $this->getHtml($file);
        $template->replace('content', content);
        return $template->toString();
    }
    
    /**
     * Returns AJAX snippet HTML; map snippet keys to PHP files in Settings.ajax
     * @example 'ajax' => array('main_content'=>'/dir/to/content.php', 'continuous_feed' => '/dir/to/feed.php');
     * @param string $snippet
     * @return string 
     */
    public function getAjaxHtml($snippet){
        $file = $this->ajax->$snippet;
        if( !is_file($file) ) throw new ExceptionFile('File not found: ', $file, 404);
        ob_start();
        include $file;
        return ob_get_clean();
    }
    
    /**
     * Executes Page, sending HTML data based on WebRouting string
     * @return boolean 
     */
    public function execute(){
        $file = WebRouting::getAnchoredFilename();
        // get AJAX
        $snippet = ( strpos($file, 'ajax:') === 0 ) ? substr($file, 5) : false;
        if( $snippet ){
            $response = $this->getAjaxHtml($snippet);
        }
        // get templated PAGE
        else if( $this->template ){
            $response = $this->getTemplatedHtml($this->file, $this->template);
        }
        // get PAGE
        else{
            $response = $this->getHtml($this->file);
        }
        // send HTML
        WebHttp::setCode(200);
        WebHttp::setContentType($this->content_type);
        echo $response;
        return true;
    }
}