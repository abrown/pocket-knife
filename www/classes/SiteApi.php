<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides an API server with an documentation pages for each class
 * with filename in the form 'SomeClass.php'.
 * @uses BasicClass, Site
 */
class SiteAPI extends Site{

	/**
	 * Returns a list of classes in the framework
	 * @throws ExceptionFile
	 */
	public function getClassList(){
		$classes = array();
		$stack = array( get_base_dir() );
		// iteratively search for classes
		while( $stack ){
			$current = array_pop($stack);
			foreach(scandir($current) as $file){
				// filter
				if( $file == '.' || $file == '..' || $file == 'test' ) continue;
				// make absolute path
				$_file = $current.DS.$file;
				// case: directory
				if( is_dir($_file) ) array_push($stack, $_file);
				// case: file
				if( is_file($_file) && preg_match('/[A-Z]\w+\.php/', $file) ){
					$class = str_replace(get_base_dir().DS, '', $_file);
					$class = str_replace(DS, '', $class);
					$classes[] = str_replace('.php', '', $class);
				}
			}
		}
		// sort
		sort($classes);
		// return
		return $classes;
	}

	/**
	 * Create HTML for overview page
	 * @return string HTML
	 */
	public function getOverviewHtml(){
		$classes = self::getClassList();
		$item_format = '<li><a href="'.WebRouting::getLocationUrl().'/%s">%s</a></li>';
		$list_format = '<ul class="pocket-knife-classes">%s</ul>';
		$html = array();
		foreach($classes as $class){
			$html[] = sprintf($item_format, $class, $class);
		}
		return sprintf($list_format, implode($html, "\n"));
	}

	/**
	 * Returns HTML for a class; wraps BasicClass::getDocumentation()
	 * @param string $class
	 * @return string
	 */
	public function getClassHtml($class){
		return BasicClass::getDocumentation($class);
	}

	/**
	 * Executes the API site server
	 */
	public function execute(){
		// get class
		$class = WebRouting::getAnchoredUrl();
		// make title
		if( !$class ){
			$title = 'Overview';
			$file = null;
			$code = null;
			$html = $this->getOverviewHtml();
		}
		else{
			$title = $class;
			$file = BasicClass::getPathToClass($class);
			$code = file_get_contents($file);
			$html = BasicClass::getDocumentation($class);
		}
		// do templating
		if ($this->template) {
			$this->getTemplate()->setVariable('title', $title);
			$this->getTemplate()->setVariable('file', $file);
			$this->getTemplate()->setVariable('code', $code);
			$this->getTemplate()->setVariable('html', $html);
			$content = $this->getTemplate()->toString();
		}
		// output
		echo $content;
	}
}