<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods to modify the inflection of English words.
 * @example
 * $lang = new LanguageInflection('blog_posts');
 * echo $lang->toCamelCaseStyle()->toPlural(); // BlogPosts
 */
class LanguageInflection{

    /**
     * Initial word
     * @var string
     */
    public $before;

    /**
     * Current (modified) word
     * @var string
     */
    public $after;

    /**
     * Constructor
     * @param string $string Words to inflect
     */
    public function __construct($string){
        $this->before = trim($string);
    }

    /**
     * Get word in current state
     * @return string
     */
    public function getWord(){
        if( is_null($this->after) ) return $this->before;
        else return $this->after;
    }

    /**
     * Set word
     * @param string $string
     */
    public function setWord($string){
        $this->after = $string;
    }

    /**
     * Checks if word is in camel-case style
     * @return boolean
     */
    public function isCamelCaseStyle(){
        if( strpos($this->getWord(), '_') !== false ) return true;
        else return false;
    }

    /**
     * Sets current word to camel-case style from underscore style
     * @param boolean $capitalize_first_letter
     * @return LanguageInflection
     */
    public function toCamelCaseStyle( $capitalize_first_letter = true ){
        $word = $this->getWord();
        // capitalize first letter
        if( $capitalize_first_letter ) $word = ucfirst($word);
        // remove spaces and hyphens
        $word = strtr($word, array(' '=>'', '-'=>''));
        // capitalize all letters after an underscore and remove underscore
        $word = preg_replace('/_(.)/e', "strtoupper('\\1')", $word);
        // save
        $this->setWord($word);
        return $this;
    }

    /**
     * Checks if word is in underscore style
     * @return boolean
     */
    public function isUnderscoreStyle(){
        if( strpos($this->getWord(), '_') === false ) return true;
        else return false;
    }

    /**
     * Sets current word to underscore style from camel-case style
     * @param boolean $lowercase_first_letter
     * @return LanguageInflection
     */
    public function toUnderscoreStyle( $lowercase_first_letter = true ){
        $word = $this->getWord();
        // capitalize first letter
        if( $lowercase_first_letter ) $word[0] = strtolower($word[0]);
        // remove spaces and hyphens
        $word = strtr($word, array(' '=>'', '-'=>''));
        // replace camelcase with underscore and lowercase
        $word = preg_replace('/([a-z])([A-Z])/e', "strtolower('\\1_\\2')", $word);
        // save
        $this->setWord($word);
        return $this;
    }

    /**
    * Sets current word to paragraph style (i.e. normal, written English)
    * @param boolean $uppercase_first_letters
    * @return LanguageInflection
    */
    public function toParagraphStyle( $uppercase_first_letters = true ){
    	$word = $this->getWord();
    	// replace camelcase with spaces
    	$word = preg_replace('/([a-z])([A-Z])/e', "strtolower('\\1 \\2')", $word);
    	// replace special characters
    	$word = strtr($word, array('-'=>' ', '_'=>' '));
    	// capitalize?
    	if( $uppercase_first_letters ){
    		$word = preg_replace('/ ([a-z])/e', "strtoupper(' \\1')", ucfirst($word));
    	}
    	// save
    	$this->setWord($word);
    	return $this;
    }
    
    public function removeFileExtension(){
    	$word = $this->getWord();
    	// replace file extensions
    	$word = preg_replace('/\.\w+$/', '', $word);
    	// save
    	$this->setWord($word);
    	return $this;
    }

    /**
     * Singular translation patterns
     * [RegExp] => replacement
     * @var <array>
     */
    private $singulars = array(
        // words
        '/(m)en$/i' => '\1\2an',
        '/(child)ren$/i' => '\1\2',
        '/(g)eese$/i' => '\1oose',
        '/(matr)ices$/i' => '\1ix',
        '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
        '/^(m|l)ice$/i' => '\1ouse',
        '/(vert|ind)ices$/i'  => '\1ex',
        // basics
        '/([ti])a$/i' => '\1um', // latin
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/(x|ch|ss|sh)es$/i' => '\1',
        '/ses$/i' => 'sis',
        '/s$/' => '',
    );

    /**
     * Transforms to singular
     * @return LanguageInflection
     */
    public function toSingular(){
        $word = $this->getWord();
        foreach($this->singulars as $pattern => $replacement){
            if( preg_match($pattern, $word) ){
                $new = preg_replace($pattern, $replacement, $word); // pattern translation
                $this->setWord($new);
                break;
            }
        }
        return $this;
    }

    /**
     * Plural translation patterns
     * [RegExp] => replacement
     * @var <array>
     */
    private $plurals = array(
        // words
        '/(m)an$/i' => '\1en',
        '/(ch)ild$/i' => '\1ildren',
        '/(g)oose$/i' => '\1eese',
        '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)us$/i' => '\1i',
        '/^(m|l)ouse$/i' => '\1ice',
        '/(matr|vert|ind)(ix|ex)$/i'  => '\1ices',
        // basics
        '/([ti])um$/i' => '\1a', // latin
        '/([^aeiouy]|qu)y$/i' => '\1ies',
        '/(x|ch|ss|sh)$/i' => '\1es',
        '/sis$/i' => 'ses',
        '/s$/' => 's',
        '/$/' => 's'
    );

    /**
     * Transforms to plural
     * @return LanguageInflection
     */
    public function toPlural(){
        $word = $this->getWord();
        foreach($this->plurals as $pattern => $replacement){
            if( preg_match($pattern, $word) ){
                $new = preg_replace($pattern, $replacement, $word); // pattern translation
                $this->setWord($new);
                break;
            }
        }
        return $this;
    }

    /**
     * Transforms word to lower case
     * @return LanguageInflection
     */
    public function toLowerCase(){
        $this->setWord( strtolower($this->getWord()) );
        return $this;
    }

    /**
     * Transforms word to upper case
     * @return LanguageInflection
     */
    public function toUpperCase(){
        $this->setWord( strtoupper($this->getWord()) );
        return $this;
    }

    /**
     * Transforms word to title case (e.g.: 'One Two Three-Four')
     * @return LanguageInflection
     */
    public function toTitleCase(){
        $word = $this->getWord();
        // uppercase first letter
        $word = ucfirst($word);
        // replace all characters after a space with their upper case equivalend
        $word = preg_replace('/( [a-z])/e', "strtoupper('\\1')", $word);
        // save
        $this->setWord($word);
        return $this;
    }

    /**
     * To string
     * @return string
     */
    public function __toString(){
        return $this->getWord();
    }

    /**
     * Alias for __toString
     * @return string
     */
    public function toString(){
        return $this->__toString();
    }
}