<?php
// TODO: multiple classes in a file is buggy, try with Security.Authentication

// setup
require 'start.php';
header('Content-type: text/plain');

// get classes to pack
$_classes = Http::getParameter('classes', 'string');
if( !$_classes ){
    echo "What classes do you want to pack?\n";
    echo "Add them to a 'classes' GET variable and separate them with +.\n";
    echo "Use the 'update_on' to specify when the script should auto-update.\n";
    die();
}
$classes = explode(' ', $_classes);

// get update on
$update_on = Http::getParameter('update_on', 'int');
if( !$update_on ) $update_on = time() + 7*24*60*60; // default to a week from now

// print class function
function print_class($class_name){
    static $printed = array();
    if( in_array($class_name, $printed) ) return;
    if( !class_exists($class_name) ){
        return "// Could not find '$class_name'";
    }
    // get reflection
    try{
        $class = new ReflectionClass($class_name);
    }
    catch(ReflectionException $e){
        return "// Could not find '$class_name'";
    }
    // file name
    $filename = $class->getFileName();
    $content = file_get_contents($filename);
    $lines = explode("\n", $content);
    // remove ...
    $content = preg_replace('#<\?php|\?>#i', '', $content); // php tags
    $content = preg_replace('#//.*#i', '', $content); // comments
    $content = preg_replace('#/\*.*?\*/#si', '', $content); /* comments */
    $content = preg_replace('#\r\n|\n#i', '', $content); // extra newlines // ([^;}])
    $content = preg_replace('# {2,}#i', ' ', $content); // extra spaces
    $content = preg_replace('#( ?(?:abstract |static )?(?:private|protected|public) (?:abstract |static )?(?:function)?)#i', "\n".'$1', $content); // newline for properties and methods
    // print and free up memory
    echo $content."\n";
    unset($content);
    // add dependencies
    $parent = $class->getParentClass();
    if( $parent !== false && !in_array($parent, $printed) ){
        print_class($parent->getName());
    }
    $interfaces = $class->getInterfaceNames();
    if( !empty($interfaces) ){
        foreach($interfaces as $i){
            print_class($i);
        }
    }
    // add dependencies for classes squirreled in at the end of the file
    if( $class->getEndLine() + 1 < count($lines) ){
        $haystack = array_slice($lines, $class->getEndLine());
        if( preg_match_all('#(?:extends|implements) +(\w+)#i', implode("\n", $haystack), $matches) ){
            foreach($matches[1] as $m){
               print_class($m);
            }
        }
    }
    // return
    $printed[] = $class_name;
}

// output updater
echo "<?php\n";
echo "\$url = 'http://www.casabrown.com/pocket-knife/mini.php?classes=$_classes';\n";
echo "\$update_on = $update_on;\n";
echo "if( time() > \$update_on ){ \$content = file_get_contents(\$url); file_put_contents(__FILE__, \$content); }\n";
echo "// CLASSES FOLLOW:\n";

// loop
foreach($classes as $class){
    print_class($class);
}