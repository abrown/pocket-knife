<!doctype html>
<html>
    <head>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/style.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <header><h1><?php echo $data->getURI(); ?></h1></header>
        <?php echo WebPage::getResourceTable($data); ?>
    </body>
</html>