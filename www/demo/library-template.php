<!doctype html>
<html>
    <head>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/style.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><?php echo $data->getURI(); ?></h1></header>
        <?php
        foreach($data->items as $book){
            $url = WebUrl::getLocationUrl().$book->getURI();
            echo "<p>{$book->title}, by {$book->author}";
            $methods = $book->OPTIONS()->methods;
            foreach($methods as $method){
                $_url = WebUrl::normalize($url.'/'.$method);
                echo " <a href='{$_url}'>$method</a>";
            }
            echo "</p>";
        }
        pr($data);
        ?>
    </body>
</html>
