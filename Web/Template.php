<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * WebTemplate
 * @uses
 * @example
 * Should work like:
 *  $t = new Template('file.html', Template::FILE);
 *  $t->replace('var', 'some value...');
 *  $t->display();
 */
// TODO: split replace into replace, replaceFromPHP, replaceFromFile
// TODO: split out __construct as well
class WebTemplate{

    /**
     * Working text
     * @var <string>
     */
    private $text;
    
    /**
     * Attempts to use TidyHTML to clean up code
     * @var bool 
     */
    private $tidy = false;
    
    /**
     * Inserts the file name of a template as a comment before the template begins
     * E.g.: <!-- template.html -->
     * @var bool 
     */
    private $insert_comment = true;
    
    /**
     * Defines the token syntax; by default, Template will find '<K:your_token/>'
     * @var string 
     */
    public $token_begin = '<K:';
    public $token_end = '/>';

    /**
     * Types of input
     */
    const STRING = 0;
    const FILE = 1;
    const PHP_STRING = 2;
    const PHP_FILE = 3;
    
    /**
     * Constructor
     * @param <string> Path/Text
     */
    public function __construct($input, $type = self::FILE){
        $on_off = $this->insert_comment;
        $this->setInsertComment(false);
        // get first text
        switch($type){
            case self::STRING: $this->text = $input; break;
            case self::FILE: $this->text = $this->getFile($input); break;
            case self::PHP_STRING: $this->text = $this->getPHPString($input); break;
            case self::PHP_FILE: $this->text = $this->getPHPFile($input); break;
        }
        // reset insert_comment
        $this->setInsertComment($on_off);
    }

    /**
     * Sets text
     * @param string $text 
     */
    public function setText($text){
        // set text
        $this->text = $text;
    }
    
    /**
     * Sets TidyHTML option; if true, will attempt to fix and format HTML
     * @param bool $on_off 
     */
    public function setTidy($on_off){
        $this->tidy = $on_off;
    }
    
    /**
     * Sets insert_commment; if true, the file/token name of a template will be 
     * printed as a comment before the template code
     * @param type $on_off 
     */
    public function setInsertComment($on_off){
        $this->insert_file_comment = $on_off;
    }
    
    /**
     * Checks whether given string is a likely path
     * @param <string> $path
     * @return <boolean>
     */
    private function isPath($path){
        $score = 0;
        $target = 2;
        // tests
        if( strpos($path, DS) !== false ) $score = $score + 2;
        if( strlen($path) < 50 ) $score++;
        if( strpos($path, ' ') === false ) $score++;
        if( strpos($path, '<') !== false ) $score = $score - 2;
        // return
        return $score > $target;
    }

    /**
     * Get valid path
     * @param <string> $path
     * @return <string>
    private function getAbsolutePath($path){
        $config = Configuration::getInstance();
        if( array_key_exists('template_path', $config) ) $path = $config['template_path'].$path;
        else throw new Exception('No template path defined', 400);
        $path = str_replace('//', '/', $path);
        return $path;
    }
     * 
     */

    /**
     * Replace token with string
     * @param string $token to replace
     * @param string $string to replace with
     */
    public function replace($token, $string ){
        $token = $this->token_begin.$token.$this->token_end;
        // replace
        $this->text = str_ireplace($token, $string, $this->text);
    }
   
    /**
     * Returns file as string; attempts to find file using base_dir
     * @param string $file
     * @return type 
     */
    public function getFile($file){
        // check file
        if( !is_file($file) ){
            $file = get_base_dir().DS.$file;
            pr($file);
            if( !is_file($file) ) throw new ExceptionFile('Could not find '.$file, 404);
        }
        // return
        return file_get_contents($file);
    }
    
    /**
     * Replaces token with simple file output
     * @param string $token
     * @param string $file
     */
    public function replaceFromFile($token, $file){
        $string = $this->getFile($file);
        // insert comment
        if( $this->insert_comment ){
            $_file = basename($file);
            $string = "<!-- FILE = $_file, TOKEN = $token -->\n".$string;
        }
        // replace
        $this->replace($token, $this->getFile($file));
    }
    
    /**
     * Returns evaluated string from PHP code
     * @param type $string__hide__
     * @param type $variables__hide__
     * @return type 
     */
    public function getPHPString($string__hide__, $variables__hide__ = array()){
        // extract context variables
        if( is_array($variables__hide__) ) extract($variables__hide__);
        // eval code
        ob_start();
        eval('?>'.$string__hide__);
        return ob_get_clean();
    }
    
    /**
     * Replaces token with an evaluated PHP string (strange var names are to avoid collisions with extract)
     * @param string $token__hide__
     * @param string $string__hide__
     * @param array $variables__hide__ 
     */
    public function replaceFromPHPString($token, $string, $variables = array()){
        $string = $this->getPHPString($string, $variables);
        // insert comment
        if( $this->insert_comment ) $string = "<!-- PHP STRING, TOKEN = $token -->\n".$string;
        // replace
        $this->replace($token, $string);
    }
    
    /**
     * Returns evaluated string of PHP file (strange var names are to avoid collisions with extract)
     * @param type $file__hide__
     * @param type $variables__hide__
     * @return type 
     */
    public function getPHPFile($file__hide__, $variables__hide__ = array()){
        // check file
        if( !is_file($file__hide__) ){
            $file__hide__ = get_base_dir().DS.$file__hide__;
            if( !is_file($file__hide__) ) return '<b>Error:</b> Could not find '.$file__hide__;
        }
        // get output
        if( is_array($variables__hide__) ) extract($variables__hide__);
        ob_start();
        require($file__hide__);
        return ob_get_clean();
    }
    
    /**
     * Replaces token with an evaluated PHP file (strange var names are to avoid collisions with extract)
     * @param string $token
     * @param string $file
     * @param array $variables 
     */
    public function replaceFromPHPFile($token, $file, $variables = array()){
        $string = $this->getPHPFile($file, $variables);
        // insert comment
        if( $this->insert_comment ){
            $_file = basename($file);
            $string = "<!-- FILE = $_file, TOKEN = $token -->\n".$string;
        }
        // replace
        $this->replace($token, $string);
    }

    /**
     * Replaces and returns text without dangling tokens
     * @return <string> text without tokens
     */
    private function replaceDanglingTokens(){
        $pattern = '#'.preg_quote($this->token_begin).'([A-Z0-9_\.-]+)'.preg_quote($this->token_end).'#i';
        return preg_replace($pattern, '', $this->text);
    }
    
    /**
     * Returns all of the unreplaced tokens in the template
     * @return array 
     */
    public function findTokens(){
        $pattern = '#'.preg_quote($this->token_begin).'([A-Z0-9_\.-]+)'.preg_quote($this->token_end).'#i';
        $number_of_results = preg_match_all($pattern, $this->text, $matches);
        if( $number_of_results ) return $matches[1];
        else return array();
    }

    /**
     * Returns template with all current replacements
     * @return <string>
     */
    public function toString(){
        if( $this->tidy && class_exists('tidy') ){
            $config = array('indent' => true, 'output-xhtml' => true, 'wrap' => 120, 'indent-spaces' => 4);
            $tidy = new tidy();
            $this->text = $tidy->repairString($this->text, $config, 'utf8');
        }
        return $this->replaceDanglingTokens();
    }

    /**
     * Display template text
     */
    public function display(){
        echo $this->toString();
    }
}