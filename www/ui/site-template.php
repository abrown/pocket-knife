<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

$lang = new LanguageInflection($name);
$title = $lang->toParagraphStyle()->removeFileExtension()->toString();
if( !$title ) $title = 'Home';
?>
<!doctype html>
<html>
    <head>
        <title>pocket-knife: <?php echo $title; ?></title>
        <link href="/pocket-knife/www/styles/reset.css" media="all" type="text/css" rel="stylesheet" />
		<link href="/pocket-knife/www/styles/base.css" media="all" type="text/css" rel="stylesheet" />
        <link href="/pocket-knife/www/styles/vertical-rhythm.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        
        <!-- TITLE -->
        <h1>pocket-knife: <?php echo $title; ?></h1>
        <hr class="title"/>
        
        <!-- CONTENT -->
        <div class="documentation">
        	<?php if( !$content ){
        		echo file_get_contents('docs/home.html');
        	}
        	else{
        		echo $content;
        	}?>
        </div>
        
        <!-- SITE MAP -->
        <hr/>
        <a name="site-map"></a>
        <h2>Site Map</h2>
        <ul class="sitemap">
        <?php 
        $url = WebRouting::getLocationUrl();
        $lang = new LanguageInflection('');
        foreach( $site->getSiteMap() as $file ){
        	$lang->setWord($file);
        	$name = $lang->toParagraphStyle()->removeFileExtension()->toString();
        	echo "<li><a href='$url/$file'>$name</a></li>";
        }
        ?>
        </ul>
        
    </body>
</html>