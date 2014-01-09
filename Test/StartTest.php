<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class StartTest extends \PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        $pocket_knife_path = dirname(dirname(__FILE__));
        require $pocket_knife_path . '/Basic/Benchmark.php';
        BasicBenchmark::startMemoryTest();
        BasicBenchmark::startTimer();
        // start pocket knife and benchmark it
        require_once $pocket_knife_path . '/start.php';
        autoload('Error');
        // display benchmark results
        BasicBenchmark::endTimer();
        BasicBenchmark::endMemoryTest();
        echo 'Load: ' . BasicBenchmark::getTimeElapsed() . 's and ' . BasicBenchmark::getMemoryUsed();
    }

    public function testGetBaseDir() {
        $path1 = get_base_dir();
        // change directory to .. from here
        chdir(dirname(dirname(__FILE__)));
        $path2 = getcwd();
        // test
        $this->assertEquals($path1, $path2);
    }

    public function testAutoload() {
        autoload('Service');
        $this->assertContains('Service', get_declared_classes());
    }

    public function testAddIncludePath() {
        add_include_path('/etc');
        $this->assertRegExp('#/etc#i', get_include_path());
    }

    public function testPr() {
        $this->expectOutputString("<pre>test</pre>\n");
        pr('test');
    }

    public function testGetPublicVars() {
        $class = new Example();
        $reflect = new ReflectionClass($class);
        $expected = array();
        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $var) {
            $expected[$var->name] = null;
        }
        $actual = get_public_vars($class);
        $this->assertEquals($expected, $actual);
    }

    public function testToObject() {
        $array_thing = array('a' => 1, 'b' => 'two', 'c' => array(1, 'two', 3.0));
        $object = to_object($array_thing);
        $this->assertEquals(1, $object->a);
        $this->assertEquals('two', $object->b);
        $this->assertEquals(3.0, $object->c->{2});
    }
}

/**
 * Simple test class
 */
class Example {

    public $a;
    protected $b;
    private $c;

}
