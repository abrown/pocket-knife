<!doctype html>
<html>
    <head>
        <title>OPTIONS</title>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/style.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><?php echo WebUrl::getURI(); ?> OPTIONS</h1></header>
        <hr class="title"/>
        <p><i>Allowed HTTP Methods: </i><?php echo implode(', ', $resource->methods); ?></p>
        <p><i>Public Properties: </i><?php echo implode(', ', $resource->properties); ?></p>
    </body>
</html>