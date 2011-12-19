<?php
$t = new Test('Functions', false);
$t->test('pr() prints an object and returns true if object evaluates to true', 'pr(array("a"=>1,"b"=>2))');
$t->test('get_public_vars() returns array of object\'s accessible variables', 'pr( new Test() )');
$t->test('get_base_dir() returns string of pocket-knife base directory', 'pr( get_base_dir() )');

$t = new Test('Settings');
$t->test('Saved as array', '!is_array($instance)');
$t->test('Set path', '$instance->setPath("test-Settings")');
$t->test('Get path', '$instance->getPath()');
$t->test('Load Settings file', '$instance->getInstance()');
$t->test('Access values', '($a = $instance->getInstance()) && $a["includes"] && pr($a)');
