<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for simple templating. The intended use is HTML
 * templating, so the replacement tokens look like
 * '&lt;template:token_name/&gt;' by default (editable in the $token_begin
 * and $token_end properties). The intended work flow is to build a
 * template by passing a path or template text to the constructor; the
 * tokens are replaced with WebTemplate::replace() and PHP variables are
 * set with WebTemplate::setVariable().
 * @uses Error, Error
 * @example
 * // build the instance
 * $template = new Template('file.php', Template::PHP_FILE);
 *
 * // replace a token (in this case, "<template:token/>")
 * $template->replace('token', 'some value...');
 *
 * // sets a variable value in PHP_FILE and PHP_STRING types
 * $t->setVariable('variable', 'some value...');
 *
 * // sends string to the client immediately
 * $t->display();
 *
 * // to save as string
 * $html = $t->toString();
 */
class WebTemplate {

    /**
     * Template source input; can be text or path
     * @var string a string, which depending on its $type, will be a text or a path
     */
    private $input;

    /**
     * Type of template source
     * @var integer one of [STRING, FILE, PHP_STRING, PHP_FILE]
     */
    private $type;

    /**
     * Input types; one of [STRING, FILE, PHP_STRING, PHP_FILE]
     */
    const STRING = 0;
    const FILE = 1;
    const PHP_STRING = 2;
    const PHP_FILE = 3;

    /**
     * Variables made available to PHP templates
     * @var array a list of variables values in the form [var => value, var2 => ...]
     */
    private $variables = array();

    /**
     * Replacement tokens for any template
     * @var array a list of tokens in the form [token => 'replacement text...', ...]
     */
    private $replacements = array();

    /**
     * Attempts to use TidyHTML to clean up code
     * @var boolean
     */
    public $tidy = false;

    /**
     * Inserts the file name of a template as a comment before the template begins
     * @example
     * &lt;!-- FILE = template.html --&gt;
     * [templated text...]
     * @var boolean
     */
    public $insert_comment = true;

    /**
     * Defines the token beginning syntax
     * @example
     * &lt;!-- in an HTML file, write the following: --&gt;
     * &lt;p&gt;&lt;template:var/&gt;&lt;/p&gt;
     *
     * // to be replaced by WebTemplate in your PHP application:
     * $template->replace('var', 'some value...');
     * @var string
     */
    public $token_begin = '<template:';

    /**
     * Defines the token end syntax; see $token_begin
     * @var string
     */
    public $token_end = '/>';

    /**
     * Constructor; defaults to FILE type
     * @param $input string a path to a template file or the text of a template
     * @param $type integer one of one of [STRING, FILE, PHP_STRING, PHP_FILE]
     */
    public function __construct($input, $type = self::FILE) {
        if (!is_string($input))
            throw new Error('Template input must be a string', 500);
        if (!is_integer($type))
            throw new Error('Template input type must be an integer (see class constants)', 500);
        $this->input = $input;
        $this->type = $type;
        $this->setVariable('template', $this);
    }

    /**
     * Sets a variable for use in PHP templates (PHP_FILE or PHP_STRING).
     * The $value is passed by reference.
     * @param string $name
     * @param mixed $value
     */
    public function setVariable($name, &$value) {
        $this->variables[$name] = &$value;
    }

    /**
     * Replaces a token with string.
     * @param string $token a token to replace
     * @param string $string a value to replace with
     */
    public function replace($token, $string) {
        $this->replacements[$token] = $string;
    }

    /**
     * Checks whether given string is a likely path
     * @deprecated unused, but originally intended for automatic template type detection
     * @param string $path
     * @return boolean
     */
    private function isPath($path) {
        $score = 0;
        $target = 2;
        // tests
        if (strpos($path, DS) !== false)
            $score = $score + 2;
        if (strlen($path) < 50)
            $score++;
        if (strpos($path, ' ') === false)
            $score++;
        if (strpos($path, '<') !== false)
            $score = $score - 2;
        // return
        return $score > $target;
    }

    /**
     * Returns the contents of a file as a string. 
     * @param string $file a path to a file
     * @return string
     */
    public function getFile($file) {
        // check file
        if (!is_file($file)) {
            throw new Error('Could not find ' . $file, 404);
        }
        // return
        return file_get_contents($file);
    }

    /**
     * Special function to immediately replace a token with 
     * the contents of the given file. Uses getFile().
     * @param string $token a token to replace.
     * @param string $file a path (absolute or relative to the pocket-knife directory) to a readable file.
     */
    public function replaceFromFile($token, $file) {
        $string = $this->getFile($file);
        // insert comment
        if ($this->insert_comment) {
            $_file = basename($file);
            $string = "<!-- FILE = $_file, TOKEN = $token -->\n" . $string;
        }
        // replace
        $this->replace($token, $this->getFile($file));
    }

