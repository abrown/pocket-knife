<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

?>
<!doctype html>
<html>
    <head>
        <title>pocket-knife: Administration</title>
        <link href="<?php echo WebRouting::getDirectoryUrl(); ?>/styles/reset.css" media="all" type="text/css" rel="stylesheet" />
	<link href="<?php echo WebRouting::getDirectoryUrl(); ?>/styles/base.css" media="all" type="text/css" rel="stylesheet" />
        <!--<link href="/pocket-knife/www/styles/vertical-rhythm.css" media="all" type="text/css" rel="stylesheet" />-->
    </head>
    <body>
        
        <!-- TITLE -->
        <h1>pocket-knife: Administration</h1>
        <hr class="title"/>
        
        <!-- ADMIN -->    
        <div class="admin">
        	<template:content/>
        </div>
</body>
</html>