<?php
/**
 * Tests WebRouting functions
 */
include '../../start.php';
if( isset($_GET['function']) && method_exists('WebRouting', $_GET['function']) ){
    $function = @$_GET['function'];
    $parameter = @$_GET['parameter'];
    echo WebRouting::$function($parameter);
}