<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides tools for editing both the files and index of a 
 * Site.
 */
class SiteAdministration{
	
	/**
	 * Reference to the parent site
	 * @var Site
	 */
	protected $site;
	
	protected $template;
	
	/**
	 * Builds a SiteAdministration object when passed a working
	 * Site object.
	 * @param Site $site
	 * @throws ExceptionSettings
	 */
	public function __construct($site, $template){
		if( !is_a($site, 'Site') ) throw new ExceptionSettings('SiteAdministration must start with an active Site', 404);
		$this->site = &$site;
		if( !$template ) throw new ExceptionSettings('SiteAdministration requires a template.', 404);
		$this->template = $template;
	}
	
	/**
	 * Performs routing for administration.
	 * @return string 
	 */
	public function execute(){
		// check routing
		if( !isset($_GET['action']) || !method_exists($this, $_GET['action']) ) $action = 'home';
		else $action = $_GET['action'];
		// check file existence
		$file = @$_GET['file'];
		// do action
		$content = $this->$action($file);
		// do templating
		$this->getTemplate()->replace('content', $content);
		$this->getTemplate()->setVariable('content', $content);
		$content = $this->getTemplate()->toString();
		// return
		echo $content;
	}
	
	/**
	 * HTML template for 'home' action
	 * @var string
	 */
	protected $home_template = '
	<p>
		<a href="">Edit Settings</a>
		<a href="">Edit Template</a>
	</p>
	<p>
		Select a file to edit:
	</p>
	<p>
		<input type="text" id="admin-search-files" value="search" 
		onfocus="if(this.value==\'search\') this.value = \'\';" />
	</p>
	<ul class="admin-site-map">
		<template:files/>
	</ul>';
	
	public function home(){
		// create url
		$url = WebRouting::getLocationUrl().'/admin';
		// create site map
		$files = '';
	    foreach( $this->site->getSiteMap() as $file ){
	    	//$_url = WebHttp::normalize($url.'?file='.$file.'&action=edit');
        	$_url = $url.'?file='.$file.'&action=edit';
	    	$files .= "<li><a href='$_url'>$file</a></li>";
        }
        // create page
        $template = new WebTemplate($this->home_template, WebTemplate::STRING);
        $template->replace('files', $files);
        // return
        return $template->toString();
	}
	
	protected $edit_template = '
	<script type="text/javascript">
	function insertAtCursor(myField, myValue) {
		//IE support
		if (document.selection) {
			myField.focus();
			sel = document.selection.createRange();
			sel.text = myValue;
		}
		//MOZILLA/NETSCAPE support
		else if (myField.selectionStart || myField.selectionStart == \'0\') {
			var startPos = myField.selectionStart;
			var endPos = myField.selectionEnd;
			myField.value = myField.value.substring(0, startPos)
				+ myValue
				+ myField.value.substring(endPos, myField.value.length);
		} else {
			myField.value += myValue;
		}
	}
	</script>
	<form action="<template:save_url/>" method="POST">
		<textarea id="admin-file" name="file-contents"
		onkeydown="if(window.event.keyCode==9){insertAtCursor(this, \'    \'); return false;}"><template:file/></textarea>
		<input type="submit" value="Save"/>
		<input type="submit" value="Cancel" onclick="window.location = \'<template:back_url/>\'; return false;" />
	</form>
	';
	public function edit($file){
		// find file
		$absolute_file = $this->site->find($file);
		// create URLs
		$url = WebRouting::getLocationUrl().'/admin';
		$save_url = $url.'?file='.$file.'&action=save';
		// get file contents
		$file_contents = file_get_contents($absolute_file);
		// create page
		$template = new WebTemplate($this->edit_template, WebTemplate::STRING);
		$template->replace('save_url', $save_url);
		$template->replace('file', $file_contents);
		$template->replace('back_url', $url);
		// return
		return $template->toString();
	}
	
	protected $save_template = '
	<p>The file "<template:file/>" has been saved</p>
	<p><a href="<template:back_url/>">Back</a></p>';
	public function save($file){
		// find file
		$absolute_file = $this->site->find($file);
		// create URLs
		$url = WebRouting::getLocationUrl().'/admin';
		// save file
		file_put_contents($absolute_file, $_POST['file-contents']);
		// create page
		$template = new WebTemplate($this->save_template, WebTemplate::STRING);
		$template->replace('file', $file);
		$template->replace('back_url', $url);
		// return
		return $template->toString();
		
	}
	public function delete($file){
		
	}
	public function rebuild(){
		
	}
	
	/**
	* Creates and returns the applicable admin template for this request.
	* @return WebTemplate
	*/
	private function getTemplate() {
		static $object = null;
		if (!$object) {
			$template_file = $this->template;
			$object = new WebTemplate($template_file, WebTemplate::PHP_FILE);
		}
		return $object;
	}
}