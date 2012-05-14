<!doctype html>
<html>
    <head>
        <title><?php echo $resource->getURI(); ?></title>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/style.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><?php echo $resource->getURI(); ?></h1></header>
        <hr class="title"/>
        <?php
        $new = WebUrl::create('book', false) . '?edit=true';
        echo "<p><a href='{$new}'>New</a></p>";        
        foreach($resource->items as $book){
            $url = WebUrl::getLocationUrl().$book->getURI();
            echo "<p>{$book->title}, by {$book->author} ";
            $view = WebUrl::create($book->getURI(), false);
            $edit = $view . '?edit=true';
            $delete = $view . '?method=DELETE';
            echo " <a href='{$view}'>view</a>";
            echo " <a href='{$edit}'>edit</a>";
            echo " <a href='{$delete}'>delete</a>";
            echo "</p>";
        }
        pr($resource);
        ?>
    </body>
</html>
