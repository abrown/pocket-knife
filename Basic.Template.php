<?php
/**
 * @copyright Copyright 2009 Gearbox Studios. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class Template{

    /**
     * Working text
     * @var <string>
     */
    public $text;

    /**
     * Constructor
     * @param <string> Path/Text
     */
    public function __construct($string = null){
        if( !$string ) return;
        if( $this->isPath($string) ){
            $this->text = $this->getTextFromPath($string);
        }
        else{
            $this->text = $this->getTextFromText($string);
        }
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
     * Gets text from given string (does not evaluate PHP code by default)
     * @param <string> $text
     * @param <boolean> $evaluate text as code
     * @param <array> $variables for evaluation context
     */
    private function getTextFromText( $text, $evaluate = false, $variables = array() ){
        if( $evaluate ){
            // in case of extracted variables overwrite
            $_unlikely_variable_name = $text;
            // extract context variables
            if( is_array($variables) ) extract($variables);
            // eval code
            ob_start();
            eval('?>'.$_unlikely_variable_name);
            return ob_get_clean();
        }
        else{
            return $text;
        }
    }

    /**
     * Sets template text from a given path (evaluates PHP code by default)
     * @param <string> $text
     * @param <boolean> $evaluate text as code
     * @param <array> $variables for evaluation context
     */
    private function getTextFromPath ($path, $evaluate = true, $variables = array() ){
        $path = $this->getAbsolutePath($path);
        // get contents or set text as error
        if( is_file($path) ){
            $text = file_get_contents($path);
        }
        else{
            $text = '<b>Error:</b> Could not find '.$path;
        }
        // reuse FromText to evaluate PHP code
        return $this->getTextFromText($text, $evaluate, $variables);
    }

    /**
     * Get valid path
     * @param <string> $path
     * @return <string>
     */
    private function getAbsolutePath($path){
        $config = Configuration::getInstance();
        if( array_key_exists('template_path', $config) ) $path = $config['template_path'].$path;
        $path = str_replace('//', '/', $path);
        return $path;
    }

    /**
     * Replace
     * @param <string> $token to replace
     * @param <string> $string to replace with
     * @param <boolean> $evaluate string as code
     * @param <array> $context_variables to use in evaluated template code
     */
    public function replace($token, $string, $evaluate = 'default', $variables = array() ){
        if( $string && $this->isPath($string) ){
            if( $evaluate == 'default' ) $evaluate = true;
            $text = $this->getTextFromPath($string, $evaluate, $variables);
        }
        else{
            if( $evaluate == 'default' ) $evaluate = false;
            $text = $this->getTextFromText($string, $evaluate, $variables);
        }
        // replace
        $token = '<K:'.$token.'/>';
        $this->text = str_ireplace($token, $text, $this->text);
    }

    /**
     * Replaces and returns text without dangling tokens
     * @return <string> text without tokens
     */
    private function replaceDanglingTokens(){
        $pattern = '#<K:\w+/>#i';
        return preg_replace($pattern, '', $this->text);
    }

    /**
     * Returns template with all current replacements
     * @return <string>
     */
    public function toString(){
        return $this->replaceDanglingTokens();
    }

    /**
     * Display template text
     */
    public function display(){
        echo $this->toString();
    }
}