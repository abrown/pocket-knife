<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
require_once dirname(__DIR__) . '/start.php';

class ResourceTreeTest extends TestCase {

    /**
     * Setup
     */
    public static function setUpBeforeClass() {
        // setup URL
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/directory/index.php/level1/35/level2/xyz/level3/92';
    }

    /**
     * Test methods like getParent(), getChild(), and getLastChild(); 
     */
    public function testNavigation() {
        $tree = new ResourceTree();
        $this->assertEquals('level2', get_class($tree->getChild()->getChild()));
        $last_level = $tree->getLastChild();
        $this->assertEquals('level3', get_class($last_level));
        $this->assertEquals('level2', get_class($last_level->getParent()));
        $this->assertEquals('level1', get_class($last_level->getParent()->getParent()));
        $this->assertEquals('level2', get_class($last_level->getParent()->getParent()->getChild()));
    }

    /**
     * Test the depth; must equal number of layers beneath the current one
     */
    public function testDepth() {
        $tree = new ResourceTree();
        $this->assertEquals(3, $tree->getDepth());
    }

    /**
     * Test OPTIONS output 
     */
    public function testOptions() {
        $tree = new ResourceTree();
        $response = $tree->OPTIONS();
        $this->assertEquals('GET', $response->children['level1']->children['level2']->methods[0]);
    }

    /**
     * Test adding invalid child 
     */
    public function testInvalidChild() {
        // set exception
        $this->setExpectedException('Error');
        // create tree
        $tree = new ResourceTree();
        $tree->getChild()->setChild(new stdClass()); // throws error, child must be of type 'level2'
    }

    /**
     * Test URI 
     */
    public function testURI() {
        $tree = new ResourceTree();
        $this->assertEquals('/ResourceTree/*/level1/35/level2/xyz/level3/92', $tree->getURI());
    }

}

test_autoload('ResourceTree', 'StorageMemory');

class level1 extends ResourceTree {

    protected $allowed_children = array('level2');
    protected $storage = array('type' => 'memory');

}

class level2 extends ResourceTree {

    protected $allowed_children = array('level3');
    protected $storage = array('type' => 'memory');

}

class level3 extends ResourceTree {

    protected $allowed_children = false;
    protected $storage = array('type' => 'memory');

}