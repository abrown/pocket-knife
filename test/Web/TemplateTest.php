<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

class WebTemplateTest extends PHPUnit_Framework_TestCase{
    
    /**
     * Sets environment for all tests
     */
    public static function setUpBeforeClass() {
        // start pocket-knife
        $path = dirname(dirname(dirname(__FILE__)));
        require $path.'/start.php';
        // manually load classes
        autoload('BasicClass');
        BasicClass::autoloadAll('WebTemplate');
    }
    
    /**
     * Sets environment before for each test
     */
    public function setUp(){
        // unneeded
    }
    
    public function testString(){
        $template = new WebTemplate("<?php echo 'a' ?> = b + <template:c/>", WebTemplate::STRING);
        $template->replace('c', '2');
        $expected = "<?php echo 'a' ?> = b + 2";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }
    
    public function testFile(){
        $template = new WebTemplate('test'.DS.'data'.DS.'web-template.php', WebTemplate::FILE);
        $template->replace('c', '2');
        $expected = "<?php echo 'a' ?> = b + 2";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }
    
    public function testPHPString(){
        $template = new WebTemplate("<?php echo 'a' ?> = b + <template:c/>", WebTemplate::PHP_STRING);
        $template->replace('c', '2');
        $expected = "a = b + 2";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }
    
    public function testPHPFile(){
        $template = new WebTemplate('test'.DS.'data'.DS.'web-template.php', WebTemplate::PHP_FILE);
        $template->replace('c', '2');
        $expected = "a = b + 2";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }
    
    public function testToken(){
        $template = new WebTemplate("<div><h1><test:title/></h1></div>", WebTemplate::STRING);
        $template->token_begin = '<test:';
        $template->replace('title', 'Title');
        $expected = "<div><h1>Title</h1></div>";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }
    
    public function testCleanup(){
        $template = new WebTemplate("<div><h1><template:title/></h1></div>", WebTemplate::STRING);
        $expected = "<div><h1></h1></div>";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }
}