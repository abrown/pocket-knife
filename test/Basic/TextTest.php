<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
if (!class_exists('TestCase'))
    require '../Case.php';

class BasicTextTest extends TestCase {

    /**
     * Demonstrates usage
     */
    public function testUsage() {
        // paragraph style
        $expected = 'Blog Posts';
        $text = new BasicText('blog_posts.php');
        $actual = $text->toParagraphStyle()->removeFileExtension()->toString();
        $this->assertEquals($expected, $actual);
        // camel-case
        $expected = 'PocketKnifeProject';
        $text = new BasicText('pocket_knife_project');
        $actual = $text->toCamelCaseStyle()->toString();
        $this->assertEquals($expected, $actual);
        // pluralize
        $expected = 'Libraries';
        $text = new BasicText('Library');
        $actual = $text->toPlural()->toString();
        $this->assertEquals($expected, $actual);
    }

}