    /**
     * Returns evaluated string from PHP code and given variables.
     * Uses ob_-family functions.
     * @param string $string__hide__ a PHP string; strange parameter names are to avoid name collisions when extracting variables
     * @param array $variables__hide__ a list of variables to use within the PHP string, in the form [var => value, ...]
     * @return string
     */
    public function getPHPString($string__hide__, $variables__hide__ = array()) {
        // extract context variables
        if (is_array($variables__hide__))
            extract($variables__hide__);
        // eval code
        ob_start();
        eval('?>' . $string__hide__);
        return ob_get_clean();
    }

    /**
     * Special function to immediately replace a token with a PHP string.
     * Evaluates the code with given variables using getPHPString().
     * @param string $token a token to replace
     * @param string $string a PHP string
     * @param array $variables a list of variables to use within the PHP string, in the form [var => value, ...]
     */
    public function replaceFromPHPString($token, $string, $variables = array()) {
        $string = $this->getPHPString($string, $variables);
        // insert comment
        if ($this->insert_comment)
            $string = "<!-- PHP STRING, TOKEN = $token -->\n" . $string;
        // replace
        $this->replace($token, $string);
    }

    /**
     * Returns evaluated string from PHP file and given variables.
     * Uses ob_-family functions.
     * @param string $file__hide__ a path (absolute or relative to pocket-knife directory) to a PHP file
     * @param array $variables__hide__ a list of variables to use within the PHP file, in the form [var => value, ...]
     * @return string
     */
    public function getPHPFile($file__hide__, $variables__hide__ = array()) {
        // check file
        if (!is_file($file__hide__)) {
            throw new Error('Could not find ' . $file__hide__, 404);
        }
        // get output
        if (is_array($variables__hide__))
            extract($variables__hide__);
        ob_start();
        require($file__hide__);
        return ob_get_clean();
    }

    /**
     * Special function to immediately replace token with an 
     * evaluated PHP file.Evaluates the code with given variables 
     * using getPHPFile().
     * @param string $token a token to replace
     * @param string $file a path (absolute or relative to pocket-knife directory) to a PHP file
     * @param array $variables a list of variables to use within the PHP file, in the form [var => value, ...]
     */
    public function replaceFromPHPFile($token, $file, $variables = array()) {
        $string = $this->getPHPFile($file, $variables);
        // insert comment
        if ($this->insert_comment) {
            $_file = basename($file);
            $string = "<!-- FILE = $_file, TOKEN = $token -->\n" . $string;
        }
        // replace
        $this->replace($token, $string);
    }

    /**
     * Replaces and returns text without dangling tokens
     * @deprecated unused by toString()
     * @return string a string without tokens
     */
    private function replaceDanglingTokens() {
        $pattern = '#' . preg_quote($this->token_begin) . '([A-Z0-9_\.-]+)' . preg_quote($this->token_end) . '#i';
        return preg_replace($pattern, '', $this->text);
    }

    /**
     * Returns all of the unreplaced tokens in the template.
     * @return array a list of all unreplaced tokens
     */
    public function findTokens() {
        $pattern = '#' . preg_quote($this->token_begin) . '([A-Z0-9_\.-]+)' . preg_quote($this->token_end) . '#i';
        $number_of_results = preg_match_all($pattern, $this->text, $matches);
        if ($number_of_results)
            return $matches[1];
        else
            return array();
    }

    /**
     * Returns template text with all current replacements
     * @return string
     */
    public function toString() {
        // turn insert_comment off for a moment
        $on_off = $this->insert_comment;
        $this->insert_comment = false;
        // get template source
        switch ($this->type) {
            case self::STRING: $text = $this->input;
                break;
            case self::FILE: $text = $this->getFile($this->input);
                break;
            case self::PHP_STRING: $text = $this->getPHPString($this->input, $this->variables);
                break;
            case self::PHP_FILE: $text = $this->getPHPFile($this->input, $this->variables);
                break;
            default: throw new SettingsError('Unknown template type: ' . $this->type, 500);
                break;
        }
        // turn insert_comment back to its original state
        $this->insert_comment = $on_off;
        // get replacements
        // TODO: time this against a foreach(...){ str_replace... }
        $offset = 0;
        $end = strlen($text);
        while ($offset < $end) {
            $a = strpos($text, $this->token_begin, $offset);
            if ($a === false)
                break;
            $b = $a + strlen($this->token_begin);
            $c = strpos($text, $this->token_end, $b);
            if ($c === false)
                break;
            $d = $c + strlen($this->token_end);
            // get token and value
            $token = trim(substr($text, $b, $c - $b));
            if (array_key_exists($token, $this->replacements))
                $value = $this->replacements[$token];
            else
                $value = '';
            // replace
            $text = substr($text, 0, $a) . $value . substr($text, $d);
        }
        // tidy code
        if ($this->tidy && class_exists('tidy')) {
            $config = array('indent' => true, 'output-xhtml' => true, 'wrap' => 120, 'indent-spaces' => 4);
            $tidy = new tidy();
            $text = $tidy->repairString($text, $config, 'utf8');
        }
        // return
        return $text;
    }

    /**
     * Sends template text to the client immediately
     */
    public function display() {
        echo $this->toString();
    }

}