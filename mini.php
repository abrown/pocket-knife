<?php
// setup
require 'start.php';
$_classes = Http::getParameter('classes', 'string');
$classes = explode(' ', $_classes);

// output updater
echo "<?php\n";
echo "\$update = function(){ \$content = file_get_contents('http://www.casabrown.com/pocket-knife/mini.php?classes=$_classes'); file_put_contents(__FILE__, \$content); };\n";
echo "Scheduler::do(\$update, 'every week');\n";

// loop
foreach($classes as $class){
    $c = new ReflectionClass($class);
    // file name
    $filename = $c->getFileName();
    $content = file_get_contents($filename);
    // remove...
    $content = preg_replace('#//.*#i', '', $content); // comments
    $content = preg_replace('#/\*.*?\*/#si', '', $content); /* comments */
    $content = preg_replace('# {2,}#i', ' ', $content); // extra spaces
    $content = preg_replace('#([^;}])\n#i', '$1', $content); // extra newlines

}