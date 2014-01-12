<?php

// set test dir
function get_test_dir() {
    return dirname(__FILE__);
}

// load pocket-knife
$path = dirname(get_test_dir());
require_once $path . '/start.php';

// load common classes
autoload('BasicClass');
autoload('Error');
autoload('Settings');

// setup test autoload
function test_autoload() {
    foreach (func_get_args() as $class) {
        BasicClass::autoloadAll($class);
    }
}

// load generic storage test
//require_once get_test_dir() . '/Storage/Generic.php';

// load generic test case
require_once get_test_dir() . '/Case.php';