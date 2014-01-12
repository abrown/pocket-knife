<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class WebTemplateTest extends PHPUnit_Framework_TestCase {

    public static $FILE = '<?php echo \'a\' ?> = b + 2';

    /**
     * Demonstrates use of a string template
     */
    public function testString() {
        $template = new WebTemplate("<?php echo 'a' ?> = b + <template:c/>", WebTemplate::STRING);
        $template->replace('c', '2');
        $expected = "<?php echo 'a' ?> = b + 2";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Demonstrates use of a file template
     */
    public function testFile() {
        $this->markTestSkipped('Overloading file_get_contents() is too difficult');
        /**
          $template = new WebTemplate('web-template.php', WebTemplate::FILE);
          $template->replace('c', '2');
          $expected = "<?php echo 'a' ?> = b + 2";
          $actual = $template->toString();
          $this->assertEquals($expected, $actual);
         */
    }

    /**
     * Demonstrates use of a PHP string template
     */
    public function testPHPString() {
        $template = new WebTemplate("<?php echo 'a' ?> = b + <template:c/>", WebTemplate::PHP_STRING);
        $template->replace('c', '2');
        $expected = "a = b + 2";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Demonstrates use of a PHP file
     */
    public function testPHPFile() {
        $this->markTestSkipped('Overloading file_get_contents() is too difficult');
        /**
          $template = new WebTemplate('web-template.php', WebTemplate::PHP_FILE);
          $template->replace('c', '2');
          $expected = "a = b + 2";
          $actual = $template->toString();
          $this->assertEquals($expected, $actual);
         */
    }

    /**
     * Demonstrates switching out token strings
     */
    public function testToken() {
        $template = new WebTemplate("<div><h1><test:title/></h1></div>", WebTemplate::STRING);
        $template->token_begin = '<test:';
        $template->replace('title', 'Title');
        $expected = "<div><h1>Title</h1></div>";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Demonstrates the clean-up functions that remove unused tokens
     */
    public function testCleanup() {
        $template = new WebTemplate("<div><h1><template:title/></h1></div>", WebTemplate::STRING);
        $expected = "<div><h1></h1></div>";
        $actual = $template->toString();
        $this->assertEquals($expected, $actual);
    }

}
