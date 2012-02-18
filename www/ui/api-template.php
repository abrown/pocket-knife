<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

?>
<!doctype html>
<html>
    <head>
        <title>pocket-knife: API/<?php echo $title; ?></title>
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/styles/reset.css" media="all" type="text/css" rel="stylesheet" />
        <link href="<?php echo WebUrl::getDirectoryUrl(); ?>/styles/base.css" media="all" type="text/css" rel="stylesheet" />
	<!--<link href="/pocket-knife/www/styles/vertical-rhythm.css" media="all" type="text/css" rel="stylesheet" />-->

    </head>
    <body>
        
        <!-- TITLE -->
        <h1>pocket-knife: API</h1>
        <hr class="title"/>
        
        <!-- DOCs -->
        <div class="documentation">
        	<?php echo $html; ?>
        </div>
        
    </body>
</html>