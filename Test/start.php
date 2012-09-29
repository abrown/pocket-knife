<?php

// set test dir
function get_test_dir() {
    return dirname(__FILE__);
}

// load pocket-knife
$path = dirname(dirname(__FILE__));
require_once $path . '/start.php';

// load BasicClass
autoload('BasicClass');

function test_autoload() {
    foreach (func_get_args() as $class) {
        BasicClass::autoloadAll($class);
    }
}

// load generic storage test
require_once $path . '/test/Storage/Generic.php';

// load Settings class
autoload('Settings');
