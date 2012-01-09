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
	
	/**
	 * Builds a SiteAdministration object when passed a working
	 * Site object.
	 * @param Site $site
	 * @throws ExceptionSettings
	 */
	public function __construct($site){
		if( !is_a($site, 'Site') ) throw new ExceptionSettings('SiteAdministration must start with an active Site', 404);
		$this->site = &$site;
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
		<input type="text" id="admin-search-files" value="search" />
	</p>
	<ul class="admin-site-map">
		<template:files/>
	</ul>';
	
	public function home(){
		// create url
		$url = WebRouting::getLocationUrl();
		// create site map
		$files = '';
	    foreach( $this->site->getSiteMap() as $file ){
	    	//$_url = WebHttp::normalize($url.'?file='.$file.'&action=edit');
        	$_url = $url.'?file='.$file.'&action=edit';
	    	$files .= "<li><a href='_$url'>$file</a></li>";
        }
        // create page
        $template = new WebTemplate($this->home_template, WebTemplate::STRING);
        $template->replace('files', $files);
        // return
        return $template->toString();
	}
	
	public function edit($file){
		
	}
	public function save($file){
		
	}
	public function delete($file){
		
	}
	public function rebuild(){
		
	}
}