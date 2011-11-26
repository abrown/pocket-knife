<!doctype html>
<html>
    <head>
        <title>PocketKnife Test Suite</title>
        <base href="test/index.php/" />
        <link rel="stylesheet" href="home/style/reset.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="../style.css" type="text/css" media="screen" />
    </head>
    <body>

        <div id="tests"><?php

        // TODO: write tests to verify pocket-knife works
        require '../start.php';

        // turn on error reporting
        ini_set('display_errors','2');
        ERROR_REPORTING(E_ALL);

        // run tests
        try{
            $page = Routing::getToken('object').'.php';
            if( is_file($page) ){
                include $page;
            }
        }
        catch(Exception $e){}
        ?></div>
        <div id="menu">
            <h3>Tests</h3><?php
            foreach( glob('*.php') as $file ){
                if( $file == 'index.php' ) continue;
                $_file = str_replace('.php', '', $file);
                echo "<a href='$_file'>$_file</a><br/>";
            }
        ?></div>
    </body>
</html>
