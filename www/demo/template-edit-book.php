<!doctype html>
<html>
    <head>
        <title><?php echo $resource->getURI(); ?></title>
        <link href="<?php echo WebUrl::create('style.css'); ?>" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1>Edit <i><?php echo ($resource->title) ? $resource->title : 'New Book'; ?></i></h1></header>
        <hr class="title"/>
        <nav><a href="<?php echo WebUrl::createAnchoredUrl('library'); ?>">Back to Library</a></nav>
        <?php echo WebPage::getResourceForm($resource); ?>
    </body>
</html>