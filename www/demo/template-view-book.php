<!doctype html>
<html>
    <head>
        <title><?php echo $resource->getURI(); ?></title>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/style.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><?php echo $resource->getURI(); ?></h1></header>
        <hr class="title"/>
        <nav><a href="<?php echo WebUrl::createAnchoredUrl('library'); ?>">Back to Library</a></nav>
        <?php echo WebPage::getResourceTable($resource); ?>
    </body>
</html>