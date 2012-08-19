<?php
include 'start.php';
WebHttp::redirect(WebUrl::getDirectoryUrl() . 'www/index.php');
?>
<!doctype html>
<html>
    <head>
        <title>pocket-knife: Home</title>
        <link href="/pocket-knife/www/styles/reset.css" media="all" type="text/css" rel="stylesheet" />
        <link href="/pocket-knife/www/styles/exception.css" media="all" type="text/css" rel="stylesheet" />
    </head>
    <body>
        <div class="exception">
            <h1>Something isn't working right</h1>
            <p>This page should automatically redirect 
                <a href="/pocket-knife/www/index.php" title="Best guess at the URL...">here</a>.
            </p>
        </div>
    </body>
</html>