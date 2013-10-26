<!doctype html>
<html>
    <head>
        <title><?php echo $resource->getURI(); ?></title>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/style.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><i><?php echo $resource->title; ?></i></h1></header>
        <hr class="title"/>
        <nav>
            <a href="<?php $_GET['edit'] = 'true'; echo WebUrl::createAnchoredUrl($resource->getURI(), true); ?>">Edit This Book</a>
            <a href="<?php echo WebUrl::createAnchoredUrl('library'); ?>">Back to Library</a>
        </nav>
<?php echo WebPage::getResourceTable($resource); ?>
    </body>
</html>