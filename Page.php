<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Page
 * @uses Configuration, WebRouting, WebHttp, WebTemplate, ExceptionFile, ExceptionConfiguration 
 */
class Page{
    
    private $configuration;
    
    /**
     * Constructor
     * @param Configuration $configuration 
     */
    public function __construct($configuration){
        // determines what configuration must be passed
        $this->configuration = array(
            'file' => Configuration::MANDATORY,
            'content_type' => Configuration::OPTIONAL,
            'template' => Configuration::OPTIONAL,
            'ajax' => Configuration::OPTIONAL | Configuration::MULTIPLE
        );
        // accepts configuration
        if( !is_a($configuration, 'Configuration') ) throw new ExceptionConfiguration('Incorrect configuration given.', 500);
        $configuration->validate($configuration_template);
        $this->configuration = $configuration;
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
     * Returns AJAX snippet HTML; map snippet keys to PHP files in configuration.ajax
     * @example 'ajax' => array('main_content'=>'/dir/to/content.php', 'continuous_feed' => '/dir/to/feed.php');
     * @param string $snippet
     * @return string 
     */
    public function getAjaxHtml($snippet){
        $file = $this->configuration->ajax->$snippet;
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
        $content_type = ($this->configuration->content_type) ? $this->configuration->content_type : 'text/html';
        // get AJAX
        $snippet = ( strpos($file, 'ajax:') === 0 ) ? substr($file, 5) : false;
        if( $snippet ){
            $response = $this->getAjaxHtml($snippet);
        }
        // get templated PAGE
        else if( $this->configuration->template ){
            $response = $this->getTemplatedHtml($this->configuration->file, $this->configuration->template);
        }
        // get PAGE
        else{
            $response = $this->getHtml($this->configuration->file);
        }
        // send HTML
        WebHttp::setCode(200);
        WebHttp::setContentType($content_type);
        echo $response;
        return true;
    }
}