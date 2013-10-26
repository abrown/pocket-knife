<!doctype html>
<html>
    <head>
        <title><?php echo $resource->getURI(); ?></title>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/style.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><?php echo $resource->name ?></h1><h4><?php echo $resource->location; ?></h4></header>
        <hr class="title"/>
        <?php
        $new = WebUrl::createAnchoredUrl('book') . '?edit=true';
        echo "<p><a href='{$new}'>Add Book</a></p>";        
        foreach($resource->items as $book){
            echo "<p><i>{$book->title}</i>, by {$book->author} ";
            $view = WebUrl::createAnchoredUrl($book->getURI(), false);
            $edit = $view . '?edit=true';
            $delete = $view . '?method=DELETE';
            echo " <a href='{$view}'>view</a>";
            echo " <a href='{$edit}'>edit</a>";
            echo " <a href='{$delete}'>delete</a>";
            echo "</p>";
        }
        //pr($resource);
        ?>
    </body>
</html>
