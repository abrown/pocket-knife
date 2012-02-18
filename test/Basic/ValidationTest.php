<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class BasicValidationTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(dirname(__FILE__)));
        require $path . '/start.php';
        // get code
        autoload('BasicClass');
        BasicClass::autoloadAll('BasicValidation');
    }

    /**
     * Demonstrates testing a list of values against a list of rules, including
     * wildcards
     */
    public function testValidate() {
        $list = array('a' => 1, 'b' => 2, 'c' => 3);
        $rules = array('*' => BasicValidation::NUMERIC, 'a' => BasicValidation::FLOAT);
        $actual = BasicValidation::validateList($list, $rules);
        $expected = array('a' => array('"1" is not a float'));
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Demonstrates sanitizing some values using the BasicValidation types
     */
    public function testSanitize(){
        $list = array('html' => '<script>dosomething();</script>...', 'url' => 'yahoo.com');
        $rules = array('*' => BasicValidation::STRING, 'html' => BasicValidation::HTML, 'url' => BasicValidation::URL);
        $actual = BasicValidation::sanitizeList($list, $rules);
        $expected = array('html' => '&lt;script&gt;dosomething();&lt;/script&gt;...', 'url' => 'http://yahoo.com/');
        $this->assertEquals($expected, $actual);        
    }
    
    /**
     * Demonstrates how the is() function tests a value to ensure
     * compliance with set rules
     */
    public function testIs() {
        $b = new BasicValidation();
        // NULL
        $this->assertEquals(true, $b->is(null, $b::IS_NULL));
        $this->assertEquals(false, $b->is(3, $b::IS_NULL));
        // BOOLEAN
        $this->assertEquals(true, $b->is(true, $b::BOOLEAN));
        $this->assertEquals(false, $b->is(0, $b::BOOLEAN));
        // INT
        $this->assertEquals(true, $b->is(42, $b::INTEGER));
        $this->assertEquals(false, $b->is("23", $b::INTEGER));
        // FLOAT
        $this->assertEquals(true, $b->is(3.2, $b::FLOAT));
        $this->assertEquals(false, $b->is(1, $b::FLOAT));
        // STRING
        $this->assertEquals(true, $b->is("...", $b::STRING));
        $this->assertEquals(false, $b->is(null, $b::STRING));
        // OBJECT
        $this->assertEquals(true, $b->is(new stdClass(), $b::OBJECT));
        $this->assertEquals(false, $b->is("...", $b::OBJECT));
        // SCALAR
        $this->assertEquals(true, $b->is(0, $b::SCALAR));
        $this->assertEquals(false, $b->is(array(), $b::SCALAR));
        // NUMERIC
        $this->assertEquals(true, $b->is('23', $b::NUMERIC));
        $this->assertEquals(false, $b->is('. 4', $b::NUMERIC));
        // EMPTY
        $this->assertEquals(true, $b->is(array(), $b::IS_EMPTY));
        $this->assertEquals(false, $b->is(' ', $b::IS_EMPTY));
        // !EMPTY
        $this->assertEquals(true, $b->is(array(' '), $b::NOT_EMPTY));
        $this->assertEquals(false, $b->is(0, $b::NOT_EMPTY));
        // ALPHANUMERIC
        $this->assertEquals(true, $b->is('ab_2340 ', $b::ALPHANUMERIC));
        $this->assertEquals(false, $b->is('path/to/file', $b::ALPHANUMERIC));
        // EMAIL
        $this->assertEquals(true, $b->is('example@site.com', $b::EMAIL));
        $this->assertEquals(false, $b->is('a <ex@s.com>', $b::EMAIL));
        // URL
        $this->assertEquals(true, $b->is('http://www.google.com', $b::URL));
        $this->assertEquals(false, $b->is('gooble.com!#asdf', $b::URL));
        // DATE
        $this->assertEquals(true, $b->is('Sat, 18 Feb 2012 10:19:34 -0500', $b::DATE));
        $this->assertEquals(true, $b->is('tomorrow', $b::DATE));
        $this->assertEquals(false, $b->is('example', $b::DATE));
        // HTML
        $this->assertEquals(true, $b->is('<html><body><p>Hello World!</p></body></html>', $b::HTML));
        $this->assertEquals(false, $b->is('<li><a>malformed...</li>', $b::HTML));
        // SQL
        $this->assertEquals(true, $b->is('SELECT * FROM table', $b::SQL));
        $this->assertEquals(false, $b->is('...', $b::SQL));
    }
}