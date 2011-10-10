<?php
ini_set('display_errors','2');
ERROR_REPORTING(E_ALL);
if( count(get_included_files()) > 1 ){
    echo '<h3>Speed Test</h3>';
    pr('Use the following link to do a speed test: <a href="/pocket-knife/test/speed.php">Test</a>');
    return;
}

// baseline
echo "<p class='success'>baseline<br/><span class='mute'>Time: 0.0s, Memory: ".memory_get_peak_usage()."b</span></p>";
include '../Basic.Timer.php';
Timer::start();

// start
include '../start.php';
Timer::end();
echo "<p class='success'>include start.php (including Timer)<br/><span class='mute'>Time: ".Timer::result().", Memory: ".memory_get_peak_usage()."b</span></p>";

// get config
Configuration::setPath('test-configuration');
$config = Configuration::getInstance();
//pr($config);
Timer::end();
echo "<p class='success'>get configuration<br/><span class='mute'>Time: ".Timer::result().", Memory: ".memory_get_peak_usage()."b</span></p>";

// start app
$app = new App();
$app->setAllowedObjects('posts');
$app->setInputFormat('Html');
$app->setOutputFormat('Html');
ob_start();
    $app->execute();
ob_end_clean();
Timer::end();
echo "<p class='success'>start app<br/><span class='mute'>Time: ".Timer::result().", Memory: ".memory_get_peak_usage()."b</span></p>";

// setup security
$auth = Authentication::factory('Html');
$auth->setACL('Array');
$auth->getACL()->add('test', 'test');
if( !$auth->isAllowed() ){
    ob_start();
    $auth->challenge();
    ob_end_clean();
}
Timer::end();
echo "<p class='success'>start html authentication<br/><span class='mute'>Time: ".Timer::result().", Memory: ".memory_get_peak_usage()."b</span></p>";


?>
<link rel="stylesheet" href="/pocket-knife/test/style.css" type="text/css" media="screen" />
