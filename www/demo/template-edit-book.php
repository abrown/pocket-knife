<!doctype html>
<html>
    <head>
        <title><?php echo $resource->getURI(); ?></title>
        <link href="<?php echo WebUrl::create('style.css'); ?>" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><?php echo $resource->getURI(); ?></h1></header>
        <hr class="title"/>
        <?php echo WebPage::getResourceForm($resource); ?>
    </body>
</html>