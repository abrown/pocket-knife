<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Page
 * @uses Settings, WebRouting, WebHttp, WebTemplate, Error, Error 
 */
class WebPage {

    /**
     * Path to the file to serve as a page
     * @var string
     * */
    public $file;

    /**
     * Content-type of the page 
     * @var string
     * */
    public $content_type = 'text/html';

    /**
     * Path to the file to use as a Template
     * @see WebTemplate
     * @var string
     * */
    public $template;

    /**
     * List of key/values mapping a snippet name (key) to a snippet PHP file (value)
     * var object
     * */
    public $ajax = array();

    /**
     * Constructor
     * @param Settings $settings 
     */
    public function __construct($settings) {
        // determines what Settings must be passed
        $settings_template = array(
            'file' => Settings::MANDATORY,
            'content_type' => Settings::OPTIONAL,
            'template' => Settings::OPTIONAL,
            'ajax' => Settings::OPTIONAL | Settings::MULTIPLE
        );
        // accepts Settings
        if (!$settings || !is_a($settings, 'Settings'))
            throw new Error('Incorrect Settings given.', 500);
        $settings->validate($settings_template);
        // copy Settings into this
        foreach ($this as $key => $value) {
            if (isset($settings->$key))
                $this->$key = $settings->$key;
        }
    }

    /**
     * Return HTML table representing a Resource
     * @param Resource $resource
     * @return string
     */
    public static function getResourceTable(Resource $resource) {
        $uri = htmlentities($resource->getURI());
        $html = array();
        $html[] = "<table class='{$uri}'>";
        foreach (get_public_vars($resource) as $property => $value) {
            $property = htmlentities($property);
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $value = htmlentities($value);
            $html[] = "<tr>";
            $html[] = "<td class='{$uri}#property' title='{$uri}#property'>{$property}</td>";
            $html[] = "<td id='{$uri}#{$property}' title='{$uri}#{$property}'>{$value}</td>";
            $html[] = "</tr>";
        }
        $html[] = "</table>";
        return implode("\n", $html);
    }

    /**
     * Return HTML form representing a Resource
     * @param Resource $resource
     * @return string
     */
    public static function getResourceForm(Resource $resource) {
        $uri = htmlentities($resource->getURI());
        $html = array();
        $html[] = "<form method='POST' action='" . WebUrl::create($uri, false) . "'>";
        $html[] = "<table class='{$uri}'>";
        foreach (get_public_vars($resource) as $property => $value) {
            $property = htmlentities($property);
            $value = htmlentities($value);
            $html[] = "<tr>";
            $html[] = "<td class='{$uri}#property'>{$property}</td>";
            $html[] = "<td id='{$uri}#{$property}'><input type='text' name='{$property}' value='{$value}' /></td>";
            $html[] = "</tr>";
        }
        // submit
        $html[] = "<tr>";
        $html[] = "<td></td>";
        $html[] = "<td><input type='submit' id='{$uri}#submit' value='Submit' /></td>";
        $html[] = "</tr>";
        $html[] = "</table></form>";
        return implode("\n", $html);
    }

    public static function getResourceList(ResourceList $list) {
        //$uri = htmlentities($list->getURI());
        $uri = 'list';
        $html = array();
        $html[] = "<table class='{$uri}'>";
        // head
        $class = $list->getItemType();
        $object = new $class;
        $html[] = "<tr class='head'>";
        foreach(get_public_vars($object) as $property => $value){
            $html[] = "<th>{$property}</th>";
        }
        $html[] = "<th></th>";
        $html[] = "</tr>";
        // rows
        foreach ($list->items as $item) {
            $_uri = htmlentities($item->getURI());
            $html[] = "<tr>";
            foreach (get_public_vars($item) as $property => $value) {
                $property = htmlentities($property);
                $value = htmlentities($value);
                $html[] = "<td class='{$_uri}#{$property}'>{$value}</td>";
             }
            $html[] = "<td id='{$_uri}#links'>".self::getResourceLinks($item)."</td>";
            $html[] = "</tr>";
        }
        // submit
        $html[] = "</table>";
        return implode("\n", $html);
    }

    /**
     *
     * @param Resource $resource
     * @return type 
     */
    public static function getResourceLinks(Resource $resource) {
        $uri = htmlentities($resource->getURI());
        $html = array();
        $original_method = @$_GET['method'];
        foreach (get_class_methods($resource) as $method) {
            if (!ctype_upper($method))
                continue;
            $_GET['method'] = $method;
            $url = WebUrl::create($uri);
            $html[] = "<a href='{$url}' title='{$uri}'>{$method}</a>";
        }
        $_GET['method'] = $original_method;
        return implode("\n", $html);
    }

    /**
     * Returns HTML from file
     * @param string $file
     * @return string 
     */
    public function getHtml($file) {
        if (!is_file($file))
            throw new Error('File not found: ' . $file, 404);
        return file_get_contents($file);
    }

    /**
     * Returns templated HTML from file
     * @param string $file
     * @param string $template_file
     * @return string 
     */
    public function getTemplatedHtml($file, $template_file) {
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
    public function getAjaxHtml($snippet) {
        $file = $this->ajax->$snippet;
        if (!is_file($file))
            throw new Error('File not found: ', $file, 404);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Executes Page, sending HTML data based on WebRouting string
     * @return boolean 
     */
    public function execute() {
        $file = WebRouting::getAnchoredFilename();
        // get AJAX
        $snippet = ( strpos($file, 'ajax:') === 0 ) ? substr($file, 5) : false;
        if ($snippet) {
            $response = $this->getAjaxHtml($snippet);
        }
        // get templated PAGE
        else if ($this->template) {
            $response = $this->getTemplatedHtml($this->file, $this->template);
        }
        // get PAGE
        else {
            $response = $this->getHtml($this->file);
        }
        // send HTML
        WebHttp::setCode(200);
        WebHttp::setContentType($this->content_type);
        echo $response;
        return true;
    }

}